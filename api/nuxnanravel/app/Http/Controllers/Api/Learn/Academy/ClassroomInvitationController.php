<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Services\InvitationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClassroomInvitationController extends Controller
{
    private InvitationService $invitationService;

    public function __construct(InvitationService $invitationService)
    {
        $this->invitationService = $invitationService;
    }

    /**
     * List invitations for a classroom
     */
    public function index(Request $request, int $academyId, int $classroomId): JsonResponse
    {
        $status = $request->query('status');
        $invitations = $this->invitationService->listInvitations($classroomId, $status);

        return response()->json([
            'success' => true,
            'data' => $invitations,
        ]);
    }

    /**
     * Create a new invitation
     */
    public function store(Request $request, int $academyId, int $classroomId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);
        if (!$this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $validated = $request->validate([
            'invited_email' => 'nullable|email|max:255',
            'invited_user_id' => 'nullable|exists:users,id',
            'role_to_assign' => 'in:teacher,co_teacher,student,observer',
            'expires_in_days' => 'nullable|integer|min:1|max:30',
        ]);

        if (empty($validated['invited_email']) && empty($validated['invited_user_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'ต้องระบุ email หรือ user_id อย่างน้อยหนึ่งอย่าง',
            ], 422);
        }

        $invitation = $this->invitationService->createInvitation($classroomId, $validated);

        return response()->json([
            'success' => true,
            'message' => 'สร้างคำเชิญสำเร็จ',
            'data' => $invitation,
        ], 201);
    }

    /**
     * Accept an invitation via token
     */
    public function accept(Request $request, string $token): JsonResponse
    {
        $userId = auth()->id();
        $member = $this->invitationService->acceptInvitation($token, $userId);

        return response()->json([
            'success' => true,
            'message' => 'เข้าร่วมห้องเรียนสำเร็จ',
            'data' => $member->load('classroom'),
        ]);
    }

    /**
     * Decline an invitation via token
     */
    public function decline(string $token): JsonResponse
    {
        $this->invitationService->declineInvitation($token);

        return response()->json([
            'success' => true,
            'message' => 'ปฏิเสธคำเชิญสำเร็จ',
        ]);
    }

    /**
     * Cancel an invitation (admin)
     */
    public function cancel(int $academyId, int $classroomId, int $invitationId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);
        if (!$this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $this->invitationService->cancelInvitation($invitationId);

        return response()->json([
            'success' => true,
            'message' => 'ยกเลิกคำเชิญสำเร็จ',
        ]);
    }

    /**
     * Join via classroom code
     */
    public function joinViaCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'classroom_code' => 'required|string|size:6',
        ]);

        $member = $this->invitationService->joinViaCode(
            $validated['classroom_code'],
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'เข้าร่วมห้องเรียนสำเร็จ',
            'data' => $member->load('classroom'),
        ]);
    }

    // ───── Auth Helper ─────

    protected function canManage(Academy $academy): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        if ($academy->user_id === $user->id) {
            return true;
        }

        return $academy->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'director', 'admin'])
            ->exists();
    }
}
