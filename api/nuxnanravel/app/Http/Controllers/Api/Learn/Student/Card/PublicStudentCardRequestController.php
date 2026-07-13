<?php

namespace App\Http\Controllers\Api\Learn\Student\Card;

use App\Enums\StudentCardRequestReason;
use App\Http\Controllers\Api\Learn\Student\Card\Concerns\ResolvesStudentCardRoom;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Student;
use App\Services\StudentCardRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PublicStudentCardRequestController extends Controller
{
    use ResolvesStudentCardRoom;

    public function __construct(
        private readonly StudentCardRequestService $requestService
    ) {}

    /**
     * POST /api/student-card/{level}/{room}/requests
     */
    public function submitRequest(Request $request, string $level, string $room): JsonResponse
    {
        $classroom = $this->resolveClassroomForRequests($level, $room);

        $validated = $request->validate([
            'student_id' => ['required', 'integer'],
            'request_type' => ['nullable', 'string', 'in:first_issue,replacement,renewal'],
            'reason_code' => ['required', 'string', Rule::enum(StudentCardRequestReason::class)],
            'reason' => ['nullable', 'string', 'max:500', 'required_if:reason_code,other'],
            'requester_name' => ['nullable', 'string', 'max:255'],
            'requester_phone' => ['nullable', 'string', 'max:50'],
        ], [
            'reason.required_if' => 'กรุณาระบุรายละเอียดเมื่อเลือกเหตุผล "อื่นๆ"',
        ]);

        $student = Student::where('academy_id', $classroom->academy_id)->find($validated['student_id']);
        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลนักเรียน',
            ], 422);
        }

        try {
            $cardRequest = $this->requestService->createPublic(
                $classroom->academy,
                $student,
                $this->buildRequestData($classroom, $validated)
            );

            return response()->json([
                'success' => true,
                'message' => 'ส่งคำร้องขอทำบัตรนักเรียนสำเร็จ',
                'request_id' => $cardRequest->id,
                'status' => $cardRequest->status,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * POST /api/student-card/{level}/{room}/requests/bulk
     *
     * ส่งคำร้องหลายคนในคราวเดียว — เหตุผล/ผู้แจ้งชุดเดียวใช้ร่วมกันทุกคน
     * ตอบผลรายคน คนที่ส่งไม่ได้ (เช่น มีคำร้องค้างอยู่) จะไม่ทำให้ทั้งชุดล้ม
     */
    public function submitBulkRequests(Request $request, string $level, string $room): JsonResponse
    {
        $classroom = $this->resolveClassroomForRequests($level, $room);

        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1', 'max:60'],
            'student_ids.*' => ['required', 'integer', 'distinct'],
            'reason_code' => ['required', 'string', Rule::enum(StudentCardRequestReason::class)],
            'reason' => ['nullable', 'string', 'max:500', 'required_if:reason_code,other'],
            'requester_name' => ['nullable', 'string', 'max:255'],
            'requester_phone' => ['nullable', 'string', 'max:50'],
        ], [
            'reason.required_if' => 'กรุณาระบุรายละเอียดเมื่อเลือกเหตุผล "อื่นๆ"',
        ]);

        $students = Student::where('academy_id', $classroom->academy_id)
            ->whereIn('id', $validated['student_ids'])
            ->get()
            ->keyBy('id');

        $data = $this->buildRequestData($classroom, $validated);
        $results = [];
        foreach ($validated['student_ids'] as $studentId) {
            $student = $students->get($studentId);
            if (! $student) {
                $results[] = ['student_id' => $studentId, 'success' => false, 'message' => 'ไม่พบข้อมูลนักเรียน'];

                continue;
            }

            try {
                $cardRequest = $this->requestService->createPublic($classroom->academy, $student, $data);
                $results[] = ['student_id' => $studentId, 'success' => true, 'request_id' => $cardRequest->id];
            } catch (ValidationException $e) {
                $results[] = ['student_id' => $studentId, 'success' => false, 'message' => $e->getMessage()];
            }
        }

        $succeeded = collect($results)->where('success', true)->count();

        return response()->json([
            'success' => $succeeded > 0,
            'message' => "ส่งคำร้องสำเร็จ {$succeeded} จาก ".count($results).' คน',
            'results' => $results,
        ], $succeeded > 0 ? 201 : 422);
    }

    private function resolveClassroomForRequests(string $level, string $room): Classroom
    {
        abort_unless(config('student-card.public_requests'), 403, 'ระบบส่งคำร้องขอทำบัตรนักเรียนสาธารณะถูกปิดใช้งาน');

        $classroom = $this->resolveClassroomFromUrl($level, $room);
        $academy = $classroom->academy;
        $cardRequestFlowEnabled = $academy ? (bool) ($academy->getSettings()?->card_request_flow_enabled) : false;
        abort_unless($cardRequestFlowEnabled, 403, 'ระบบส่งคำร้องขอทำบัตรยังไม่เปิดใช้งานสำหรับโรงเรียนนี้');

        return $classroom;
    }

    /**
     * ประกอบข้อมูลคำร้อง — ไม่ส่งชื่อผู้แจ้งมา ให้ default เป็นครูประจำชั้นของห้อง
     * ไม่ส่ง request_type มา service จะคำนวณจากเหตุผล + สถานะบัตรจริง
     */
    private function buildRequestData(Classroom $classroom, array $validated): array
    {
        return [
            'request_type' => $validated['request_type'] ?? null,
            'reason_code' => $validated['reason_code'],
            'reason' => $validated['reason'] ?? null,
            'requester_name' => ($validated['requester_name'] ?? null) ?: $classroom->homeroomTeacher?->name,
            'requester_phone' => $validated['requester_phone'] ?? null,
            'classroom_id' => $classroom->id,
            'priority' => 'normal',
        ];
    }
}
