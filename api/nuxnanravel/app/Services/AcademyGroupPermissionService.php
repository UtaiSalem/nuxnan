<?php

namespace App\Services;

use App\Models\AcademyGroup;
use App\Models\AcademyPermission;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AcademyGroupPermissionService
{
    /**
     * Get enabled permissions for a group
     */
    public function getEnabledPermissions(AcademyGroup $group): Collection
    {
        return $group->permissions()
            ->where('enabled', true)
            ->pluck('permission_key');
    }

    /**
     * Get all permissions with their enabled status for a group
     */
    public function getAllPermissions(AcademyGroup $group): Collection
    {
        return $group->permissions()->get();
    }

    /**
     * Sync permissions for a group
     * Sets only provided permission keys to enabled, others for the same group to disabled
     */
    public function syncPermissions(AcademyGroup $group, array $permissionKeys): void
    {
        if ($group->type === 'department' && ($rejected = AcademyPermission::nonDelegableDepartmentKeys($permissionKeys)) !== []) {
            throw ValidationException::withMessages([
                'permission_keys' => ['Non-delegable permission(s): '.implode(', ', $rejected)],
            ]);
        }

        // Disable all current permissions first (optional approach)
        // Or more efficiently:

        // 1. Get current permissions
        $existing = $group->permissions()->pluck('permission_key')->toArray();

        // 2. Identify keys to enable
        foreach ($permissionKeys as $key) {
            $group->permissions()->updateOrCreate(
                ['permission_key' => $key],
                ['enabled' => true]
            );
        }

        // 3. Disable keys not in the provided list but existing in DB
        $group->permissions()
            ->whereNotIn('permission_key', $permissionKeys)
            ->update(['enabled' => false]);
    }

    /**
     * Check if a group has a specific permission
     */
    public function hasPermission(AcademyGroup $group, string $permissionKey): bool
    {
        $permission = $group->permissions()
            ->where('permission_key', $permissionKey)
            ->first();

        return $permission ? (bool) $permission->enabled : true;
    }
}
