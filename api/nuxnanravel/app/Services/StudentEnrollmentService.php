<?php

namespace App\Services;

use App\Enums\StudentCardRequestStatus;
use App\Events\Enrollment\StudentDropped;
use App\Events\Enrollment\StudentEnrolled;
use App\Events\Enrollment\StudentGraduated;
use App\Events\Enrollment\StudentPromoted;
use App\Events\Enrollment\StudentRemovedFromClassroom;
use App\Events\Enrollment\StudentRepeated;
use App\Events\Enrollment\StudentTransferred;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentAcademicInfo;
use App\Models\StudentCardRequest;
use Illuminate\Database\Eloquent\Collection;
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
     * Helper to normalize grade level for student.class_level
     * "ม.1" -> "1", "1" -> "1", "ป.3" -> "3", "อ.2" -> "2"
     */
    public function normalizeGradeLevel(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return preg_replace('/^[^0-9]+/u', '', trim($value)) ?: null;
    }

    /**
     * Close the active enrollment of a student in a classroom
     */
    private function closeActiveEnrollment(
        Student $student,
        Classroom $classroom,
        string $newStatus,
        ?string $reason = null,
        $at = null,
        ?string $batchId = null,
        ?int $userId = null
    ): ?ClassroomStudent {
        $enrollment = ClassroomStudent::where('classroom_id', $classroom->id)
            ->where('student_id', $student->id)
            ->where('status', ClassroomStudent::STATUS_ACTIVE)
            ->first();

        if (! $enrollment) {
            return null;
        }

        $leftAt = $at ?: today();

        $enrollment->update([
            'status' => $newStatus,
            'left_at' => $leftAt,
            'leave_reason' => $reason,
            'rollover_batch_id' => $batchId,
            'created_by_user_id' => $userId,
        ]);

        return $enrollment;
    }

    /**
     * Manage student academic info snapshot sequentially
     */
    private function manageAcademicInfoSnapshot(
        Student $student,
        Classroom $newActive,
        ?string $batchId = null
    ): StudentAcademicInfo {
        // Step 1: Flip all current academic records to false to avoid unique constraint violations
        StudentAcademicInfo::where('student_id', $student->id)
            ->where('is_current', true)
            ->update(['is_current' => false]);

        // Step 2: Resolve year name
        $academicYear = $newActive->academicYear;
        $yearName = $academicYear ? $academicYear->name : StudentAcademicInfo::getCurrentAcademicYear();

        // Step 3: updateOrCreate for this year
        return StudentAcademicInfo::updateOrCreate(
            [
                'student_id' => $student->id,
                'academic_year' => $yearName,
            ],
            [
                'academy_id' => $newActive->academy_id,
                'classroom_id' => $newActive->id,
                'current_grade' => $newActive->grade_level,
                'current_class' => $newActive->section,
                'classroom_full' => $newActive->display_name,
                'education_level' => StudentAcademicInfo::getEducationLevelFromString($newActive->grade_level),
                'semester' => $newActive->semester ?? StudentAcademicInfo::getCurrentSemester(),
                'study_status' => StudentAcademicInfo::STATUS_STUDYING,
                'is_current' => true,
            ]
        );
    }

    /**
     * ลงทะเบียนนักเรียนเข้าห้องเรียน
     */
    public function enrollStudent(
        Student $student,
        Classroom $classroom,
        ?int $studentNumber = null,
        ?string $batchId = null,
        ?int $userId = null,
        bool $dispatchEvent = true
    ): ClassroomStudent {
        return DB::transaction(function () use ($student, $classroom, $studentNumber, $batchId, $userId, $dispatchEvent) {
            // Auto-assign student number if not provided
            if ($studentNumber === null) {
                // ถ้านักเรียนยัง active อยู่ห้องนี้แล้ว ให้คงเลขที่เดิมไว้ — updateOrCreate ด้านล่างเขียนทับ
                // student_number เสมอ การลงทะเบียนซ้ำจึงเคยดันเลขที่ของเด็กพุ่งไปท้ายห้อง
                // (เฉพาะกรณี active เท่านั้น ถ้าเคยออกไปแล้วกลับเข้ามาใหม่ให้รับเลขที่ใหม่ตามปกติ)
                $studentNumber = ClassroomStudent::where('classroom_id', $classroom->id)
                    ->where('student_id', $student->id)
                    ->where('status', ClassroomStudent::STATUS_ACTIVE)
                    ->value('student_number');
            }

            if ($studentNumber === null) {
                $studentNumber = ClassroomStudent::where('classroom_id', $classroom->id)
                    ->where('status', ClassroomStudent::STATUS_ACTIVE)
                    ->max('student_number') + 1;
            }

            // Create or update enrollment record (E2 same-classroom re-enroll updateOrCreate strategy)
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
                    'enrolled_at' => today(),
                    'left_at' => null,
                    'leave_reason' => null,
                    'rollover_batch_id' => $batchId,
                    'created_by_user_id' => $userId,
                ]
            );

            // Sync class_level & class_section for backward compatibility (with normalization)
            $student->update([
                'class_level' => $this->normalizeGradeLevel($classroom->grade_level),
                'class_section' => $classroom->section,
                'status' => 'active',
            ]);

            // Update StudentAcademicInfo snapshot
            $this->manageAcademicInfoSnapshot($student, $classroom, $batchId);

            if ($dispatchEvent) {
                event(new StudentEnrolled($student, $enrollment, $batchId));
            }

            return $enrollment;
        });
    }

    /**
     * ลงทะเบียนนักเรียนหลายคนเข้าห้องเรียน (Bulk)
     */
    public function bulkEnrollStudents(
        Classroom $classroom,
        array $studentIds,
        ?int $userId = null
    ): array {
        $result = ['enrolled' => 0, 'transferred' => 0, 'promoted' => 0];

        DB::transaction(function () use ($classroom, $studentIds, $userId, &$result) {
            foreach ($studentIds as $studentId) {
                $student = Student::find($studentId);
                if (! $student) {
                    continue;
                }

                // นักเรียนที่ยัง active อยู่ห้องอื่นต้องปิดทะเบียนห้องเดิมก่อน ไม่งั้นจะค้างสองห้องพร้อมกัน
                // (enrollStudent() แตะเฉพาะห้องปลายทาง จึงไม่ปิดของเดิมให้)
                $openElsewhere = ClassroomStudent::with('classroom')
                    ->where('student_id', $student->id)
                    ->where('status', ClassroomStudent::STATUS_ACTIVE)
                    ->where('classroom_id', '!=', $classroom->id)
                    ->orderByDesc('created_at')
                    ->get();

                // เหลือไว้แค่ห้องล่าสุดเป็นต้นทางของการย้าย ส่วนที่ค้างซ้ำ (ข้อมูลเก่าผิดปกติ) ปิดทิ้งตรงๆ
                // ถ้าวนย้ายทีละห้องเข้าห้องเดียวกัน enrollStudent() จะถูกเรียกซ้ำและเลขที่จะเขยิบขึ้นทุกรอบ
                $primary = $openElsewhere->shift();

                foreach ($openElsewhere as $stale) {
                    $stale->update([
                        'status' => ClassroomStudent::STATUS_TRANSFERRED,
                        'left_at' => today(),
                        'leave_reason' => 'ปิดทะเบียนที่ค้างซ้ำอัตโนมัติ',
                    ]);
                }

                $from = $primary?->classroom;

                if (! $from) {
                    $this->enrollStudent($student, $classroom, null, null, $userId);
                    $result['enrolled']++;
                } elseif ((int) $from->academic_year_id === (int) $classroom->academic_year_id) {
                    $this->transferStudent($student, $from, $classroom, 'ย้ายเข้าห้องเรียนโดยผู้ดูแล', null, $userId);
                    $result['transferred']++;
                } else {
                    // คนละปีการศึกษา — transferStudent() จะโยน InvalidArgumentException ต้องใช้ promoteStudent()
                    $this->promoteStudent($student, $from, $classroom, 'ย้ายเข้าห้องเรียนโดยผู้ดูแล', null, null, $userId);
                    $result['promoted']++;
                }
            }
        });

        return $result;
    }

    /**
     * ย้ายนักเรียนจากห้องเดิมไปห้องใหม่ในปีการศึกษาเดียวกัน
     */
    public function transferStudent(
        Student $student,
        Classroom $fromClassroom,
        Classroom $toClassroom,
        string $reason = 'ย้ายห้อง',
        ?string $batchId = null,
        ?int $userId = null
    ): ClassroomStudent {
        return DB::transaction(function () use ($student, $fromClassroom, $toClassroom, $reason, $batchId, $userId) {
            if ($fromClassroom->academic_year_id !== $toClassroom->academic_year_id) {
                throw new \InvalidArgumentException('Use promoteStudent() for cross-year transfers');
            }

            // ปิด enrollment เดิม
            $closed = $this->closeActiveEnrollment(
                $student,
                $fromClassroom,
                ClassroomStudent::STATUS_TRANSFERRED,
                $reason,
                today(),
                $batchId,
                $userId
            );

            // สร้าง enrollment ใหม่
            $opened = $this->enrollStudent($student, $toClassroom, null, $batchId, $userId, false);

            if ((int) $fromClassroom->academy_id !== (int) $toClassroom->academy_id) {
                StudentCardRequest::query()
                    ->where('student_id', $student->id)
                    ->where('academy_id', $fromClassroom->academy_id)
                    ->whereIn('status', [
                        StudentCardRequestStatus::Pending->value,
                        StudentCardRequestStatus::Approved->value,
                        StudentCardRequestStatus::InProgress->value,
                    ])
                    ->update([
                        'status' => StudentCardRequestStatus::Cancelled->value,
                        'cancelled_at' => now(),
                        'admin_notes' => 'ยกเลิกอัตโนมัติเนื่องจากนักเรียนย้ายโรงเรียน',
                    ]);
            }

            if ($closed) {
                event(new StudentTransferred($student, $closed, $opened, $batchId));
            }

            return $opened;
        });
    }

    /**
     * นักเรียนจบการศึกษา
     */
    public function graduateStudent(
        Student $student,
        ?Classroom $classroom = null,
        string $reason = 'จบการศึกษา',
        ?string $batchId = null,
        ?int $userId = null
    ): ?ClassroomStudent {
        return DB::transaction(function () use ($student, $classroom, $reason, $batchId, $userId) {
            if ($classroom === null) {
                $activeEnrollment = ClassroomStudent::where('student_id', $student->id)
                    ->where('status', ClassroomStudent::STATUS_ACTIVE)
                    ->first();
                $classroom = $activeEnrollment ? $activeEnrollment->classroom : null;
            }

            $closed = null;
            if ($classroom) {
                $closed = $this->closeActiveEnrollment(
                    $student,
                    $classroom,
                    ClassroomStudent::STATUS_GRADUATED,
                    $reason,
                    today(),
                    $batchId,
                    $userId
                );
            }

            // Update student record
            $student->update([
                'status' => 'graduated',
                'class_level' => null,
                'class_section' => null,
            ]);

            // Update academic info
            $academicInfo = $student->currentAcademicInfo ?: $student->academicInfos()->latest('academic_year')->first();
            if ($academicInfo) {
                $academicInfo->update([
                    'graduation_date' => today(),
                    'study_status' => StudentAcademicInfo::STATUS_GRADUATED,
                    'is_current' => false,
                ]);
            }

            event(new StudentGraduated($student, $closed, $batchId));

            return $closed;
        });
    }

    /**
     * นักเรียนออกจากห้องเรียน (ลาออก/พ้นสภาพ)
     */
    public function dropStudent(
        Student $student,
        ?Classroom $classroom = null,
        string $reason = 'พ้นสภาพ',
        ?string $batchId = null,
        ?int $userId = null
    ): ?ClassroomStudent {
        return DB::transaction(function () use ($student, $classroom, $reason, $batchId, $userId) {
            if ($classroom === null) {
                $activeEnrollment = ClassroomStudent::where('student_id', $student->id)
                    ->where('status', ClassroomStudent::STATUS_ACTIVE)
                    ->first();
                $classroom = $activeEnrollment ? $activeEnrollment->classroom : null;
            }

            $closed = null;
            if ($classroom) {
                $closed = $this->closeActiveEnrollment(
                    $student,
                    $classroom,
                    ClassroomStudent::STATUS_DROPPED,
                    $reason,
                    today(),
                    $batchId,
                    $userId
                );
            }

            // Update student status to inactive
            $student->update([
                'status' => 'inactive',
                'class_level' => null,
                'class_section' => null,
            ]);

            // Update academic info
            $academicInfo = $student->currentAcademicInfo ?: $student->academicInfos()->latest('academic_year')->first();
            if ($academicInfo) {
                $academicInfo->update([
                    'study_status' => StudentAcademicInfo::STATUS_DROPPED,
                    'is_current' => false,
                ]);
            }

            event(new StudentDropped($student, $closed, $reason, $batchId));

            return $closed;
        });
    }

    /**
     * นำนักเรียนออกจากห้อง โดยยังคงสถานะนักเรียนเป็น active
     *
     * ต่างจาก dropStudent(): นักเรียนไม่พ้นสภาพ แค่ไม่สังกัดห้องใดๆ
     */
    public function removeFromClassroom(
        Student $student,
        Classroom $classroom,
        string $reason = 'นำออกจากห้อง',
        ?string $batchId = null,
        ?int $userId = null
    ): ClassroomStudent {
        return DB::transaction(function () use ($student, $classroom, $reason, $batchId, $userId) {
            $closed = $this->closeActiveEnrollment(
                $student,
                $classroom,
                ClassroomStudent::STATUS_REMOVED,
                $reason,
                today(),
                $batchId,
                $userId
            );

            if (! $closed) {
                throw new \InvalidArgumentException('ไม่พบการลงทะเบียนที่ active ของนักเรียนในห้องนี้');
            }

            // เคลียร์ห้องปัจจุบัน แต่คงสถานะนักเรียนเป็น active
            $student->update([
                'class_level' => null,
                'class_section' => null,
            ]);

            // เคลียร์ห้องใน academic info snapshot ปัจจุบัน (ยังคง studying)
            StudentAcademicInfo::where('student_id', $student->id)
                ->where('is_current', true)
                ->update([
                    'classroom_id' => null,
                    'current_class' => null,
                    'classroom_full' => null,
                ]);

            event(new StudentRemovedFromClassroom($student, $closed, $reason, $batchId));

            return $closed;
        });
    }

    /**
     * เรียนซ้ำชั้นในห้องเรียนใหม่
     */
    public function repeatStudent(
        Student $student,
        Classroom $newClassroom,
        ?int $studentNumber = null,
        string $reason = 'ซ้ำชั้น',
        ?string $batchId = null,
        ?int $userId = null
    ): ClassroomStudent {
        return DB::transaction(function () use ($student, $newClassroom, $studentNumber, $reason, $batchId, $userId) {
            $activeEnrollment = ClassroomStudent::where('student_id', $student->id)
                ->where('status', ClassroomStudent::STATUS_ACTIVE)
                ->first();

            $closed = null;
            if ($activeEnrollment) {
                $fromClassroom = $activeEnrollment->classroom;

                // Assert same grade level
                if ($newClassroom->grade_level !== $fromClassroom->grade_level) {
                    throw new \InvalidArgumentException('repeatStudent() requires the new classroom to have the same grade level');
                }

                // Assert different classroom
                if ($newClassroom->id === $fromClassroom->id) {
                    throw new \InvalidArgumentException('Cannot repeat in the exact same classroom ID to avoid UNIQUE constraint conflicts');
                }

                $closed = $this->closeActiveEnrollment(
                    $student,
                    $fromClassroom,
                    ClassroomStudent::STATUS_REPEATING,
                    $reason,
                    today(),
                    $batchId,
                    $userId
                );
            }

            // Enroll in new classroom
            $opened = $this->enrollStudent($student, $newClassroom, $studentNumber, $batchId, $userId, false);

            if ($closed) {
                event(new StudentRepeated($student, $closed, $opened, $batchId));
            }

            return $opened;
        });
    }

    /**
     * เลื่อนชั้นนักเรียนข้ามปีการศึกษา
     */
    public function promoteStudent(
        Student $student,
        Classroom $fromClassroom,
        Classroom $toClassroom,
        string $reason = 'เลื่อนชั้น',
        ?int $studentNumber = null,
        ?string $batchId = null,
        ?int $userId = null
    ): ClassroomStudent {
        return DB::transaction(function () use ($student, $fromClassroom, $toClassroom, $reason, $studentNumber, $batchId, $userId) {
            // assert cross-year
            if ($fromClassroom->academic_year_id === $toClassroom->academic_year_id) {
                throw new \InvalidArgumentException('Use transferStudent() for same-year classroom transitions');
            }

            $closed = $this->closeActiveEnrollment(
                $student,
                $fromClassroom,
                ClassroomStudent::STATUS_PROMOTED,
                $reason,
                today(),
                $batchId,
                $userId
            );

            $opened = $this->enrollStudent($student, $toClassroom, $studentNumber, $batchId, $userId, false);

            if ($closed) {
                event(new StudentPromoted($student, $closed, $opened, $batchId));
            }

            return $opened;
        });
    }

    /**
     * @deprecated Use dropStudent or transferStudent
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
                ->where('status', ClassroomStudent::STATUS_ACTIVE)
                ->update([
                    'status' => $status,
                    'left_at' => today(),
                    'leave_reason' => $reason,
                ]);

            return $updated > 0;
        });
    }

    /**
     * @deprecated Use AcademicYearRolloverService or call promoteStudent directly in loop
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
                    $this->promoteStudent(
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
                    'class_level' => $this->normalizeGradeLevel($enrollment->classroom->grade_level),
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
    public function getStudentHistory(Student $student): Collection
    {
        return ClassroomStudent::where('student_id', $student->id)
            ->with(['classroom.academicYear', 'classroom.homeroomTeacher'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
