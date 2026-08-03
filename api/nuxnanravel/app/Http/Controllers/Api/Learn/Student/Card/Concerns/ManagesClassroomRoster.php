<?php

namespace App\Http\Controllers\Api\Learn\Student\Card\Concerns;

use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Services\StudentEnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * การจัดการรายชื่อนักเรียนในห้อง (เพิ่ม / ย้าย / นำออก) ที่ใช้ร่วมกัน
 * ระหว่างเส้นทางสาธารณะชั่วคราว (/api/student-card/...) กับเส้นทางของโรงเรียน
 * (/api/academies/{academy}/student-cards/...)
 *
 * ตรรกะการ "ตรวจก่อนเขียน" อยู่ที่นี่ที่เดียว ส่วนการตรวจสิทธิ์เป็นหน้าที่ของ
 * controller แต่ละตัว (ฝั่งสาธารณะใช้ config flag ฝั่งโรงเรียนใช้
 * StudentCardAccessService) — ข้อความและรหัส error ต้องเหมือนกันทั้งสองเส้น
 */
trait ManagesClassroomRoster
{
    abstract protected function enrollmentService(): StudentEnrollmentService;

    protected function activeEnrollmentCount(Classroom $classroom): int
    {
        return $classroom->classroomStudents()->active()->count();
    }

    /**
     * ห้องอื่นในโรงเรียนและปีการศึกษาเดียวกัน สำหรับเป็นตัวเลือกปลายทางตอนย้ายห้อง
     */
    protected function availableClassroomOptions(Classroom $classroom): Collection
    {
        return Classroom::query()
            ->where('academy_id', $classroom->academy_id)
            ->where('academic_year_id', $classroom->academic_year_id)
            ->where('status', 'active')
            ->whereKeyNot($classroom->id)
            ->withCount(['classroomStudents as student_count' => fn ($query) => $query->active()])
            ->orderBy('grade_level')
            ->orderBy('section')
            ->get()
            ->map(fn (Classroom $target) => [
                'id' => $target->id,
                'name' => $target->display_name,
                'grade_level' => $target->grade_level,
                'section' => $target->section,
                'student_count' => $target->student_count,
            ]);
    }

    /**
     * ค้นหานักเรียนในโรงเรียนเดียวกันเพื่อเพิ่มเข้าห้อง
     * ค้นสั้นกว่า 2 ตัวอักษรคืนรายการว่าง ไม่ใช่ทั้งโรงเรียน
     */
    protected function searchEnrollableStudents(Classroom $classroom, string $search): Collection
    {
        if (mb_strlen($search) < 2) {
            return collect();
        }

        return Student::query()
            ->where('academy_id', $classroom->academy_id)
            ->where('status', 'active')
            ->where(function ($query) use ($search) {
                $query->where('first_name_th', 'like', "%{$search}%")
                    ->orWhere('last_name_th', 'like', "%{$search}%")
                    ->orWhere('student_id', 'like', "%{$search}%");
            })
            ->with('currentEnrollment.classroom')
            ->orderBy('first_name_th')
            ->limit(20)
            ->get()
            ->map(function (Student $student) use ($classroom) {
                $enrollment = $student->currentEnrollment;

                return [
                    'id' => $student->id,
                    'student_id_code' => $student->student_id,
                    'full_name' => trim("{$student->title_prefix_th} {$student->first_name_th} {$student->last_name_th}"),
                    'current_classroom' => $enrollment?->classroom?->display_name,
                    'current_classroom_id' => $enrollment?->classroom_id,
                    'enrollment_status' => match (true) {
                        $enrollment === null => 'unassigned',
                        $enrollment->classroom_id === $classroom->id => 'already_in_room',
                        default => 'in_other_room',
                    },
                ];
            });
    }

    /**
     * เพิ่มนักเรียนเข้าห้อง
     *
     * $allowCrossRoomTransfer = true จะ "ดึง" นักเรียนที่ยังสังกัดห้องอื่นเข้ามาให้เลย
     * (ปิดห้องเดิมเป็น transferred แล้วเปิดแถวใหม่ในห้องนี้) ใช้กับหน้าจัดการบัตร
     * ของโรงเรียนที่ครูประจำชั้นยืนยันแล้วเท่านั้น
     *
     * ค่าเริ่มต้นเป็น false เพื่อให้เส้นทางสาธารณะชั่วคราวยังตอบ 422 in_other_room
     * เหมือนเดิมทุกประการ
     */
    protected function enrollStudentIntoRoom(
        Classroom $classroom,
        Student $student,
        ?int $studentNumber,
        bool $allowCrossRoomTransfer = false,
        ?int $actorUserId = null
    ): JsonResponse {
        if ((int) $student->academy_id !== (int) $classroom->academy_id) {
            return $this->rosterError('different_academy', 'นักเรียนไม่ได้อยู่ในโรงเรียนเดียวกับห้องนี้');
        }

        $currentEnrollment = ClassroomStudent::query()
            ->where('student_id', $student->id)
            ->where('status', ClassroomStudent::STATUS_ACTIVE)
            ->with('classroom')
            ->first();

        if ($currentEnrollment) {
            if ((int) $currentEnrollment->classroom_id === (int) $classroom->id) {
                return $this->rosterError('already_in_room', 'นักเรียนอยู่ในห้องนี้แล้ว');
            }

            if (! $allowCrossRoomTransfer) {
                // ส่งห้องปัจจุบันกลับไปด้วย เพื่อให้หน้าจอถามยืนยันการย้ายได้
                // โดยไม่ต้องยิงค้นหาซ้ำ
                return $this->rosterError(
                    'in_other_room',
                    'นักเรียนอยู่ห้อง '.($currentEnrollment->classroom?->display_name ?? 'อื่น').' — กรุณาใช้เมนูย้ายห้องแทน',
                    [
                        'current_classroom_id' => $currentEnrollment->classroom_id,
                        'current_classroom_name' => $currentEnrollment->classroom?->display_name,
                    ]
                );
            }
        }

        if ($error = $this->guardRoomSpace($classroom, $studentNumber)) {
            return $error;
        }

        if ($currentEnrollment) {
            return $this->pullStudentFromOtherRoom($classroom, $student, $currentEnrollment, $studentNumber, $actorUserId);
        }

        $enrollment = $this->enrollmentService()->enrollStudent($student, $classroom, $studentNumber);

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มนักเรียนเข้าห้องเรียบร้อย',
            'enrollment_id' => $enrollment->id,
            'student_count' => $this->activeEnrollmentCount($classroom),
        ], 201);
    }

    /**
     * ห้องนี้ยังรับได้ไหม และเลขที่ที่ขอมาว่างไหม
     */
    private function guardRoomSpace(Classroom $classroom, ?int $studentNumber): ?JsonResponse
    {
        if ($classroom->isFull()) {
            return $this->rosterError('room_full', 'ห้องนี้เต็มแล้ว (ความจุ '.$classroom->capacity.' คน)');
        }

        if ($studentNumber !== null) {
            $numberTaken = $classroom->classroomStudents()
                ->active()
                ->where('student_number', $studentNumber)
                ->exists();

            if ($numberTaken) {
                return $this->rosterError('duplicate_student_number', "เลขที่ {$studentNumber} ถูกใช้แล้วในห้องนี้");
            }
        }

        return null;
    }

    /**
     * ดึงนักเรียนที่สังกัดห้องอื่นอยู่เข้ามาห้องนี้
     *
     * ข้อความตอบกลับต้องบอกให้ชัดว่า "ย้ายมาจากห้องไหน" ไม่ใช่คำว่าเพิ่มเฉย ๆ
     * เพราะผลลัพธ์คือห้องของครูอีกคนมีคนหายไปหนึ่งคน
     */
    private function pullStudentFromOtherRoom(
        Classroom $classroom,
        Student $student,
        ClassroomStudent $currentEnrollment,
        ?int $studentNumber,
        ?int $actorUserId
    ): JsonResponse {
        $fromClassroom = $currentEnrollment->classroom;

        if (! $fromClassroom) {
            return $this->rosterError('in_other_room', 'ไม่พบห้องเดิมของนักเรียน จึงย้ายเข้าห้องนี้ไม่ได้');
        }

        if ((int) $fromClassroom->academic_year_id !== (int) $classroom->academic_year_id) {
            return $this->rosterError(
                'different_academic_year',
                'ห้องเดิมของนักเรียนอยู่คนละปีการศึกษา ต้องใช้การเลื่อนชั้นแทนการย้ายห้อง'
            );
        }

        try {
            $enrollment = $this->enrollmentService()->transferStudent(
                $student,
                $fromClassroom,
                $classroom,
                'ย้ายเข้าห้องจากหน้าจัดการบัตรนักเรียน',
                null,
                $actorUserId
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->rosterError('invalid_transfer', $exception->getMessage());
        }

        // transferStudent() ไม่รับเลขที่ จึงต้องตั้งทีหลัง (ตรวจว่าว่างไปแล้วข้างบน)
        if ($studentNumber !== null) {
            $enrollment->update(['student_number' => $studentNumber]);
        }

        $studentName = trim("{$student->first_name_th} {$student->last_name_th}") ?: 'นักเรียน';

        return response()->json([
            'success' => true,
            'message' => "ย้าย {$studentName} ออกจากห้อง {$fromClassroom->display_name} เข้าห้องนี้เรียบร้อย",
            'moved_from_classroom_id' => $fromClassroom->id,
            'moved_from_classroom_name' => $fromClassroom->display_name,
            'enrollment_id' => $enrollment->id,
            'student_count' => $this->activeEnrollmentCount($classroom),
        ], 201);
    }

    protected function transferStudentBetweenRooms(
        Classroom $fromClassroom,
        Classroom $toClassroom,
        Student $student,
        ?string $reason
    ): JsonResponse {
        if ((int) $toClassroom->id === (int) $fromClassroom->id) {
            return $this->rosterError('same_classroom', 'ห้องปลายทางเป็นห้องเดิม');
        }

        if ((int) $toClassroom->academy_id !== (int) $fromClassroom->academy_id) {
            return $this->rosterError('different_academy', 'ห้องปลายทางอยู่คนละโรงเรียน');
        }

        if ((int) $toClassroom->academic_year_id !== (int) $fromClassroom->academic_year_id) {
            return $this->rosterError('different_academic_year', 'ห้องปลายทางต้องอยู่ในปีการศึกษาเดียวกัน');
        }

        $hasActiveEnrollment = ClassroomStudent::query()
            ->where('student_id', $student->id)
            ->where('classroom_id', $fromClassroom->id)
            ->where('status', ClassroomStudent::STATUS_ACTIVE)
            ->exists();

        if (! $hasActiveEnrollment) {
            return $this->rosterError('not_in_room', 'นักเรียนไม่ได้อยู่ในห้องนี้');
        }

        if ($toClassroom->isFull()) {
            return $this->rosterError('room_full', 'ห้องปลายทางเต็มแล้ว (ความจุ '.$toClassroom->capacity.' คน)');
        }

        try {
            $newEnrollment = $this->enrollmentService()->transferStudent(
                $student,
                $fromClassroom,
                $toClassroom,
                $reason ?: 'ย้ายห้อง'
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->rosterError('invalid_transfer', $exception->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'ย้ายนักเรียนไปห้อง '.$toClassroom->display_name.' เรียบร้อย',
            'enrollment_id' => $newEnrollment->id,
            'student_count' => $this->activeEnrollmentCount($fromClassroom),
        ]);
    }

    protected function removeStudentFromRoom(Classroom $classroom, Student $student, ?string $reason): JsonResponse
    {
        try {
            $this->enrollmentService()->removeFromClassroom(
                $student,
                $classroom,
                $reason ?: 'นำออกจากห้อง'
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->rosterError('not_in_room', $exception->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'นำนักเรียนออกจากห้องเรียบร้อย (นักเรียนยังคงสถานะ active)',
            'student_count' => $this->activeEnrollmentCount($classroom),
        ]);
    }

    protected function rosterError(string $error, string $message, array $extra = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $error,
            'message' => $message,
        ] + $extra, 422);
    }
}
