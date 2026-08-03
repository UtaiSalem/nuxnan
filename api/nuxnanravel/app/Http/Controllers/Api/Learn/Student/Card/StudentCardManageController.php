<?php

namespace App\Http\Controllers\Api\Learn\Student\Card;

use App\Http\Controllers\Api\Learn\Student\Card\Concerns\ManagesClassroomRoster;
use App\Http\Controllers\Api\Learn\Student\Card\Concerns\ResolvesStudentCardRoom;
use App\Http\Controllers\Controller;
use App\Http\Requests\StudentCard\AddStudentToRoomRequest;
use App\Http\Requests\StudentCard\RemoveStudentFromRoomRequest;
use App\Http\Requests\StudentCard\TransferStudentFromRoomRequest;
use App\Models\Classroom;
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
 *
 * เส้นทางของโรงเรียนที่ตรวจสิทธิ์จริงอยู่ที่ AcademyStudentCardManageController
 * ทั้งสองตัวใช้ ManagesClassroomRoster ร่วมกัน
 */
class StudentCardManageController extends Controller
{
    use ManagesClassroomRoster;
    use ResolvesStudentCardRoom;

    public function __construct(
        private readonly StudentEnrollmentService $enrollmentService
    ) {}

    protected function enrollmentService(): StudentEnrollmentService
    {
        return $this->enrollmentService;
    }

    private function ensureManagementEnabled(): void
    {
        abort_unless(config('student-card.public_management'), 403, 'ระบบจัดการชั่วคราวถูกปิดใช้งาน');
    }

    /**
     * GET /api/student-card/{level}/{room}/manage-context
     */
    public function context(string $level, string $room): JsonResponse
    {
        $classroom = $this->resolveClassroomFromUrl($level, $room);
        $academy = $classroom->academy;
        $cardRequestFlowEnabled = $academy ? (bool) ($academy->getSettings()?->card_request_flow_enabled) : false;

        $canManage = (bool) config('student-card.public_management');
        $canRequest = (bool) config('student-card.public_requests') && $cardRequestFlowEnabled;

        if (! $canManage) {
            return response()->json([
                'success' => true,
                'can_manage' => false,
                'can_request' => $canRequest,
                'academy_id' => $classroom->academy_id,
                'academy_name' => $classroom->academy?->name,
                'classroom_id' => $classroom->id,
                'classroom_name' => $classroom->display_name,
                'academic_year_id' => $classroom->academic_year_id,
                'academic_year_name' => $classroom->academicYear?->name,
                'homeroom_teacher_name' => $classroom->homeroomTeacher?->name,
            ]);
        }

        return response()->json([
            'success' => true,
            'can_manage' => true,
            'can_request' => $canRequest,
            'academy_id' => $classroom->academy_id,
            'academy_name' => $classroom->academy?->name,
            'classroom_id' => $classroom->id,
            'classroom_name' => $classroom->display_name,
            'academic_year_id' => $classroom->academic_year_id,
            'academic_year_name' => $classroom->academicYear?->name,
            'homeroom_teacher_name' => $classroom->homeroomTeacher?->name,
            'student_count' => $this->activeEnrollmentCount($classroom),
            'capacity' => $classroom->capacity,
            'available_classrooms' => $this->availableClassroomOptions($classroom),
        ]);
    }

    /**
     * GET /api/student-card/{level}/{room}/available-students?search=
     */
    public function availableStudents(Request $request, string $level, string $room): JsonResponse
    {
        $this->ensureManagementEnabled();
        $classroom = $this->resolveClassroomFromUrl($level, $room);

        return response()->json([
            'success' => true,
            'students' => $this->searchEnrollableStudents($classroom, trim((string) $request->query('search', ''))),
        ]);
    }

    /**
     * POST /api/student-card/{level}/{room}/students
     */
    public function addStudent(AddStudentToRoomRequest $request, string $level, string $room): JsonResponse
    {
        $classroom = $this->resolveClassroomFromUrl($level, $room);
        $student = Student::findOrFail($request->integer('student_id'));

        return $this->enrollStudentIntoRoom(
            $classroom,
            $student,
            $request->filled('student_number') ? $request->integer('student_number') : null
        );
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

        return $this->transferStudentBetweenRooms($fromClassroom, $toClassroom, $student, $request->input('reason'));
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

        return $this->removeStudentFromRoom($classroom, $student, $request->input('reason'));
    }
}
