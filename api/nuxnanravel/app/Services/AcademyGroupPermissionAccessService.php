<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\AcademyGroupMember;
use App\Models\AcademyGroupPermission;
use App\Models\AcademyPermission;
use App\Models\User;

class AcademyGroupPermissionAccessService
{
    /**
     * Check explicit, enabled group permission access for an academy member.
     *
     * This grants academy-wide access today; data-scope restrictions are not
     * implemented until D-S4. Be aware that enabling a permission for a group
     * allows its members to see or modify data across the whole academy.
     */
    public function hasAnyPermission(User $user, Academy $academy, array $permissions): bool
    {
        $permissions = array_values(array_intersect($permissions, AcademyPermission::departmentDelegableKeys()));
        if ($permissions === []) {
            return false;
        }

        return AcademyGroupMember::query()
            ->where('user_id', $user->id)
            ->where('status', 2)
            ->whereHas('group', fn ($query) => $query->where('academy_id', $academy->id))
            ->whereHas('group.permissions', fn ($query) => $query
                ->whereIn('permission_key', $permissions)
                ->where('enabled', true))
            ->exists();
    }

    /**
     * Every delegable permission key this user gains from the departments they belong to.
     *
     * Mirrors hasAnyPermission(): only rows with enabled = true count, and the result is
     * intersected with the delegable allow-list so a stale row can never widen access.
     *
     * @return list<string>
     */
    public function permissionKeysFor(User $user, Academy $academy): array
    {
        $groupIds = AcademyGroupMember::query()
            ->where('user_id', $user->id)
            ->where('status', 2)
            ->whereHas('group', fn ($query) => $query->where('academy_id', $academy->id))
            ->pluck('academy_group_id');

        if ($groupIds->isEmpty()) {
            return [];
        }

        $keys = AcademyGroupPermission::query()
            ->whereIn('academy_group_id', $groupIds)
            ->where('enabled', true)
            ->pluck('permission_key')
            ->all();

        return array_values(array_unique(
            array_intersect($keys, AcademyPermission::departmentDelegableKeys())
        ));
    }
}
