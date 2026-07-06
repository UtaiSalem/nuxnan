<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\ClassroomInvitation;
use App\Models\ClassroomMember;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * InvitationService — Create, accept/decline invitations and join-via-code.
 *
 * Business Rules enforced:
 * - BR-5: Capacity check before accepting student invitation.
 * - BR-6: Invitation expiry validation (410 Gone).
 */
class InvitationService
{
    private MemberService $memberService;

    public function __construct(MemberService $memberService)
    {
        $this->memberService = $memberService;
    }

    /**
     * List invitations for a classroom.
     */
    public function listInvitations(int $classroomId, ?string $status = null)
    {
        $query = ClassroomInvitation::where('classroom_id', $classroomId)
            ->with(['invitedUser', 'inviter'])
            ->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    /**
     * Create a new invitation.
     *
     * @param  array  $data  Keys: invited_email or invited_user_id, role_to_assign, expires_in_days
     */
    public function createInvitation(int $classroomId, array $data): ClassroomInvitation
    {
        $expiresInDays = $data['expires_in_days'] ?? 7;

        return ClassroomInvitation::create([
            'classroom_id' => $classroomId,
            'token' => (string) Str::uuid(),
            'invited_email' => $data['invited_email'] ?? null,
            'invited_user_id' => $data['invited_user_id'] ?? null,
            'role_to_assign' => $data['role_to_assign'] ?? 'student',
            'status' => ClassroomInvitation::STATUS_PENDING,
            'invited_by' => Auth::id(),
            'expires_at' => now()->addDays($expiresInDays),
        ]);
    }

    /**
     * Accept an invitation by token.
     *
     * @param  int  $userId  The authenticated user accepting
     *
     * @throws HttpException 410 if expired, 422 if capacity
     */
    public function acceptInvitation(string $token, int $userId): ClassroomMember
    {
        return DB::transaction(function () use ($token, $userId) {
            $invitation = ClassroomInvitation::where('token', $token)
                ->where('status', ClassroomInvitation::STATUS_PENDING)
                ->firstOrFail();

            // BR-6: expiry check
            if ($invitation->isExpired()) {
                $invitation->update(['status' => ClassroomInvitation::STATUS_EXPIRED]);
                abort(410, 'คำเชิญหมดอายุแล้ว');
            }

            $classroom = Classroom::findOrFail($invitation->classroom_id);

            // Add as member (BR-5 capacity check inside MemberService)
            $member = $this->memberService->addMember(
                $classroom,
                $userId,
                $invitation->role_to_assign,
                null,
                ClassroomMember::JOIN_INVITATION
            );

            $invitation->markAccepted();

            return $member;
        });
    }

    /**
     * Decline an invitation.
     */
    public function declineInvitation(string $token): ClassroomInvitation
    {
        $invitation = ClassroomInvitation::where('token', $token)
            ->where('status', ClassroomInvitation::STATUS_PENDING)
            ->firstOrFail();

        $invitation->markDeclined();

        return $invitation;
    }

    /**
     * Cancel an invitation (by the inviter or admin).
     */
    public function cancelInvitation(int $invitationId): ClassroomInvitation
    {
        $invitation = ClassroomInvitation::findOrFail($invitationId);
        $invitation->update(['status' => ClassroomInvitation::STATUS_CANCELLED]);

        return $invitation;
    }

    /**
     * Join a classroom via classroom_code.
     *
     * @param  string  $classroomCode  6-char code
     */
    public function joinViaCode(string $classroomCode, int $userId): ClassroomMember
    {
        $classroom = Classroom::where('classroom_code', $classroomCode)
            ->where('status', 'active')
            ->firstOrFail();

        return $this->memberService->addMember(
            $classroom,
            $userId,
            ClassroomMember::ROLE_STUDENT,
            null,
            ClassroomMember::JOIN_CODE
        );
    }
}
