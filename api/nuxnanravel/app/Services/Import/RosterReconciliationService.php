<?php

namespace App\Services\Import;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentImportBatch;
use App\Models\StudentImportRow;
use App\Models\User;
use App\Services\StudentCardSyncService;
use App\Services\StudentEnrollmentService;
use App\Services\StudentImportService;
use App\Services\StudentIntakeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RosterReconciliationService
{
    public function __construct(
        private readonly StudentEnrollmentService $enrollmentService,
        private readonly StudentIntakeService $intakeService,
        private readonly StudentCardSyncService $cardSyncService,
        private readonly TeacherAutoIntakeService $teacherAutoIntakeService,
    ) {}

    /**
     * Preview reconciliation and save rows.
     */
    public function preview(
        Academy $academy,
        AcademicYear $year,
        StudentImportBatch $batch,
        array $jsonData
    ): void {
        $batch->update(['status' => 'validating']);
        $batch->rows()->delete();

        // Get previous year (2568 if target is 2569)
        $targetYearInt = (int) $year->name;
        $prevYearName = (string) ($targetYearInt - 1);
        $previousYear = AcademicYear::where('academy_id', $academy->id)
            ->where('name', $prevYearName)
            ->first();

        $previousYearId = $previousYear?->id;

        $rowNumber = 1;
        $seenCodes = [];

        // Fetch all classrooms in the target year for caching
        $classrooms = Classroom::where('academy_id', $academy->id)
            ->where('academic_year_id', $year->id)
            ->get()
            ->keyBy(fn ($c) => "{$c->grade_level}/{$c->section}");

        // Teacher mapping cache
        $teachers = User::whereIn('id', function ($query) use ($academy): void {
            $query->select('academy_members.user_id')
                ->from('academy_members')
                ->leftJoin('academy_roles', 'academy_members.academy_role_id', '=', 'academy_roles.id')
                ->where('academy_members.academy_id', $academy->id)
                ->where(function ($roleQuery): void {
                    $roleQuery->whereIn('academy_roles.name', ['teacher', 'staff'])
                        ->orWhereIn('academy_members.role', ['teacher', 'staff']);
                });
        })->get(['id', 'name']);

        $reconciledStudentIds = [];
        $teacherAssignments = [];

        foreach ($jsonData['classrooms'] ?? [] as $classData) {
            $gradeLevel = $classData['grade_level'];
            $section = $classData['section'];
            $classKey = "{$gradeLevel}/{$section}";
            $classroom = $classrooms->get($classKey);

            $teacherRaw = $classData['homeroom_teacher_raw'] ?? '';
            $teacherMatch = $this->matchTeacher($teacherRaw, $teachers);
            $teacherAssignments[] = [
                'grade_level' => $gradeLevel,
                'section' => $section,
                'teacher_raw' => $teacherRaw,
                'matched_user_id' => $teacherMatch['user']?->id,
                'match_status' => $teacherMatch['status'],
                'candidate_user_ids' => $teacherMatch['candidates'],
                'resolved_user_id' => null,
                'should_auto_create' => $teacherRaw !== '' && $teacherMatch['status'] === 'unmatched',
            ];

            $studentNumberCounter = 1;
            foreach ($classData['students'] ?? [] as $stud) {
                $studentCode = trim((string) ($stud['student_code'] ?? ''));
                if ($studentCode === '') {
                    continue;
                }

                $studentNumber = isset($stud['student_number']) && $stud['student_number'] !== ''
                    ? (int) $stud['student_number']
                    : $studentNumberCounter++;

                $fullName = trim((string) ($stud['full_name'] ?? ''));
                $parsedName = $this->parseThaiFullName($fullName);

                $errors = [];
                $warnings = [];

                if (! $classroom) {
                    $errors[] = ['field' => 'classroom', 'message' => "Classroom {$classKey} not found for academic year {$year->name}. Create it first."];
                }

                if (isset($seenCodes[$studentCode])) {
                    $errors[] = ['field' => 'student_code', 'message' => 'Duplicate student code in import file.'];
                } else {
                    $seenCodes[$studentCode] = $rowNumber;
                }

                // Check student in database
                $student = Student::where('academy_id', $academy->id)
                    ->where('student_id', $studentCode)
                    ->first();

                $action = 'new_intake';
                $diffData = [];

                if ($student) {
                    $reconciledStudentIds[] = $student->id;

                    // Get enrollments
                    $enrollment2569 = ClassroomStudent::where('student_id', $student->id)
                        ->where('academic_year_id', $year->id)
                        ->where('status', 'active')
                        ->first();

                    $enrollment2568 = $previousYearId ? ClassroomStudent::where('student_id', $student->id)
                        ->where('academic_year_id', $previousYearId)
                        ->where('status', 'active')
                        ->first() : null;

                    if ($enrollment2569) {
                        if ($classroom && $enrollment2569->classroom_id === $classroom->id) {
                            $action = 'unchanged';
                        } else {
                            $action = 'transfer_within_year';
                            $diffData = [
                                'from_classroom' => $enrollment2569->classroom?->name ?? 'Unknown',
                                'to_classroom' => $classKey,
                            ];
                        }
                    } elseif ($enrollment2568) {
                        if ($enrollment2568->classroom?->grade_level === $gradeLevel) {
                            $action = 'repeat_student';
                            $diffData = [
                                'from_classroom' => $enrollment2568->classroom?->name ?? 'Unknown',
                                'to_classroom' => $classKey,
                                'source_academic_year_id' => $enrollment2568->academic_year_id,
                            ];
                        } else {
                            $action = 'promote_student';
                            $diffData = [
                                'from_classroom' => $enrollment2568->classroom?->name ?? 'Unknown',
                                'to_classroom' => $classKey,
                                'source_academic_year_id' => $enrollment2568->academic_year_id,
                            ];
                        }
                    } else {
                        $action = 're_enroll';
                        $diffData = [
                            'to_classroom' => $classKey,
                        ];
                    }
                } else {
                    $action = 'new_intake';
                }

                // Format row normalized data for commit process
                $normalized = [
                    'identity' => [
                        'student_id' => $studentCode,
                        'citizen_id' => null,
                    ],
                    'personal' => [
                        'title_prefix_th' => $parsedName['title_prefix_th'],
                        'first_name_th' => $parsedName['first_name_th'],
                        'last_name_th' => $parsedName['last_name_th'],
                    ],
                    'admission' => [
                        'academic_year_id' => $year->id,
                        'grade_level' => $gradeLevel,
                        'section' => $section,
                        'student_number' => $studentNumber,
                    ],
                    'teacher' => [
                        'raw_name' => $teacherRaw,
                        'matched_user_id' => $teacherMatch['user']?->id,
                    ],
                ];

                StudentImportRow::create([
                    'batch_id' => $batch->id,
                    'row_number' => $rowNumber++,
                    'raw_data' => $stud,
                    'normalized_data' => $normalized,
                    'status' => ! empty($errors) ? 'invalid' : (! empty($warnings) ? 'warning' : 'valid'),
                    'action' => $action,
                    'matched_student_id' => $student?->id,
                    'diff_data' => ! empty($diffData) ? $diffData : null,
                    'errors' => ! empty($errors) ? $errors : null,
                    'warnings' => ! empty($warnings) ? $warnings : null,
                ]);
            }
        }

        // Identify missing students (Active in previous year but not in the import JSON)
        if ($previousYearId) {
            $activePrevYearIds = ClassroomStudent::where('academic_year_id', $previousYearId)
                ->where('status', 'active')
                ->pluck('student_id')
                ->toArray();

            $missingIds = array_diff($activePrevYearIds, $reconciledStudentIds);

            foreach ($missingIds as $missingId) {
                $student = Student::find($missingId);
                if (! $student) {
                    continue;
                }

                $enrollment2568 = ClassroomStudent::where('student_id', $student->id)
                    ->where('academic_year_id', $previousYearId)
                    ->where('status', 'active')
                    ->first();

                $grade = trim((string) ($enrollment2568?->classroom?->grade_level ?? ''));
                $isM6 = in_array($grade, ['ม.6', 'ม.๖'], true);

                $action = $isM6 ? 'auto_graduate' : 'flag_missing';

                $normalized = [
                    'identity' => [
                        'student_id' => $student->student_id,
                        'citizen_id' => $student->citizen_id,
                    ],
                    'personal' => [
                        'first_name_th' => $student->first_name_th,
                        'last_name_th' => $student->last_name_th,
                    ],
                    'admission' => [
                        'academic_year_id' => $year->id,
                    ],
                ];

                StudentImportRow::create([
                    'batch_id' => $batch->id,
                    'row_number' => $rowNumber++,
                    'raw_data' => [
                        'student_code' => $student->student_id,
                        'full_name' => $student->full_name_th,
                    ],
                    'normalized_data' => $normalized,
                    'status' => 'valid',
                    'action' => $action,
                    'matched_student_id' => $student->id,
                    'diff_data' => [
                        'last_classroom' => $enrollment2568?->classroom?->name ?? 'Unknown',
                    ],
                ]);
            }
        }

        $batch->update([
            'default_values' => array_merge($batch->default_values ?? [], [
                'classroom_teacher_assignments' => $teacherAssignments,
            ]),
        ]);
        app(StudentImportService::class)->refreshCounters($batch);
        $batch->update(['status' => 'validated']);
    }

    /**
     * Process a single row during commit.
     */
    public function processRow(StudentImportRow $row, User $operator): void
    {
        $data = $row->normalized_data;
        $action = $row->action;
        $academy = $row->batch->academy;
        $academicYear = $row->batch->academicYear;

        DB::transaction(function () use ($row, $data, $action, $academy, $academicYear, $operator) {
            if ($action === 'unchanged') {
                $student = Student::findOrFail($row->matched_student_id);
                $classroomId = $this->findClassroomId($academy->id, $academicYear->id, $data['admission']['grade_level'], $data['admission']['section']);
                $activeEnrollment = ClassroomStudent::where('student_id', $student->id)
                    ->where('classroom_id', $classroomId)
                    ->where('status', 'active')
                    ->first();
                if ($activeEnrollment && isset($data['admission']['student_number'])) {
                    $activeEnrollment->update([
                        'student_number' => $data['admission']['student_number'],
                    ]);
                }
                $row->update(['status' => 'imported', 'student_id' => $student->id]);

                return;
            }

            $student = null;
            if ($action === 'new_intake') {
                // Minimal student intake
                $intakeData = [
                    'identity' => [
                        'student_id' => $data['identity']['student_id'],
                        'citizen_id' => null,
                    ],
                    'personal' => [
                        'title_prefix_th' => $data['personal']['title_prefix_th'] ?? null,
                        'first_name_th' => $data['personal']['first_name_th'],
                        'last_name_th' => $data['personal']['last_name_th'],
                    ],
                    'admission' => [
                        'academic_year_id' => $academicYear->id,
                        'classroom_id' => $this->findClassroomId($academy->id, $academicYear->id, $data['admission']['grade_level'], $data['admission']['section']),
                        'student_number' => $data['admission']['student_number'] ?? null,
                        'enrollment_date' => now()->toDateString(),
                    ],
                    'account' => ['mode' => 'pending_activation'],
                ];

                $result = $this->intakeService->intake($academy, $intakeData, $operator);
                $student = $result['student'];
                // Set incomplete profile flag on student
                $student->update(['remarks' => 'Minimal intake profile. Profile incomplete.']);
            } else {
                $student = Student::findOrFail($row->matched_student_id);

                if (in_array($action, ['promote_student', 'transfer_within_year', 'repeat_student', 're_enroll'])) {
                    $classroomId = $this->findClassroomId($academy->id, $academicYear->id, $data['admission']['grade_level'], $data['admission']['section']);
                    $classroom = Classroom::findOrFail($classroomId);

                    $sourceYearId = $action === 'promote_student'
                        ? (int) ($row->diff_data['source_academic_year_id'] ?? $this->previousAcademicYearId($academy->id, $academicYear))
                        : $academicYear->id;
                    $activeEnrollment = ClassroomStudent::where('student_id', $student->id)
                        ->where('academic_year_id', $sourceYearId)
                        ->where('status', 'active')
                        ->first();

                    if ($action === 'transfer_within_year' && $activeEnrollment) {
                        $this->enrollmentService->transferStudent($student, $activeEnrollment->classroom, $classroom, 'ย้ายห้อง', null, $operator->id);
                    } elseif ($action === 'promote_student' && $activeEnrollment) {
                        $this->enrollmentService->promoteStudent($student, $activeEnrollment->classroom, $classroom, 'เลื่อนชั้น', $data['admission']['student_number'] ?? null, null, $operator->id);
                    } elseif ($action === 'repeat_student' && $activeEnrollment) {
                        $this->enrollmentService->repeatStudent($student, $classroom, $data['admission']['student_number'] ?? null, 'ซ้ำชั้น', null, $operator->id);
                    } else {
                        // re_enroll or no active enrollment found
                        $this->enrollmentService->enrollStudent($student, $classroom, $data['admission']['student_number'] ?? null, null, $operator->id);
                    }
                } elseif ($action === 'auto_graduate') {
                    $this->enrollmentService->graduateStudent($student, null, 'จบการศึกษาโดยอัตโนมัติจาก Roster reconciliation', null, $operator->id);
                } elseif ($action === 'flag_missing') {
                    $row->update([
                        'status' => 'imported',
                        'student_id' => $student->id,
                        'diff_data' => array_merge($row->diff_data ?? [], [
                            'note' => 'Student flagged as missing from roster. No action taken.',
                        ]),
                    ]);

                    return;
                }
            }

            $row->update([
                'status' => 'imported',
                'student_id' => $student?->id,
            ]);
        });
    }

    private function findClassroomId(int $academyId, int $yearId, string $grade, string $section): int
    {
        $classroom = Classroom::where('academy_id', $academyId)
            ->where('academic_year_id', $yearId)
            ->where('grade_level', $grade)
            ->where('section', $section)
            ->first();

        return $classroom?->id ?? throw new \RuntimeException("Target classroom {$grade}/{$section} does not exist.");
    }

    public function assignHomeroomTeachers(StudentImportBatch $batch, User $operator): array
    {
        $results = ['assigned' => 0, 'skipped' => 0, 'errors' => []];
        foreach ($batch->default_values['classroom_teacher_assignments'] ?? [] as $assignment) {
            try {
                DB::transaction(function () use ($batch, $operator, $assignment, &$results): void {
                    $userId = $assignment['resolved_user_id'] ?? $assignment['matched_user_id'] ?? null;
                    if (! $userId && ($assignment['should_auto_create'] ?? false)) {
                        $userId = $this->teacherAutoIntakeService
                            ->autoCreateTeacher($batch->academy, (string) $assignment['teacher_raw'], $operator)->id;
                    }
                    if (! $userId) {
                        $results['skipped']++;

                        return;
                    }
                    $classroom = Classroom::query()
                        ->where('academy_id', $batch->academy_id)
                        ->where('academic_year_id', $batch->academic_year_id)
                        ->where('grade_level', $assignment['grade_level'])
                        ->where('section', $assignment['section'])
                        ->firstOrFail();
                    $classroom->update(['homeroom_teacher_id' => $userId]);
                    ClassroomMember::updateOrCreate(
                        ['classroom_id' => $classroom->id, 'user_id' => $userId],
                        ['role' => ClassroomMember::ROLE_TEACHER, 'join_method' => ClassroomMember::JOIN_ADMIN,
                            'is_active' => true, 'joined_at' => now(), 'left_at' => null, 'added_by' => $operator->id]
                    );
                    $results['assigned']++;
                });
            } catch (\Throwable $exception) {
                $results['errors'][] = $exception->getMessage();
            }
        }

        return $results;
    }

    private function previousAcademicYearId(int $academyId, AcademicYear $year): int
    {
        return (int) AcademicYear::query()->where('academy_id', $academyId)
            ->where('name', (string) ((int) $year->name - 1))->value('id');
    }

    public function parseThaiFullName(string $fullName): array
    {
        $fullName = trim($fullName);
        $prefixes = ['เด็กชาย', 'เด็กหญิง', 'นาย', 'นางสาว', 'นาง', 'ด.ช.', 'ด.ญ.'];
        $titlePrefix = null;

        foreach ($prefixes as $prefix) {
            if (str_starts_with($fullName, $prefix)) {
                $titlePrefix = $prefix;
                $fullName = trim(substr($fullName, strlen($prefix)));
                break;
            }
        }

        $parts = preg_split('/\s+/u', $fullName, 2);

        return [
            'title_prefix_th' => $titlePrefix,
            'first_name_th' => $parts[0] ?? '',
            'last_name_th' => $parts[1] ?? '-',
        ];
    }

    private function matchTeacher(string $teacherName, Collection $teachers): array
    {
        $teacherName = trim($teacherName);
        if ($teacherName === '') {
            return ['status' => 'unmatched', 'user' => null, 'candidates' => []];
        }

        // Try exact name match
        $normalized = $this->normalizeTeacherName($teacherName);
        $exact = $teachers->filter(fn ($user) => $this->normalizeTeacherName((string) $user->name) === $normalized);
        if ($exact->count() === 1) {
            return ['status' => 'exact', 'user' => $exact->first(), 'candidates' => []];
        }
        if ($exact->count() > 1) {
            return ['status' => 'ambiguous', 'user' => null, 'candidates' => $exact->pluck('id')->all()];
        }

        return ['status' => 'unmatched', 'user' => null, 'candidates' => []];
    }

    private function normalizeTeacherName(string $name): string
    {
        $name = preg_replace('/^(นางสาว|นาง|นาย|น\.ส\.)\s*/u', '', trim($name));

        return preg_replace('/\s+/u', ' ', (string) $name);
    }
}
