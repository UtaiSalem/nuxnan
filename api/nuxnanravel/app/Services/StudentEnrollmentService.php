<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use Illuminate\Support\Facades\DB;

/**
 * StudentEnrollmentService
 *
 * จัดการการลงทะเบียนนักเรียนเข้าห้องเรียน
 * รองรับการเลื่อนชั้น, ย้ายห้อง, ย้ายออกกลางคัน
 *
 * Source of truth: classroom_students pivot table
 */
class StudentEnrollmentService
{
    /**
     * ลงทะเบียนนักเรียนเข้าห้องเรียน
     */
    public function enrollStudent(
        Student $student,
        Classroom $classroom,
        ?int $studentNumber = null
    ): ClassroomStudent {
        return DB::transaction(function () use ($student, $classroom, $studentNumber) {
            // Auto-assign student number if not provided
            if ($studentNumber === null) {
                $studentNumber = ClassroomStudent::where('classroom_id', $classroom->id)
                    ->where('status', 'active')
                    ->max('student_number') + 1;
            }

            // Create or update enrollment record
            $enrollment = ClassroomStudent::updateOrCreate(
                [
                    'classroom_id' => $classroom->id,
                    'student_id' => $student->id,
                ],
                [
                    'academy_id' => $classroom->academy_id,
                    'academic_year_id' => $classroom->academic_year_id,
                    'student_number' => $studentNumber,
                    'status' => ClassroomStudent::STATUS_ACTIVE,
                    'enrolled_at' => now(),
                    'left_at' => null,
                    'leave_reason' => null,
                ]
            );

            // Sync class_level & class_section for backward compatibility
            $student->update([
                'class_level' => $classroom->grade_level,
                'class_section' => $classroom->section,
            ]);

            // Update current StudentAcademicInfo if exists
            $academicInfo = $student->currentAcademicInfo;
            if ($academicInfo) {
                $academicInfo->update([
                    'classroom_id' => $classroom->id,
                    'current_grade' => $classroom->grade_level,
                    'current_class' => $classroom->section,
                    'classroom_full' => $classroom->display_name,
                ]);
            }

            return $enrollment;
        });
    }

    /**
     * ลงทะเบียนนักเรียนหลายคนเข้าห้องเรียน (Bulk)
     */
    public function bulkEnrollStudents(
        Classroom $classroom,
        array $studentIds
    ): int {
        $enrolled = 0;

        DB::transaction(function () use ($classroom, $studentIds, &$enrolled) {
            foreach ($studentIds as $studentId) {
                $student = Student::find($studentId);
                if ($student) {
                    $this->enrollStudent($student, $classroom);
                    $enrolled++;
                }
            }
        });

        return $enrolled;
    }

    /**
     * ย้ายนักเรียนจากห้องเดิมไปห้องใหม่
     */
    public function transferStudent(
        Student $student,
        Classroom $fromClassroom,
        Classroom $toClassroom,
        string $reason = 'ย้ายห้อง'
    ): ClassroomStudent {
        return DB::transaction(function () use ($student, $fromClassroom, $toClassroom, $reason) {
            // ปิด enrollment เดิม
            ClassroomStudent::where('classroom_id', $fromClassroom->id)
                ->where('student_id', $student->id)
                ->where('status', 'active')
                ->update([
                    'status' => ClassroomStudent::STATUS_TRANSFERRED,
                    'left_at' => now(),
                    'leave_reason' => $reason,
                ]);

            // สร้าง enrollment ใหม่
            return $this->enrollStudent($student, $toClassroom);
        });
    }

    /**
     * นักเรียนออกจากห้องเรียน (ย้ายออก, พ้นสภาพ, จบการศึกษา)
     */
    public function unenrollStudent(
        Student $student,
        Classroom $classroom,
        string $status = 'transferred',
        ?string $reason = null
    ): bool {
        return DB::transaction(function () use ($student, $classroom, $status, $reason) {
            $updated = ClassroomStudent::where('classroom_id', $classroom->id)
                ->where('student_id', $student->id)
                ->where('status', 'active')
                ->update([
                    'status' => $status,
                    'left_at' => now(),
                    'leave_reason' => $reason,
                ]);

            return $updated > 0;
        });
    }

    /**
     * เลื่อนชั้นนักเรียนทั้งห้อง (ขึ้นปีการศึกษาใหม่)
     *
     * @param Classroom $fromClassroom ห้องเดิม (ปีที่แล้ว)
     * @param Classroom $toClassroom ห้องใหม่ (ปีใหม่)
     * @param string $reasonForOld เหตุผลที่ปิด enrollment เดิม
     * @return int จำนวนนักเรียนที่เลื่อนชั้น
     */
    public function promoteClassroom(
        Classroom $fromClassroom,
        Classroom $toClassroom,
        string $reasonForOld = 'เลื่อนชั้น'
    ): int {
        $promoted = 0;

        DB::transaction(function () use ($fromClassroom, $toClassroom, $reasonForOld, &$promoted) {
            $activeStudents = ClassroomStudent::where('classroom_id', $fromClassroom->id)
                ->where('status', ClassroomStudent::STATUS_ACTIVE)
                ->with('student')
                ->get();

            foreach ($activeStudents as $enrollment) {
                if ($enrollment->student) {
                    $this->transferStudent(
                        $enrollment->student,
                        $fromClassroom,
                        $toClassroom,
                        $reasonForOld
                    );
                    $promoted++;
                }
            }
        });

        return $promoted;
    }

    /**
     * ซิงค์ข้อมูล class_level + class_section จาก pivot ลง students table
     * ใช้ในกรณี migration หรือ data repair
     */
    public function syncStudentFields(int $academyId): int
    {
        $synced = 0;

        $enrollments = ClassroomStudent::where('academy_id', $academyId)
            ->where('status', ClassroomStudent::STATUS_ACTIVE)
            ->with(['student', 'classroom'])
            ->get();

        foreach ($enrollments as $enrollment) {
            if ($enrollment->student && $enrollment->classroom) {
                $enrollment->student->update([
                    'class_level' => $enrollment->classroom->grade_level,
                    'class_section' => $enrollment->classroom->section,
                ]);
                $synced++;
            }
        }

        return $synced;
    }

    /**
     * Get enrollment history for a student
     */
    public function getStudentHistory(Student $student): \Illuminate\Database\Eloquent\Collection
    {
        return ClassroomStudent::where('student_id', $student->id)
            ->with(['classroom.academicYear', 'classroom.homeroomTeacher'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
