<?php

namespace App\Services;

use App\Events\Enrollment\RolloverCommitted;
use App\Events\Enrollment\RolloverUndone;
use App\Exceptions\RolloverNotUndoable;
use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\RolloverBatch;
use App\Models\Student;
use App\Models\StudentAcademicInfo;
use App\Models\User;
use App\Services\Rollover\RolloverPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AcademicYearRolloverService
{
    private StudentEnrollmentService $enrollService;

    public function __construct(StudentEnrollmentService $enrollService)
    {
        $this->enrollService = $enrollService;
    }

    /**
     * Helper to compute next grade level
     */
    private function nextGrade(string $grade): ?string
    {
        $grade = trim($grade);
        if (preg_match('/^(ม|ป|อ)\.(\d+)$/u', $grade, $matches)) {
            $prefix = $matches[1];
            $num = (int) $matches[2];

            if ($prefix === 'ม') {
                if ($num >= 6) {
                    return null;
                }

                return 'ม.'.($num + 1);
            }
            if ($prefix === 'ป') {
                if ($num >= 6) {
                    return 'ม.1';
                }

                return 'ป.'.($num + 1);
            }
            if ($prefix === 'อ') {
                if ($num >= 3) {
                    return 'ป.1';
                }

                return 'อ.'.($num + 1);
            }
        }

        if (is_numeric($grade)) {
            $num = (int) $grade;
            if ($num >= 12) {
                return null;
            }

            return (string) ($num + 1);
        }

        return null;
    }

    /**
     * Preview suggested rollover mappings and warnings
     */
    public function previewRollover(Academy $academy, AcademicYear $from, AcademicYear $to): array
    {
        $entries = [];
        $missingTargets = [];
        $warnings = [];

        $totals = [
            'promote' => 0,
            'graduate' => 0,
            'repeat' => 0,
            'drop' => 0,
            'new_intake' => 0,
            'skip' => 0,
        ];

        // Load all classrooms of the new year
        $toClassrooms = Classroom::where('academic_year_id', $to->id)
            ->where('academy_id', $academy->id)
            ->get();

        // Index classrooms by grade_level and section
        $gradeMap = [];
        $gradeFallbackMap = [];
        $normalizedMap = [];
        $normalizedFallbackMap = [];

        foreach ($toClassrooms as $c) {
            $gradeMap["{$c->grade_level}-{$c->section}"] = $c;
            $gradeFallbackMap[$c->grade_level][] = $c;

            $norm = $this->enrollService->normalizeGradeLevel($c->grade_level);
            if ($norm) {
                $normalizedMap["{$norm}-{$c->section}"] = $c;
                $normalizedFallbackMap[$norm][] = $c;
            }
        }

        // 1. Process active students in the current year
        $activeEnrollments = ClassroomStudent::where('academy_id', $academy->id)
            ->where('academic_year_id', $from->id)
            ->where('status', ClassroomStudent::STATUS_ACTIVE)
            ->with(['student', 'classroom'])
            ->get();

        foreach ($activeEnrollments as $enrollment) {
            $student = $enrollment->student;
            $classroom = $enrollment->classroom;

            if (! $student || ! $classroom) {
                continue;
            }

            $nextGradeLevel = $this->nextGrade($classroom->grade_level);

            if ($nextGradeLevel === null) {
                // Graduate
                $entries[] = [
                    'student_id' => $student->id,
                    'student_name' => "{$student->first_name_th} {$student->last_name_th}",
                    'from_classroom_id' => $classroom->id,
                    'from_classroom_name' => $classroom->name,
                    'to_classroom_id' => null,
                    'to_classroom_name' => null,
                    'action' => 'graduate',
                    'reason' => 'จบการศึกษาสำเร็จ',
                ];
                $totals['graduate']++;
            } else {
                // Try to find matching classroom (same section)
                $targetKey = "{$nextGradeLevel}-{$classroom->section}";
                if (isset($gradeMap[$targetKey])) {
                    $targetClassroom = $gradeMap[$targetKey];
                    $entries[] = [
                        'student_id' => $student->id,
                        'student_name' => "{$student->first_name_th} {$student->last_name_th}",
                        'from_classroom_id' => $classroom->id,
                        'from_classroom_name' => $classroom->name,
                        'to_classroom_id' => $targetClassroom->id,
                        'to_classroom_name' => $targetClassroom->name,
                        'action' => 'promote',
                        'reason' => 'เลื่อนชั้นปกติ',
                    ];
                    $totals['promote']++;
                } elseif (isset($gradeFallbackMap[$nextGradeLevel])) {
                    // Fallback to first section of this grade level
                    $fallbackClassroom = $gradeFallbackMap[$nextGradeLevel][0];
                    $warnMsg = "ห้องเรียน {$nextGradeLevel}/{$classroom->section} ไม่มีในระบบ แนะนำให้จัดเข้าห้อง {$fallbackClassroom->name}";
                    $warnings[] = $warnMsg;

                    $entries[] = [
                        'student_id' => $student->id,
                        'student_name' => "{$student->first_name_th} {$student->last_name_th}",
                        'from_classroom_id' => $classroom->id,
                        'from_classroom_name' => $classroom->name,
                        'to_classroom_id' => $fallbackClassroom->id,
                        'to_classroom_name' => $fallbackClassroom->name,
                        'action' => 'promote',
                        'reason' => 'เลื่อนชั้น (Fallback ห้องเรียน)',
                    ];
                    $totals['promote']++;
                } else {
                    // No classroom for next grade
                    $warnMsg = "ไม่พบห้องเรียนระดับชั้น {$nextGradeLevel} ในปีการศึกษา {$to->name}";
                    $warnings[] = $warnMsg;
                    $missingTargets[] = $nextGradeLevel;

                    $entries[] = [
                        'student_id' => $student->id,
                        'student_name' => "{$student->first_name_th} {$student->last_name_th}",
                        'from_classroom_id' => $classroom->id,
                        'from_classroom_name' => $classroom->name,
                        'to_classroom_id' => null,
                        'to_classroom_name' => null,
                        'action' => 'skip',
                        'reason' => "ข้ามการย้ายห้อง (ไม่พบระดับชั้น {$nextGradeLevel})",
                    ];
                    $totals['skip']++;
                }
            }
        }

        // 2. Process pending intake (students with class_level but no active classroom students rows)
        $pendingStudents = Student::where('academy_id', $academy->id)
            ->whereNotNull('class_level')
            ->where('class_level', '!=', '')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('classroom_students')
                    ->whereColumn('classroom_students.student_id', 'students.id')
                    ->where('classroom_students.status', ClassroomStudent::STATUS_ACTIVE);
            })
            ->get();

        foreach ($pendingStudents as $student) {
            $normalizedLevel = $this->enrollService->normalizeGradeLevel($student->class_level);
            $section = $student->class_section ?: '1';

            $targetKey = "{$normalizedLevel}-{$section}";
            if (isset($normalizedMap[$targetKey])) {
                $targetClassroom = $normalizedMap[$targetKey];
                $entries[] = [
                    'student_id' => $student->id,
                    'student_name' => "{$student->first_name_th} {$student->last_name_th}",
                    'from_classroom_id' => null,
                    'from_classroom_name' => 'เด็กใหม่รอเข้าเรียน',
                    'to_classroom_id' => $targetClassroom->id,
                    'to_classroom_name' => $targetClassroom->name,
                    'action' => 'new_intake',
                    'reason' => 'ลงทะเบียนเด็กใหม่',
                ];
                $totals['new_intake']++;
            } elseif (isset($normalizedFallbackMap[$normalizedLevel])) {
                $fallbackClassroom = $normalizedFallbackMap[$normalizedLevel][0];
                $warnMsg = "ห้องเรียนระดับ {$normalizedLevel}/{$section} ไม่มีในระบบ แนะนำให้จัดเด็กใหม่เข้าห้อง {$fallbackClassroom->name}";
                $warnings[] = $warnMsg;

                $entries[] = [
                    'student_id' => $student->id,
                    'student_name' => "{$student->first_name_th} {$student->last_name_th}",
                    'from_classroom_id' => null,
                    'from_classroom_name' => 'เด็กใหม่รอเข้าเรียน',
                    'to_classroom_id' => $fallbackClassroom->id,
                    'to_classroom_name' => $fallbackClassroom->name,
                    'action' => 'new_intake',
                    'reason' => 'ลงทะเบียนเด็กใหม่ (Fallback ห้องเรียน)',
                ];
                $totals['new_intake']++;
            } else {
                $warnMsg = "ไม่พบห้องเรียนระดับชั้นสำหรับเด็กใหม่ระดับ {$normalizedLevel} ในปีการศึกษา {$to->name}";
                $warnings[] = $warnMsg;

                $entries[] = [
                    'student_id' => $student->id,
                    'student_name' => "{$student->first_name_th} {$student->last_name_th}",
                    'from_classroom_id' => null,
                    'from_classroom_name' => 'เด็กใหม่รอเข้าเรียน',
                    'to_classroom_id' => null,
                    'to_classroom_name' => null,
                    'action' => 'skip',
                    'reason' => "ข้ามเด็กใหม่ (ไม่พบระดับชั้น {$normalizedLevel})",
                ];
                $totals['skip']++;
            }
        }

        return [
            'entries' => $entries,
            'missing_targets' => array_values(array_unique($missingTargets)),
            'totals' => $totals,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * Validate user mapping and return RolloverPlan
     */
    public function planRollover(Academy $academy, AcademicYear $from, AcademicYear $to, array $userMapping): RolloverPlan
    {
        $studentIds = array_column($userMapping, 'student_id');

        // Check duplicates
        if (count($studentIds) !== count(array_unique($studentIds))) {
            throw ValidationException::withMessages([
                'entries' => ['นักเรียนคนเดียวกันไม่สามารถทำงานซ้ำกันในรายการได้'],
            ]);
        }

        $warnings = [];
        $summary = [
            'promote' => 0,
            'graduate' => 0,
            'repeat' => 0,
            'drop' => 0,
            'new_intake' => 0,
            'skip' => 0,
        ];

        // Preload classrooms in $to
        $toClassroomIds = Classroom::where('academic_year_id', $to->id)
            ->where('academy_id', $academy->id)
            ->pluck('id')
            ->toArray();

        foreach ($userMapping as $e) {
            $studentId = $e['student_id'];
            $action = $e['action'] ?? 'skip';
            $toClassroomId = $e['to_classroom_id'] ?? null;

            // Validate student belongs to academy
            $student = Student::where('id', $studentId)->where('academy_id', $academy->id)->first();
            if (! $student) {
                throw ValidationException::withMessages([
                    'entries' => ["ไม่พบนักเรียนรหัส {$studentId} ในสถาบันนี้"],
                ]);
            }

            // Validate target classroom if promote/repeat/new_intake
            if (in_array($action, ['promote', 'repeat', 'new_intake']) && $toClassroomId) {
                if (! in_array($toClassroomId, $toClassroomIds)) {
                    throw ValidationException::withMessages([
                        'entries' => ["ห้องเรียนเป้าหมายรหัส {$toClassroomId} ไม่อยู่ในปีการศึกษาถัดไปหรือผิดสถาบัน"],
                    ]);
                }
            }

            if (isset($summary[$action])) {
                $summary[$action]++;
            } else {
                $summary['skip']++;
            }
        }

        return new RolloverPlan(
            $academy->id,
            $from->id,
            $to->id,
            $userMapping,
            $summary,
            $warnings
        );
    }

    /**
     * Commit rollover plan and write database transitions
     */
    public function commitRollover(RolloverPlan $plan, User $by): RolloverBatch
    {
        return DB::transaction(function () use ($plan, $by) {
            // E3 lock academic year row of from year
            $fromYear = AcademicYear::where('id', $plan->fromYearId)->lockForUpdate()->firstOrFail();
            $toYear = AcademicYear::where('id', $plan->toYearId)->lockForUpdate()->firstOrFail();

            $batchId = (string) Str::uuid();

            $batch = RolloverBatch::create([
                'id' => $batchId,
                'academy_id' => $plan->academyId,
                'from_academic_year_id' => $plan->fromYearId,
                'to_academic_year_id' => $plan->toYearId,
                'status' => 'committed',
                'committed_at' => now(),
                'committed_by_user_id' => $by->id,
                'plan_summary' => [],
                'totals' => [],
            ]);

            $totals = [
                'promote' => 0,
                'graduate' => 0,
                'repeat' => 0,
                'drop' => 0,
                'new_intake' => 0,
                'skip' => 0,
            ];

            $beforeSnapshots = [];

            foreach ($plan->entries as $e) {
                $student = Student::findOrFail($e['student_id']);
                $action = $e['action'] ?? 'skip';

                // Keep record of student current fields ONLY if not skipping
                if ($action !== 'skip') {
                    $beforeSnapshots[$student->id] = [
                        'status' => $student->status,
                        'class_level' => $student->class_level,
                        'class_section' => $student->class_section,
                    ];
                }

                $fromClassroomId = $e['from_classroom_id'] ?? null;
                $toClassroomId = $e['to_classroom_id'] ?? null;
                $reason = $e['reason'] ?? '';

                $fromClassroom = $fromClassroomId ? Classroom::find($fromClassroomId) : null;
                $toClassroom = $toClassroomId ? Classroom::find($toClassroomId) : null;

                switch ($action) {
                    case 'promote':
                        if ($fromClassroom && $toClassroom) {
                            $this->enrollService->promoteStudent($student, $fromClassroom, $toClassroom, $reason ?: 'เลื่อนชั้น', null, $batchId, $by->id);
                        }
                        break;
                    case 'graduate':
                        $this->enrollService->graduateStudent($student, $fromClassroom, $reason ?: 'จบการศึกษา', $batchId, $by->id);
                        break;
                    case 'drop':
                        $this->enrollService->dropStudent($student, $fromClassroom, $reason ?: 'พ้นสภาพ', $batchId, $by->id);
                        break;
                    case 'repeat':
                        if ($toClassroom) {
                            $this->enrollService->repeatStudent($student, $toClassroom, null, $reason ?: 'ซ้ำชั้น', $batchId, $by->id);
                        }
                        break;
                    case 'new_intake':
                        if ($toClassroom) {
                            $this->enrollService->enrollStudent($student, $toClassroom, null, $batchId, $by->id);
                        }
                        break;
                    case 'skip':
                    default:
                        break;
                }

                if (isset($totals[$action])) {
                    $totals[$action]++;
                }
            }

            $batch->update([
                'totals' => $totals,
                'plan_summary' => [
                    'before' => $beforeSnapshots,
                    'entries' => $plan->entries,
                    'from_academic_year_name' => $fromYear->name,
                    'to_academic_year_name' => $toYear->name,
                ],
            ]);

            event(new RolloverCommitted($batch));

            return $batch;
        });
    }

    /**
     * Undo a committed rollover batch within 24 hours
     */
    public function undoRollover(string $batchId, User $by): RolloverBatch
    {
        return DB::transaction(function () use ($batchId, $by) {
            // Lock batch record
            $batch = RolloverBatch::where('id', $batchId)->lockForUpdate()->firstOrFail();

            if (! $batch->isUndoable()) {
                throw new RolloverNotUndoable('Cannot undo rollover batch because the undo window has expired, closed, or already undone.');
            }

            // E8: check if any student has newer changes
            $planSummary = $batch->plan_summary;
            $beforeSnapshots = $planSummary['before'] ?? [];

            foreach (array_keys($beforeSnapshots) as $studentId) {
                // If student has an active enrollment with rollover_batch_id NOT equal to this batch, throw exception
                $newer = ClassroomStudent::where('student_id', $studentId)
                    ->where('status', ClassroomStudent::STATUS_ACTIVE)
                    ->where(function ($q) use ($batchId) {
                        $q->whereNull('rollover_batch_id')
                            ->orWhere('rollover_batch_id', '!=', $batchId);
                    })
                    ->exists();

                if ($newer) {
                    throw new RolloverNotUndoable("Cannot undo rollover: Student ID {$studentId} has newer active enrollment changes.");
                }
            }

            // 1. Restore/Delete ClassroomStudent rows
            $batchEnrollments = ClassroomStudent::where('rollover_batch_id', $batchId)->get();

            foreach ($batchEnrollments as $enrollment) {
                if ($enrollment->status === ClassroomStudent::STATUS_ACTIVE) {
                    // Newly created enrollment row in this batch -> delete
                    $enrollment->delete();
                } else {
                    // Closed enrollment row in this batch -> restore to active
                    $enrollment->update([
                        'status' => ClassroomStudent::STATUS_ACTIVE,
                        'left_at' => null,
                        'leave_reason' => null,
                        'rollover_batch_id' => null,
                    ]);
                }
            }

            // 2. Restore student fields and StudentAcademicInfo snapshots
            $frozenFromYearName = $planSummary['from_academic_year_name'] ?? $batch->fromAcademicYear->name;
            $frozenToYearName = $planSummary['to_academic_year_name'] ?? $batch->toAcademicYear->name;

            foreach ($beforeSnapshots as $studentId => $oldFields) {
                $student = Student::find($studentId);
                if ($student) {
                    // Revert student status/level/section
                    $student->update([
                        'status' => $oldFields['status'],
                        'class_level' => $oldFields['class_level'],
                        'class_section' => $oldFields['class_section'],
                    ]);

                    // Delete new year SAI row scoped to academy
                    StudentAcademicInfo::where('student_id', $studentId)
                        ->whereHas('classroom', fn ($q) => $q->where('academy_id', $batch->academy_id))
                        ->where('academic_year', $frozenToYearName)
                        ->delete();

                    // Restore old year SAI row to is_current = true
                    StudentAcademicInfo::where('student_id', $studentId)
                        ->whereHas('classroom', fn ($q) => $q->where('academy_id', $batch->academy_id))
                        ->where('academic_year', $frozenFromYearName)
                        ->update(['is_current' => true]);

                    // Demote other SAI rows for this student in this academy to false
                    StudentAcademicInfo::where('student_id', $studentId)
                        ->whereHas('classroom', fn ($q) => $q->where('academy_id', $batch->academy_id))
                        ->where('academic_year', '!=', $frozenFromYearName)
                        ->update(['is_current' => false]);
                }
            }

            // Update batch status
            $batch->update([
                'status' => 'undone',
                'undone_at' => now(),
                'undone_by_user_id' => $by->id,
            ]);

            event(new RolloverUndone($batch));

            return $batch;
        });
    }

    /**
     * Manually close the undo window
     */
    public function closeUndoWindow(string $batchId, User $by): void
    {
        DB::transaction(function () use ($batchId) {
            $batch = RolloverBatch::where('id', $batchId)->lockForUpdate()->firstOrFail();
            $batch->update([
                'undo_closed_at' => now(),
            ]);
        });
    }
}
