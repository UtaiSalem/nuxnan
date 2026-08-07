<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use Illuminate\Support\Facades\DB;

class ClassroomRenumberService
{
    public function renumber(Classroom $classroom, bool $dryRun = false, string $sortBy = 'student_id'): array
    {
        $enrollments = ClassroomStudent::join('students', 'classroom_students.student_id', '=', 'students.id')
            ->where('classroom_students.classroom_id', $classroom->id)
            ->where('classroom_students.status', ClassroomStudent::STATUS_ACTIVE)
            ->select(
                'classroom_students.id as pivot_id',
                'classroom_students.student_id as pk_student_id',
                'classroom_students.student_number as old_number',
                'students.student_id as student_code',
                'students.first_name_th',
                'students.last_name_th'
            )
            ->get();

        $total = $enrollments->count();

        if ($total === 0) {
            return [
                'success' => true,
                'dry_run' => $dryRun,
                'total' => 0,
                'changed_count' => 0,
                'preview' => [],
                'message' => 'ห้องนี้ยังไม่มีนักเรียน',
            ];
        }

        // เราต้องการจัดเรียงตามรหัสนักเรียนแบบตัวเลข ไม่ใช่แบบพจนานุกรม (lexicographic)
        // เพราะรหัสนักเรียนอาจมีความยาวไม่เท่ากัน เช่น 6436 ต้องมาก่อน 11149
        // ถ้าเรียงแบบ string "11" จะมาก่อน "6" ซึ่งจะทำให้ลำดับผิด
        $sorted = $enrollments->sort(function ($a, $b) {
            $codeA = $a->student_code;
            $codeB = $b->student_code;

            $aEmpty = $codeA === null || $codeA === '';
            $bEmpty = $codeB === null || $codeB === '';

            if ($aEmpty && ! $bEmpty) {
                return 1;
            }
            if (! $aEmpty && $bEmpty) {
                return -1;
            }
            if ($aEmpty && $bEmpty) {
                return $a->pk_student_id <=> $b->pk_student_id;
            }

            $aIsNumeric = ctype_digit((string) $codeA);
            $bIsNumeric = ctype_digit((string) $codeB);

            if ($aIsNumeric && $bIsNumeric) {
                $cmp = (int) $codeA <=> (int) $codeB;
            } elseif ($aIsNumeric && ! $bIsNumeric) {
                return -1;
            } elseif (! $aIsNumeric && $bIsNumeric) {
                return 1;
            } else {
                $cmp = strnatcmp((string) $codeA, (string) $codeB);
            }

            if ($cmp === 0) {
                return $a->pk_student_id <=> $b->pk_student_id;
            }

            return $cmp;
        })->values();

        $preview = [];
        $changedCount = 0;
        $updates = [];

        foreach ($sorted as $index => $row) {
            $newNumber = $index + 1;
            $changed = $row->old_number !== $newNumber;

            if ($changed) {
                $changedCount++;
                $updates[] = [
                    'pivot_id' => $row->pivot_id,
                    'student_id' => $row->pk_student_id,
                    'new_number' => $newNumber,
                ];
            }

            $fullName = trim("{$row->first_name_th} {$row->last_name_th}");

            $preview[] = [
                'student_id' => $row->pk_student_id,
                'student_code' => $row->student_code,
                'full_name' => $fullName,
                'from' => $row->old_number,
                'to' => $newNumber,
                'changed' => $changed,
            ];
        }

        $message = $dryRun
            ? "ตัวอย่างการจัดเรียงเลขที่ใหม่ {$changedCount} รายการ จากทั้งหมด {$total} คน"
            : "จัดเรียงเลขที่ใหม่เรียบร้อย ({$changedCount} รายการเปลี่ยนแปลง จากทั้งหมด {$total} คน)";

        if ($dryRun) {
            return [
                'success' => true,
                'dry_run' => true,
                'total' => $total,
                'changed_count' => $changedCount,
                'preview' => $preview,
                'message' => $message,
            ];
        }

        if ($changedCount > 0) {
            DB::transaction(function () use ($updates, $classroom) {
                $pivotIds = [];
                $pivotCases = [];
                $pivotBindings = [];

                $studentIds = [];
                $cardCases = [];
                $cardBindings = [];

                foreach ($updates as $update) {
                    $pivotIds[] = $update['pivot_id'];
                    $pivotCases[] = 'WHEN ? THEN ?';
                    $pivotBindings[] = $update['pivot_id'];
                    $pivotBindings[] = $update['new_number'];

                    $studentIds[] = $update['student_id'];
                    $cardCases[] = 'WHEN ? THEN ?';
                    $cardBindings[] = $update['student_id'];
                    $cardBindings[] = $update['new_number'];
                }

                $now = now()->toDateTimeString();

                $pivotSql = 'UPDATE classroom_students SET student_number = CASE id '.implode(' ', $pivotCases).' END, updated_at = ? WHERE id IN ('.implode(',', array_fill(0, count($pivotIds), '?')).')';
                $pivotQueryBindings = array_merge($pivotBindings, [$now], $pivotIds);
                DB::update($pivotSql, $pivotQueryBindings);

                // ต้องอัปเดต order_no ใน student_cards ด้วย เพื่อให้บัตรนักเรียนที่พิมพ์ออกมามีเลขที่ตรงกับในระบบเสมอ
                // หากไม่ทำ ข้อมูลจะเกิดความขัดแย้งเมื่อเทียบกับเลขที่ปัจจุบันในห้องเรียน
                $cardSql = 'UPDATE student_cards SET order_no = CASE student_id '.implode(' ', $cardCases)." END, updated_at = ? WHERE academy_id = ? AND student_status = 'active' AND student_id IN (".implode(',', array_fill(0, count($studentIds), '?')).')';
                $cardQueryBindings = array_merge($cardBindings, [$now, $classroom->academy_id], $studentIds);
                DB::update($cardSql, $cardQueryBindings);
            });
        }

        return [
            'success' => true,
            'dry_run' => false,
            'total' => $total,
            'changed_count' => $changedCount,
            'preview' => $preview,
            'message' => $message,
        ];
    }

    public function renumberAcademy(
        Academy $academy,
        ?int $academicYearId = null,
        ?string $academicYear = null,
        ?string $gradeLevel = null,
        bool $dryRun = false
    ): array {
        // ต้องระบุปีการศึกษาให้ชัดเจนเสมอ ไม่งั้นจะไปเขียนทับห้องของปีเก่า (ประวัติพัง)
        $resolvedYear = null;
        if ($academicYearId) {
            // กัน id ปีการศึกษาของโรงเรียนอื่น
            $resolvedYear = AcademicYear::where('academy_id', $academy->id)
                ->whereKey($academicYearId)
                ->first();
        } elseif ($academicYear) {
            $resolvedYear = AcademicYear::where('academy_id', $academy->id)
                ->where('name', $academicYear)
                ->first();
        } else {
            $resolvedYear = AcademicYear::where('academy_id', $academy->id)
                ->where('is_current', true)
                ->first();
        }
        $resolvedYearId = $resolvedYear?->id;

        if (! $resolvedYearId) {
            return [
                'success' => false,
                'message' => 'ไม่พบปีการศึกษา กรุณาระบุปีการศึกษาที่ต้องการจัดเรียง',
            ];
        }

        $query = Classroom::where('academy_id', $academy->id)
            ->where('academic_year_id', $resolvedYearId)
            ->where('status', 'active');

        if ($gradeLevel) {
            $query->where('grade_level', $gradeLevel);
        }

        $classrooms = $query->orderBy('grade_level')
            ->orderBy('section')
            ->get();

        $classrooms = $classrooms->sort(function ($a, $b) {
            $gradeA = $a->grade_level ?? '';
            $gradeB = $b->grade_level ?? '';
            $cmpGrade = strnatcmp((string) $gradeA, (string) $gradeB);

            if ($cmpGrade !== 0) {
                return $cmpGrade;
            }

            $secA = $a->section ?? '';
            $secB = $b->section ?? '';
            $aIsNumeric = $secA !== '' && ctype_digit((string) $secA);
            $bIsNumeric = $secB !== '' && ctype_digit((string) $secB);

            if ($aIsNumeric && $bIsNumeric) {
                $cmpSec = (int) $secA <=> (int) $secB;
            } else {
                $cmpSec = strnatcmp((string) $secA, (string) $secB);
            }

            if ($cmpSec !== 0) {
                return $cmpSec;
            }

            return $a->id <=> $b->id;
        })->values();

        $classroomCount = 0;
        $affectedClassroomCount = 0;
        $totalStudents = 0;
        $changedCount = 0;
        $classroomsResult = [];

        // ทำเป็น transaction เดียวเพื่อให้เมื่อเกิดข้อผิดพลาด จะได้ไม่เกิดการเรียงเลขที่แค่บางส่วนของโรงเรียน
        $process = function () use ($classrooms, $dryRun, &$classroomCount, &$affectedClassroomCount, &$totalStudents, &$changedCount, &$classroomsResult) {
            foreach ($classrooms as $classroom) {
                $result = $this->renumber($classroom, $dryRun);

                if ($result['total'] === 0) {
                    continue;
                }

                $classroomCount++;
                $totalStudents += $result['total'];

                if ($result['changed_count'] > 0) {
                    $affectedClassroomCount++;
                    $changedCount += $result['changed_count'];
                }

                $classroomsResult[] = [
                    'classroom_id' => $classroom->id,
                    'name' => $classroom->display_name ?? $classroom->name,
                    'grade_level' => $classroom->grade_level,
                    'section' => $classroom->section,
                    'total' => $result['total'],
                    'changed_count' => $result['changed_count'],
                ];
            }
        };

        if ($dryRun) {
            $process();
        } else {
            DB::transaction($process);
        }

        if ($classroomCount === 0) {
            $message = 'ไม่พบห้องเรียนที่ตรงกับเงื่อนไข';
        } elseif ($changedCount === 0) {
            $message = 'ทุกห้องเรียงเลขที่ถูกต้องอยู่แล้ว';
        } else {
            $message = $dryRun
                ? "ตัวอย่างการจัดเรียงเลขที่ {$affectedClassroomCount} ห้อง รวม {$changedCount} รายการ"
                : "จัดเรียงเลขที่เรียบร้อย {$affectedClassroomCount} ห้อง รวม {$changedCount} รายการ";
        }

        return [
            'success' => true,
            'dry_run' => $dryRun,
            'academic_year_id' => $resolvedYearId,
            'academic_year' => $resolvedYear ? $resolvedYear->name : null,
            'classroom_count' => $classroomCount,
            'affected_classroom_count' => $affectedClassroomCount,
            'total_students' => $totalStudents,
            'changed_count' => $changedCount,
            'classrooms' => $classroomsResult,
            'message' => $message,
        ];
    }
}
