<?php

namespace App\Services;

use App\Models\MemberActivityLog;

/**
 * MemberActivityService - บริการสำหรับบันทึกกิจกรรมของสมาชิก
 */
class MemberActivityService
{
    /**
     * Log member join request
     */
    public static function logJoinRequest(int $academyId, int $userId, int $memberId): void
    {
        MemberActivityLog::logActivity([
            'academy_id' => $academyId,
            'user_id' => $userId,
            'target_user_id' => $userId,
            'academy_member_id' => $memberId,
            'action' => MemberActivityLog::ACTION_JOIN,
            'action_category' => MemberActivityLog::CATEGORY_MEMBER,
        ]);
    }

    /**
     * Log member approval
     */
    public static function logApproval(int $academyId, int $approvedBy, int $targetUserId, int $memberId): void
    {
        MemberActivityLog::logActivity([
            'academy_id' => $academyId,
            'user_id' => $approvedBy,
            'target_user_id' => $targetUserId,
            'academy_member_id' => $memberId,
            'action' => MemberActivityLog::ACTION_APPROVE,
            'action_category' => MemberActivityLog::CATEGORY_MEMBER,
        ]);
    }

    /**
     * Log member rejection
     */
    public static function logRejection(int $academyId, int $rejectedBy, int $targetUserId, int $memberId, ?string $reason = null): void
    {
        MemberActivityLog::logActivity([
            'academy_id' => $academyId,
            'user_id' => $rejectedBy,
            'target_user_id' => $targetUserId,
            'academy_member_id' => $memberId,
            'action' => MemberActivityLog::ACTION_REJECT,
            'action_category' => MemberActivityLog::CATEGORY_MEMBER,
            'new_values' => $reason ? ['reason' => $reason] : null,
        ]);
    }

    /**
     * Log member suspension
     */
    public static function logSuspension(int $academyId, int $suspendedBy, int $targetUserId, int $memberId, ?string $reason = null): void
    {
        MemberActivityLog::logActivity([
            'academy_id' => $academyId,
            'user_id' => $suspendedBy,
            'target_user_id' => $targetUserId,
            'academy_member_id' => $memberId,
            'action' => MemberActivityLog::ACTION_SUSPEND,
            'action_category' => MemberActivityLog::CATEGORY_MEMBER,
            'new_values' => $reason ? ['reason' => $reason] : null,
        ]);
    }

    /**
     * Log member unsuspension
     */
    public static function logUnsuspension(int $academyId, int $unsuspendedBy, int $targetUserId, int $memberId): void
    {
        MemberActivityLog::logActivity([
            'academy_id' => $academyId,
            'user_id' => $unsuspendedBy,
            'target_user_id' => $targetUserId,
            'academy_member_id' => $memberId,
            'action' => MemberActivityLog::ACTION_UNSUSPEND,
            'action_category' => MemberActivityLog::CATEGORY_MEMBER,
        ]);
    }

    /**
     * Log member removal
     */
    public static function logRemoval(int $academyId, int $removedBy, int $targetUserId, int $memberId): void
    {
        MemberActivityLog::logActivity([
            'academy_id' => $academyId,
            'user_id' => $removedBy,
            'target_user_id' => $targetUserId,
            'academy_member_id' => $memberId,
            'action' => MemberActivityLog::ACTION_REMOVE,
            'action_category' => MemberActivityLog::CATEGORY_MEMBER,
        ]);
    }

    /**
     * Log member leave
     */
    public static function logLeave(int $academyId, int $userId, int $memberId): void
    {
        MemberActivityLog::logActivity([
            'academy_id' => $academyId,
            'user_id' => $userId,
            'target_user_id' => $userId,
            'academy_member_id' => $memberId,
            'action' => MemberActivityLog::ACTION_LEAVE,
            'action_category' => MemberActivityLog::CATEGORY_MEMBER,
        ]);
    }

    /**
     * Log role change
     */
    public static function logRoleChange(int $academyId, int $changedBy, int $targetUserId, int $memberId, ?string $oldRole, ?string $newRole): void
    {
        MemberActivityLog::logActivity([
            'academy_id' => $academyId,
            'user_id' => $changedBy,
            'target_user_id' => $targetUserId,
            'academy_member_id' => $memberId,
            'action' => MemberActivityLog::ACTION_ROLE_CHANGE,
            'action_category' => MemberActivityLog::CATEGORY_ROLE,
            'old_values' => ['role' => $oldRole],
            'new_values' => ['role' => $newRole],
            'description' => "เปลี่ยนบทบาทจาก {$oldRole} เป็น {$newRole}",
        ]);
    }

    /**
     * Log invitation sent
     */
    public static function logInvitation(int $academyId, int $invitedBy, int $targetUserId, int $memberId): void
    {
        MemberActivityLog::logActivity([
            'academy_id' => $academyId,
            'user_id' => $invitedBy,
            'target_user_id' => $targetUserId,
            'academy_member_id' => $memberId,
            'action' => MemberActivityLog::ACTION_INVITE,
            'action_category' => MemberActivityLog::CATEGORY_MEMBER,
        ]);
    }

    /**
     * Log invitation accepted
     */
    public static function logInviteAccepted(int $academyId, int $userId, int $memberId): void
    {
        MemberActivityLog::logActivity([
            'academy_id' => $academyId,
            'user_id' => $userId,
            'target_user_id' => $userId,
            'academy_member_id' => $memberId,
            'action' => MemberActivityLog::ACTION_ACCEPT_INVITE,
            'action_category' => MemberActivityLog::CATEGORY_MEMBER,
        ]);
    }

    /**
     * Log invitation declined
     */
    public static function logInviteDeclined(int $academyId, int $userId, int $memberId): void
    {
        MemberActivityLog::logActivity([
            'academy_id' => $academyId,
            'user_id' => $userId,
            'target_user_id' => $userId,
            'academy_member_id' => $memberId,
            'action' => MemberActivityLog::ACTION_DECLINE_INVITE,
            'action_category' => MemberActivityLog::CATEGORY_MEMBER,
        ]);
    }

    /**
     * Log bulk action
     */
    public static function logBulkAction(int $academyId, int $performedBy, string $action, array $targetUserIds, int $count): void
    {
        MemberActivityLog::logActivity([
            'academy_id' => $academyId,
            'user_id' => $performedBy,
            'action' => MemberActivityLog::ACTION_BULK_ACTION,
            'action_category' => MemberActivityLog::CATEGORY_MEMBER,
            'new_values' => [
                'bulk_action' => $action,
                'affected_users' => $targetUserIds,
                'count' => $count,
            ],
            'description' => "ดำเนินการ {$action} กับสมาชิก {$count} คน",
        ]);
    }
}
