<?php

namespace App\Http\Controllers\Api\Learn\Student\Card;

use App\Http\Controllers\Api\Learn\Student\Card\Concerns\ResolvesStudentCardRoom;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\StudentCardRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        abort_unless(config('student-card.public_requests'), 403, 'ระบบส่งคำร้องขอทำบัตรนักเรียนสาธารณะถูกปิดใช้งาน');

        $classroom = $this->resolveClassroomFromUrl($level, $room);
        $academy = $classroom->academy;
        $cardRequestFlowEnabled = $academy ? (bool) ($academy->getSettings()?->card_request_flow_enabled) : false;
        abort_unless($cardRequestFlowEnabled, 403, 'ระบบส่งคำร้องขอทำบัตรยังไม่เปิดใช้งานสำหรับโรงเรียนนี้');

        $validated = $request->validate([
            'student_id' => ['required', 'integer'],
            'request_type' => ['required', 'string', 'in:replacement,renewal'],
            'reason' => ['nullable', 'string', 'max:500'],
            'requester_name' => ['nullable', 'string', 'max:255'],
            'requester_phone' => ['nullable', 'string', 'max:50'],
        ]);

        $student = Student::where('academy_id', $classroom->academy_id)->find($validated['student_id']);
        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลนักเรียน',
            ], 422);
        }

        // Check if student's active card is null (legacy card) and prevent it if needed.
        // Actually, the service will check if existing card is active for replacement/renewal.
        // Let's pass the classroom_id to restrict the request to the classroom resolved from URL.
        $data = [
            'request_type' => $validated['request_type'],
            'reason' => $validated['reason'] ?? null,
            'classroom_id' => $classroom->id,
            'priority' => 'normal',
        ];

        // Format a note inside the reason or append requester info.
        $requesterInfo = [];
        $reqName = $validated['requester_name'] ?? null;
        $reqPhone = $validated['requester_phone'] ?? null;
        if (! empty($reqName)) {
            $requesterInfo[] = 'ผู้แจ้ง: '.$reqName;
        }
        if (! empty($reqPhone)) {
            $requesterInfo[] = 'เบอร์ติดต่อ: '.$reqPhone;
        }

        if (! empty($requesterInfo)) {
            $data['reason'] = trim(($data['reason'] ?? '').' ('.implode(', ', $requesterInfo).')');
        }

        try {
            $cardRequest = $this->requestService->createPublic($academy, $student, $data);

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
}
