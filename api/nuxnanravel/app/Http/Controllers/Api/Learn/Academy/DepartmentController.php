<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\User;
use App\Services\AcademyGroupPermissionService;
use App\Services\SchoolDepartmentSetupService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Department Controller
 *
 * จัดการฝ่ายงาน/แผนก ภายในสถาบัน
 * เช่น ฝ่ายวิชาการ, ฝ่ายการเงิน, ฝ่ายบุคคล
 */
class DepartmentController extends Controller
{
    use AuthorizesRequests;

    protected $permissionService;

    public function __construct(AcademyGroupPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Ensure the department feature flag is enabled.
     *
     * This only checks per-department feature enablement and defaults to enabled
     * when no permission row exists. User authorization is enforced by route middleware.
     */
    protected function ensureDepartmentFeatureEnabled(AcademyGroup $department, string $permissionKey): void
    {
        if (! $this->permissionService->hasPermission($department, $permissionKey)) {
            abort(response()->json([
                'success' => false,
                'message' => "ฝ่ายงานนี้ไม่ได้เปิดใช้งานสิทธิ์: {$permissionKey}",
            ], 403));
        }
    }

    /**
     * Ensure the group is a department under the requested academy.
     */
    protected function ensureDepartment(Academy $academy, AcademyGroup $department): ?JsonResponse
    {
        if ($department->academy_id !== $academy->id || $department->type !== 'department') {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบฝ่ายงานที่ระบุ',
            ], 404);
        }

        return null;
    }

    /**
     * Get all departments of an academy
     */
    public function index(Request $request, Academy $academy): JsonResponse
    {
        $query = $academy->academyGroups()
            ->where('type', 'department')
            // Filter departments that have 'departments.view' enabled (default true)
            ->whereDoesntHave('permissions', function ($q) {
                $q->where('permission_key', 'departments.view')->where('enabled', false);
            })
            ->withCount('members');

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        // Include members if requested
        if ($request->boolean('with_members')) {
            $query->with(['members:id,name,email,profile_photo_path']);
        }

        $departments = $query->orderBy('name')->get();
        $headUserIds = $departments
            ->map(fn ($department) => data_get($department->settings, 'head_user_id'))
            ->filter()
            ->unique()
            ->values();
        $headUsers = $headUserIds->isNotEmpty()
            ? User::query()
                ->whereIn('id', $headUserIds)
                ->get(['id', 'name', 'email', 'avatar', 'profile_photo_path'])
                ->keyBy('id')
            : collect();

        $departments->each(function ($department) use ($headUsers) {
            $headUserId = data_get($department->settings, 'head_user_id');
            $headUser = $headUserId ? $headUsers->get((int) $headUserId) : null;

            $department->setAttribute('head_user_id', $headUserId);
            $department->setAttribute('head_user', $headUser ? [
                'id' => $headUser->id,
                'name' => $headUser->name,
                'email' => $headUser->email,
                'profile_photo_url' => $headUser->profile_photo_url,
            ] : null);
        });

        return response()->json([
            'success' => true,
            'data' => [
                'departments' => $departments,
                'total' => $departments->count(),
            ],
        ]);
    }

    /**
     * Create a new department
     */
    public function store(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'head_user_id' => 'nullable|exists:users,id',
            'settings' => 'nullable|array',
            'settings.color' => 'nullable|string|max:7',
            'settings.icon' => 'nullable|string|max:50',
        ]);

        $department = $academy->academyGroups()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => 'department',
            'settings' => array_merge($validated['settings'] ?? [], [
                'head_user_id' => $validated['head_user_id'] ?? null,
            ]),
        ]);

        // Add head as admin if specified
        if (! empty($validated['head_user_id'])) {
            $department->members()->attach($validated['head_user_id'], [
                'role' => 'admin',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'สร้างฝ่ายงานสำเร็จ',
            'data' => $department->load('members'),
        ], 201);
    }

    /**
     * Get department details
     */
    public function show(Academy $academy, AcademyGroup $department): JsonResponse
    {
        if ($response = $this->ensureDepartment($academy, $department)) {
            return $response;
        }

        $this->ensureDepartmentFeatureEnabled($department, 'departments.view');

        $department->load(['members:id,name,email,profile_photo_path', 'academy:id,name']);
        $department->loadCount('members');

        // Get head user
        $headUserId = $department->settings['head_user_id'] ?? null;
        $headUser = $headUserId ? User::find($headUserId) : null;

        return response()->json([
            'success' => true,
            'data' => [
                'department' => $department,
                'head' => $headUser ? [
                    'id' => $headUser->id,
                    'name' => $headUser->name,
                    'email' => $headUser->email,
                    'avatar' => $headUser->avatar,
                ] : null,
            ],
        ]);
    }

    /**
     * Update department
     */
    public function update(Request $request, Academy $academy, AcademyGroup $department): JsonResponse
    {
        if ($response = $this->ensureDepartment($academy, $department)) {
            return $response;
        }

        $this->ensureDepartmentFeatureEnabled($department, 'departments.manage');

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'head_user_id' => 'nullable|exists:users,id',
            'settings' => 'nullable|array',
        ]);

        $updateData = [
            'name' => $validated['name'] ?? $department->name,
            'description' => $validated['description'] ?? $department->description,
        ];

        if (isset($validated['settings']) || isset($validated['head_user_id'])) {
            $settings = $department->settings ?? [];
            if (isset($validated['settings'])) {
                $settings = array_merge($settings, $validated['settings']);
            }
            if (isset($validated['head_user_id'])) {
                $settings['head_user_id'] = $validated['head_user_id'];
            }
            $updateData['settings'] = $settings;
        }

        $department->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตฝ่ายงานสำเร็จ',
            'data' => $department->fresh(),
        ]);
    }

    /**
     * Delete department
     */
    public function destroy(Academy $academy, AcademyGroup $department): JsonResponse
    {
        if ($response = $this->ensureDepartment($academy, $department)) {
            return $response;
        }

        $this->ensureDepartmentFeatureEnabled($department, 'departments.manage');

        $membersCount = $department->members()->count();

        if ($membersCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "ไม่สามารถลบได้ เนื่องจากมีสมาชิก {$membersCount} คน กรุณาย้ายสมาชิกออกก่อน",
            ], 400);
        }

        $department->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบฝ่ายงานสำเร็จ',
        ]);
    }

    /**
     * Get department members with roles
     */
    public function getMembers(Request $request, Academy $academy, AcademyGroup $department): JsonResponse
    {
        if ($response = $this->ensureDepartment($academy, $department)) {
            return $response;
        }

        $this->ensureDepartmentFeatureEnabled($department, 'departments.manage-members');

        $query = $department->members()
            ->select('users.id', 'users.name', 'users.email', 'users.profile_photo_path')
            ->withPivot('role', 'created_at');

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role')) {
            $query->wherePivot('role', $request->role);
        }

        $members = $query->orderByPivot('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'members' => $members->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'name' => $member->name,
                        'email' => $member->email,
                        'avatar' => $member->avatar,
                        'profile_photo_url' => $member->profile_photo_url,
                        'role' => $member->pivot->role,
                        'joined_at' => $member->pivot->created_at,
                    ];
                }),
                'total' => $members->count(),
            ],
        ]);
    }

    /**
     * Add member to department
     */
    public function addMember(Request $request, Academy $academy, AcademyGroup $department): JsonResponse
    {
        if ($response = $this->ensureDepartment($academy, $department)) {
            return $response;
        }

        $this->ensureDepartmentFeatureEnabled($department, 'departments.manage-members');

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|string|in:member,staff,admin,head',
        ]);

        // Check if already a member
        if ($department->members()->where('users.id', $validated['user_id'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'ผู้ใช้นี้เป็นสมาชิกของฝ่ายงานนี้อยู่แล้ว',
            ], 400);
        }

        $department->members()->attach($validated['user_id'], [
            'role' => $validated['role'] ?? 'member',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มสมาชิกเข้าฝ่ายงานสำเร็จ',
        ]);
    }

    /**
     * Remove member from department
     */
    public function removeMember(Request $request, Academy $academy, AcademyGroup $department): JsonResponse
    {
        if ($response = $this->ensureDepartment($academy, $department)) {
            return $response;
        }

        $this->ensureDepartmentFeatureEnabled($department, 'departments.manage-members');

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $department->members()->detach($validated['user_id']);

        return response()->json([
            'success' => true,
            'message' => 'ลบสมาชิกออกจากฝ่ายงานสำเร็จ',
        ]);
    }

    /**
     * Bulk add members to department
     */
    public function bulkAddMembers(Request $request, Academy $academy, AcademyGroup $department): JsonResponse
    {
        if ($response = $this->ensureDepartment($academy, $department)) {
            return $response;
        }

        $this->ensureDepartmentFeatureEnabled($department, 'departments.manage-members');

        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'role' => 'nullable|string|in:member,staff,admin,head',
        ]);

        $role = $validated['role'] ?? 'member';
        $added = 0;
        $skipped = 0;

        foreach ($validated['user_ids'] as $userId) {
            if ($department->members()->where('users.id', $userId)->exists()) {
                $skipped++;

                continue;
            }

            $department->members()->attach($userId, ['role' => $role]);
            $added++;
        }

        return response()->json([
            'success' => true,
            'message' => "เพิ่มสมาชิกสำเร็จ {$added} คน".($skipped > 0 ? " (ข้าม {$skipped} คนที่มีอยู่แล้ว)" : ''),
            'data' => [
                'added' => $added,
                'skipped' => $skipped,
            ],
        ]);
    }

    /**
     * Update member role in department
     */
    public function updateMemberRole(Request $request, Academy $academy, AcademyGroup $department): JsonResponse
    {
        if ($response = $this->ensureDepartment($academy, $department)) {
            return $response;
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string|in:member,staff,admin,head',
        ]);

        if (! $department->members()->where('users.id', $validated['user_id'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบผู้ใช้ในฝ่ายงานนี้',
            ], 404);
        }

        $department->members()->updateExistingPivot($validated['user_id'], [
            'role' => $validated['role'],
        ]);

        // If setting as head, update settings
        if ($validated['role'] === 'head') {
            $settings = $department->settings ?? [];
            $settings['head_user_id'] = $validated['user_id'];
            $department->update(['settings' => $settings]);
        }

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตบทบาทสมาชิกสำเร็จ',
        ]);
    }

    /**
     * Get the standard Thai school department template hierarchy
     */
    public function getTemplate(): JsonResponse
    {
        $service = new SchoolDepartmentSetupService;

        return response()->json([
            'success' => true,
            'data' => [
                'template' => $service->getTemplate(),
            ],
        ]);
    }

    /**
     * Create standard Thai school department structure for an academy
     */
    public function setupDepartments(Request $request, Academy $academy): JsonResponse
    {
        $service = new SchoolDepartmentSetupService;

        if (! $request->boolean('force') && $service->hasExistingDepartments($academy)) {
            return response()->json([
                'success' => false,
                'message' => 'โรงเรียนนี้มีโครงสร้างฝ่ายงานอยู่แล้ว หากต้องการสร้างเพิ่มเติม ให้ส่ง force=true',
                'has_existing' => true,
            ], 409);
        }

        $result = $service->setup($academy, auth()->id());

        return response()->json([
            'success' => true,
            'message' => "สร้างโครงสร้างฝ่ายงานสำเร็จ ({$result['created']} รายการ)".
                ($result['skipped'] > 0 ? " ข้าม {$result['skipped']} รายการที่มีอยู่แล้ว" : ''),
            'data' => $result,
        ], 201);
    }

    /**
     * Get department statistics
     */
    public function getStatistics(Academy $academy): JsonResponse
    {
        $departments = $academy->academyGroups()
            ->where('type', 'department')
            ->withCount('members')
            ->get();

        $totalMembers = $departments->sum('members_count');
        $avgMembers = $departments->count() > 0
            ? round($totalMembers / $departments->count(), 1)
            : 0;
        $departmentsWithHead = $departments->filter(function ($department) {
            return ! empty(data_get($department->settings, 'head_user_id'));
        })->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_departments' => $departments->count(),
                'total_members' => $totalMembers,
                'average_members_per_department' => $avgMembers,
                'departments_with_head' => $departmentsWithHead,
                'departments' => $departments->map(function ($dept) {
                    return [
                        'id' => $dept->id,
                        'name' => $dept->name,
                        'members_count' => $dept->members_count,
                    ];
                }),
            ],
        ]);
    }
}
