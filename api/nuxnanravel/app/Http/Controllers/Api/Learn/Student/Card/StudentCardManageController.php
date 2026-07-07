<?php

namespace App\Http\Controllers\Api\Learn\Student\Card;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentCard\AddStudentToRoomRequest;
use App\Http\Requests\StudentCard\RemoveStudentFromRoomRequest;
use App\Http\Requests\StudentCard\TransferStudentFromRoomRequest;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Services\StudentEnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Temporary public classroom management for the student card pages.
 *
 * เปิดใช้เฉพาะเมื่อ config('student-card.public_management') เป็น true
 * URL ไม่มี academy จึง resolve ห้องจาก level + section + ปีการศึกษาปัจจุบัน
 * ถ้าห้องตรงกันมากกว่าหนึ่ง academy จะตอบ 409 ไม่เลือกแบบเดา
 */
class StudentCardManageController extends Controller
{
    public function __construct(
        private readonly StudentEnrollmentService $enrollmentService
    ) {}

    private function ensureManagementEnabled(): void
    {
        abort_unless(config('student-card.public_management'), 403, 'ระบบจัดการชั่วคราวถูกปิดใช้งาน');
    }

    private function resolveClassroomFromUrl(string $level, string $room): Classroom
    {
        $classrooms = Classroom::query()
            ->where('grade_level', 'like', '%'.$level)
            ->where('section', $room)
            ->where('status', 'active')
            ->whereHas('academicYear', fn ($query) => $query->where('is_current', true))
            ->with(['academy', 'academicYear'])
            ->get();

        abort_if($classrooms->isEmpty(), 404, 'ไม่พบห้องเรียนในปีการศึกษาปัจจุบัน');
        abort_if(
            $classrooms->count() > 1,
            409,
            'พบห้องเรียนตรงกันมากกว่าหนึ่งโรงเรียน ไม่สามารถระบุห้องได้'
        );

        return $classrooms->first();
    }

    private function activeEnrollmentCount(Classroom $classroom): int
    {
        return $classroom->classroomStudents()->active()->count();
    }

    /**
     * GET /api/student-card/{level}/{room}/manage-context
     */
    public function context(string $level, string $room): JsonResponse
    {
        if (! config('student-card.public_management')) {
            return response()->json([
                'success' => true,
                'can_manage' => false,
            ]);
        }

        $classroom = $this->resolveClassroomFromUrl($level, $room);

        $availableClassrooms = Classroom::query()
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

        return response()->json([
            'success' => true,
            'can_manage' => true,
            'academy_id' => $classroom->academy_id,
            'academy_name' => $classroom->academy?->name,
            'classroom_id' => $classroom->id,
            'classroom_name' => $classroom->display_name,
            'academic_year_id' => $classroom->academic_year_id,
            'academic_year_name' => $classroom->academicYear?->name,
            'student_count' => $this->activeEnrollmentCount($classroom),
            'capacity' => $classroom->capacity,
            'available_classrooms' => $availableClassrooms,
        ]);
    }

    /**
     * GET /api/student-card/{level}/{room}/available-students?search=
     */
    public function availableStudents(Request $request, string $level, string $room): JsonResponse
    {
        $this->ensureManagementEnabled();
        $classroom = $this->resolveClassroomFromUrl($level, $room);

        $search = trim((string) $request->query('search', ''));
        if (mb_strlen($search) < 2) {
            return response()->json(['success' => true, 'students' => []]);
        }

        $students = Student::query()
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

        return response()->json(['success' => true, 'students' => $students]);
    }

    /**
     * POST /api/student-card/{level}/{room}/students
     */
    public function addStudent(AddStudentToRoomRequest $request, string $level, string $room): JsonResponse
    {
        $classroom = $this->resolveClassroomFromUrl($level, $room);
        $student = Student::findOrFail($request->integer('student_id'));

        if ((int) $student->academy_id !== (int) $classroom->academy_id) {
            return $this->errorResponse('different_academy', 'นักเรียนไม่ได้อยู่ในโรงเรียนเดียวกับห้องนี้');
        }

        $currentEnrollment = ClassroomStudent::query()
            ->where('student_id', $student->id)
            ->where('status', ClassroomStudent::STATUS_ACTIVE)
            ->with('classroom')
            ->first();

        if ($currentEnrollment) {
            if ((int) $currentEnrollment->classroom_id === (int) $classroom->id) {
                return $this->errorResponse('already_in_room', 'นักเรียนอยู่ในห้องนี้แล้ว');
            }

            return $this->errorResponse(
                'in_other_room',
                'นักเรียนอยู่ห้อง '.($currentEnrollment->classroom?->display_name ?? 'อื่น').' — กรุณาใช้เมนูย้ายห้องแทน'
            );
        }

        if ($classroom->isFull()) {
            return $this->errorResponse('room_full', 'ห้องนี้เต็มแล้ว (ความจุ '.$classroom->capacity.' คน)');
        }

        $studentNumber = $request->filled('student_number') ? $request->integer('student_number') : null;
        if ($studentNumber !== null) {
            $numberTaken = $classroom->classroomStudents()
                ->active()
                ->where('student_number', $studentNumber)
                ->exists();

            if ($numberTaken) {
                return $this->errorResponse('duplicate_student_number', "เลขที่ {$studentNumber} ถูกใช้แล้วในห้องนี้");
            }
        }

        $enrollment = $this->enrollmentService->enrollStudent($student, $classroom, $studentNumber);

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มนักเรียนเข้าห้องเรียบร้อย',
            'enrollment_id' => $enrollment->id,
            'student_count' => $this->activeEnrollmentCount($classroom),
        ], 201);
    }

    /**
     * POST /api/student-card/{level}/{room}/students/{student}/transfer
     */
    public function transferStudent(
        TransferStudentFromRoomRequest $request,
        string $level,
        string $room,
        Student $student
    ): JsonResponse {
        $fromClassroom = $this->resolveClassroomFromUrl($level, $room);
        $toClassroom = Classroom::findOrFail($request->integer('to_classroom_id'));

        if ((int) $toClassroom->id === (int) $fromClassroom->id) {
            return $this->errorResponse('same_classroom', 'ห้องปลายทางเป็นห้องเดิม');
        }

        if ((int) $toClassroom->academy_id !== (int) $fromClassroom->academy_id) {
            return $this->errorResponse('different_academy', 'ห้องปลายทางอยู่คนละโรงเรียน');
        }

        if ((int) $toClassroom->academic_year_id !== (int) $fromClassroom->academic_year_id) {
            return $this->errorResponse('different_academic_year', 'ห้องปลายทางต้องอยู่ในปีการศึกษาเดียวกัน');
        }

        $hasActiveEnrollment = ClassroomStudent::query()
            ->where('student_id', $student->id)
            ->where('classroom_id', $fromClassroom->id)
            ->where('status', ClassroomStudent::STATUS_ACTIVE)
            ->exists();

        if (! $hasActiveEnrollment) {
            return $this->errorResponse('not_in_room', 'นักเรียนไม่ได้อยู่ในห้องนี้');
        }

        if ($toClassroom->isFull()) {
            return $this->errorResponse('room_full', 'ห้องปลายทางเต็มแล้ว (ความจุ '.$toClassroom->capacity.' คน)');
        }

        try {
            $newEnrollment = $this->enrollmentService->transferStudent(
                $student,
                $fromClassroom,
                $toClassroom,
                $request->input('reason') ?: 'ย้ายห้อง'
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->errorResponse('invalid_transfer', $exception->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'ย้ายนักเรียนไปห้อง '.$toClassroom->display_name.' เรียบร้อย',
            'enrollment_id' => $newEnrollment->id,
            'student_count' => $this->activeEnrollmentCount($fromClassroom),
        ]);
    }

    /**
     * DELETE /api/student-card/{level}/{room}/students/{student}
     */
    public function removeStudent(
        RemoveStudentFromRoomRequest $request,
        string $level,
        string $room,
        Student $student
    ): JsonResponse {
        $classroom = $this->resolveClassroomFromUrl($level, $room);

        try {
            $this->enrollmentService->removeFromClassroom(
                $student,
                $classroom,
                $request->input('reason') ?: 'นำออกจากห้อง'
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->errorResponse('not_in_room', $exception->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'นำนักเรียนออกจากห้องเรียบร้อย (นักเรียนยังคงสถานะ active)',
            'student_count' => $this->activeEnrollmentCount($classroom),
        ]);
    }

    private function errorResponse(string $error, string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $error,
            'message' => $message,
        ], 422);
    }
}
