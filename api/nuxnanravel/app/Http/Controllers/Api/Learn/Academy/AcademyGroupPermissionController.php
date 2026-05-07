<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Services\AcademyGroupPermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademyGroupPermissionController extends Controller
{
    protected $permissionService;

    public function __construct(AcademyGroupPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Get permissions for a specific group/department
     */
    public function index(Academy $academy, AcademyGroup $department): JsonResponse
    {
        // Ensure group belongs to academy and is a department (as per requirements)
        if ($department->academy_id !== $academy->id || $department->type !== 'department') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid group or not a department.'
            ], 404);
        }

        $permissions = $this->permissionService->getAllPermissions($department);

        return response()->json([
            'success' => true,
            'data' => [
                'permissions' => $permissions,
                'enabled_keys' => $permissions->where('enabled', true)->pluck('permission_key'),
            ]
        ]);
    }

    /**
     * Update/Sync permissions for a specific group/department
     */
    public function update(Request $request, Academy $academy, AcademyGroup $department): JsonResponse
    {
        if ($department->academy_id !== $academy->id || $department->type !== 'department') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid group or not a department.'
            ], 404);
        }

        $validated = $request->validate([
            'permission_keys' => 'required|array',
            'permission_keys.*' => 'string',
        ]);

        $this->permissionService->syncPermissions($department, $validated['permission_keys']);

        return response()->json([
            'success' => true,
            'message' => 'Permissions updated successfully.',
            'data' => [
                'enabled_keys' => $this->permissionService->getEnabledPermissions($department),
            ]
        ]);
    }
}
