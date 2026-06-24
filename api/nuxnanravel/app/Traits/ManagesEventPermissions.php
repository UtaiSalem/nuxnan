<?php

namespace App\Traits;

use App\Models\Academy;
use App\Models\AcademyGroupAdmin;
use App\Models\User;

/**
 * Shared authorization for SchoolEvent management: academy admins can manage
 * any event; group admins (e.g. หัวหน้าหอพัก, หัวหน้าชมรม) can manage only
 * events scoped to their own group (school_events.group_id).
 */
trait ManagesEventPermissions
{
    protected function isAcademyAdmin(User $user, Academy $academy): bool
    {
        return $academy->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin', 'moderator'])
            ->exists();
    }

    protected function isGroupAdmin(User $user, int $groupId): bool
    {
        return AcademyGroupAdmin::where('academy_group_id', $groupId)
            ->where('user_id', $user->id)
            ->exists();
    }

    // null $groupId = school-wide event → only academy admins may manage it
    protected function canManageEvent(User $user, Academy $academy, ?int $groupId): bool
    {
        if ($this->isAcademyAdmin($user, $academy)) {
            return true;
        }

        return $groupId && $this->isGroupAdmin($user, $groupId);
    }
}
