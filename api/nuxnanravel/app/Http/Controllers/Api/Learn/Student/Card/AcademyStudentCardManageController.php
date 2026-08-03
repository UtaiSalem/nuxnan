<?php

namespace App\Http\Controllers\Api\Learn\Student\Card;

use App\Http\Controllers\Api\Learn\Student\Card\Concerns\ManagesClassroomRoster;
use App\Http\Controllers\Controller;
use App\Http\Requests\StudentCard\Academy\AddStudentToAcademyRoomRequest;
use App\Http\Requests\StudentCard\Academy\RemoveStudentFromAcademyRoomRequest;
use App\Http\Requests\StudentCard\Academy\TransferStudentFromAcademyRoomRequest;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\Student;
use App\Services\StudentCardAccessService;
use App\Services\StudentEnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * จัดการรายชื่อนักเรียนในห้อง จากหน้าบัตรนักเรียนของโรงเรียน
 * Prefix: /api/academies/{academy}/student-cards/{level}/{room}
 *
 * ต่างจาก StudentCardManageController (เส้นทางสาธารณะชั่วคราว) ตรงที่ห้องถูก
 * จำกัดอยู่ในโรงเรียนของ URL และทุกการเขียนต้องผ่าน StudentCardAccessService
 * ซึ่งอนุญาตผู้จัดการระดับโรงเรียน หรือครูประจำชั้นของห้องนั้นเท่านั้น
 */
class AcademyStudentCardManageController extends Controller
{
    use ManagesClassroomRoster;

    public function __construct(
        private readonly StudentEnrollmentService $enrollmentService,
        private readonly StudentCardAccessService $access
    ) {}

    protected function enrollmentService(): StudentEnrollmentService
    {
        return $this->enrollmentService;
    }

    /**
     * หาห้องจาก level/room ภายในโรงเรียนของ URL และปีการศึกษาปัจจุบัน
     *
     * ตอบ 409 เมื่อเจอมากกว่าหนึ่งห้อง แทนที่จะเดา — เป็นสัญญาณว่าข้อมูล
     * ห้องเรียนซ้ำกันในปีเดียวกัน ซึ่งต้องให้คนแก้ ไม่ใช่ให้โค้ดเลือกเอง
     */
    private function resolveClassroom(Academy $academy, string $level, string $room): Classroom
    {
        $classrooms = Classroom::query()
            ->where('academy_id', $academy->id)
            ->where('grade_level', 'like', '%'.$level)
            ->where('section', $room)
            ->where('status', Classroom::STATUS_ACTIVE)
            ->whereHas('academicYear', fn ($query) => $query->where('is_current', true))
            ->with(['academicYear', 'homeroomTeacher:id,name'])
            ->get();

        abort_if($classrooms->isEmpty(), 404, 'ไม่พบห้องเรียนในปีการศึกษาปัจจุบัน');
        abort_if($classrooms->count() > 1, 409, 'พบห้องเรียนซ้ำกันในปีการศึกษาปัจจุบัน ไม่สามารถระบุห้องได้');

        return $classrooms->first();
    }

    /**
     * GET /{level}/{room}/context
     *
     * บอกหน้าจอว่าปุ่มไหนควรแสดง โดยไม่ต้องให้ frontend เดาเอาจาก role
     */
    public function context(Request $request, Academy $academy, string $level, string $room): JsonResponse
    {
        $classroom = $this->resolveClassroom($academy, $level, $room);
        $user = $request->user();

        $canManageRoster = $this->access->canManageClassroom($academy, $classroom, $user);
        $requestFlowEnabled = (bool) ($academy->getSettings()?->card_request_flow_enabled);

        return response()->json([
            'success' => true,
            'can_manage_roster' => $canManageRoster,
            'can_edit_card' => $canManageRoster,
            'can_request' => $requestFlowEnabled && $canManageRoster,
            'is_homeroom_teacher' => $classroom->homeroom_teacher_id !== null
                && (int) $classroom->homeroom_teacher_id === (int) $user?->id,
            'academy_id' => $academy->id,
            'academy_name' => $academy->name,
            'classroom_id' => $classroom->id,
            'classroom_name' => $classroom->display_name,
            'academic_year_id' => $classroom->academic_year_id,
            'academic_year_name' => $classroom->academicYear?->name,
            'homeroom_teacher_name' => $classroom->homeroomTeacher?->name,
            'student_count' => $this->activeEnrollmentCount($classroom),
            'capacity' => $classroom->capacity,
            'available_classrooms' => $canManageRoster
                ? $this->availableClassroomOptions($classroom)
                : collect(),
        ]);
    }

    /**
     * GET /{level}/{room}/available-students?search=
     */
    public function availableStudents(Request $request, Academy $academy, string $level, string $room): JsonResponse
    {
        $classroom = $this->resolveClassroom($academy, $level, $room);
        $this->access->ensureCanManageClassroom($academy, $classroom, $request->user());

        return response()->json([
            'success' => true,
            'students' => $this->searchEnrollableStudents($classroom, trim((string) $request->query('search', ''))),
        ]);
    }

    /**
     * POST /{level}/{room}/students
     *
     * นักเรียนที่ยังสังกัดห้องอื่นอยู่ต้องส่ง confirm_transfer=true มาด้วยจึงจะดึงเข้าได้
     * ครั้งแรกที่ยังไม่ยืนยันจะตอบ 422 in_other_room พร้อมชื่อห้องปัจจุบัน เพื่อให้
     * หน้าจอถามครูก่อนว่าจะย้ายออกจากห้องของครูอีกคนจริงไหม
     */
    public function addStudent(
        AddStudentToAcademyRoomRequest $request,
        Academy $academy,
        string $level,
        string $room
    ): JsonResponse {
        $classroom = $this->resolveClassroom($academy, $level, $room);
        $this->access->ensureCanManageClassroom($academy, $classroom, $request->user());

        $student = Student::findOrFail($request->integer('student_id'));

        return $this->enrollStudentIntoRoom(
            $classroom,
            $student,
            $request->filled('student_number') ? $request->integer('student_number') : null,
            $request->boolean('confirm_transfer'),
            $request->user()?->id
        );
    }

    /**
     * POST /{level}/{room}/students/{student}/transfer
     *
     * ตรวจสิทธิ์ที่ "ห้องต้นทาง" อย่างเดียว — ครูประจำชั้นย้ายนักเรียนของห้องตัวเอง
     * ออกไปห้องอื่นได้เหมือนที่ทำได้บนหน้าชั่วคราว ส่วนห้องปลายทางถูกจำกัดด้วย
     * ตัวตรวจใน transferStudentBetweenRooms() ว่าต้องอยู่โรงเรียนและปีเดียวกัน
     * (ถ้าภายหลังต้องการให้ย้ายได้เฉพาะระหว่างห้องที่ตัวเองดูแล ให้เพิ่ม
     * ensureCanManageClassroom กับ $toClassroom อีกจุด)
     */
    public function transferStudent(
        TransferStudentFromAcademyRoomRequest $request,
        Academy $academy,
        string $level,
        string $room,
        Student $student
    ): JsonResponse {
        $fromClassroom = $this->resolveClassroom($academy, $level, $room);
        $this->access->ensureCanManageClassroom($academy, $fromClassroom, $request->user());

        $toClassroom = Classroom::query()
            ->where('academy_id', $academy->id)
            ->findOrFail($request->integer('to_classroom_id'));

        return $this->transferStudentBetweenRooms($fromClassroom, $toClassroom, $student, $request->input('reason'));
    }

    /**
     * DELETE /{level}/{room}/students/{student}
     */
    public function removeStudent(
        RemoveStudentFromAcademyRoomRequest $request,
        Academy $academy,
        string $level,
        string $room,
        Student $student
    ): JsonResponse {
        $classroom = $this->resolveClassroom($academy, $level, $room);
        $this->access->ensureCanManageClassroom($academy, $classroom, $request->user());

        return $this->removeStudentFromRoom($classroom, $student, $request->input('reason'));
    }
}
