<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyPermission;
use App\Models\AcademyRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AcademyRoleController extends Controller
{
    /**
     * Get all roles for an academy (including system roles)
     */
    public function index(Academy $academy)
    {
        // Check if user has permission to view roles
        if (! $this->canManageRoles($academy)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์ดูบทบาท',
            ], 403);
        }

        $roles = AcademyRole::forAcademy($academy->id)
            ->active()
            ->orderBy('sort_order')
            ->withCount('members')
            ->get();

        return response()->json([
            'success' => true,
            'roles' => $roles,
        ]);
    }

    /**
     * Get available roles for member assignment
     */
    public function available(Academy $academy)
    {
        $roles = AcademyRole::forAcademy($academy->id)
            ->active()
            ->orderBy('sort_order')
            ->get(['id', 'name', 'display_name_th', 'display_name_en', 'color', 'icon']);

        return response()->json([
            'success' => true,
            'roles' => $roles,
        ]);
    }

    /**
     * Get current user's role in the academy
     */
    public function myRole(Academy $academy)
    {
        $user = Auth::user();

        // Check if user is academy owner
        if ($academy->user_id === $user->id) {
            $ownerRole = AcademyRole::where('name', 'owner')
                ->whereNull('academy_id')
                ->first();

            return response()->json([
                'success' => true,
                'role' => $ownerRole,
                'permissions' => $ownerRole->permissions ?? ['*'],
                'is_owner' => true,
                'is_admin' => true,
            ]);
        }

        // Check if user is academy admin
        $isAdmin = $academy->academyAdmins()->where('user_id', $user->id)->exists();
        if ($isAdmin) {
            $adminRole = AcademyRole::where('name', 'admin')
                ->whereNull('academy_id')
                ->first();

            return response()->json([
                'success' => true,
                'role' => $adminRole,
                'permissions' => $adminRole->permissions ?? [],
                'is_owner' => false,
                'is_admin' => true,
            ]);
        }

        // Check member role
        $member = AcademyMember::where('academy_id', $academy->id)
            ->where('user_id', $user->id)
            ->where('status', 2) // Approved member
            ->with('academyRole')
            ->first();

        if (! $member) {
            return response()->json([
                'success' => true,
                'role' => null,
                'permissions' => [],
                'is_owner' => false,
                'is_admin' => false,
                'is_member' => false,
            ]);
        }

        $role = $member->academyRole;

        // If no role assigned, use default 'student' role
        if (! $role) {
            $role = AcademyRole::where('name', 'student')
                ->whereNull('academy_id')
                ->first();
        }

        return response()->json([
            'success' => true,
            'role' => $role,
            'permissions' => $role->permissions ?? [],
            'is_owner' => false,
            'is_admin' => false,
            'is_member' => true,
            'member_id' => $member->id,
        ]);
    }

    /**
     * Get all available permissions grouped
     */
    public function permissions()
    {
        return response()->json([
            'success' => true,
            'permissions' => AcademyPermission::getGroupedPermissions(),
        ]);
    }

    /**
     * Create a custom role for academy
     */
    public function store(Request $request, Academy $academy)
    {
        if (! $this->canManageRoles($academy)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์สร้างบทบาท',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|regex:/^[a-z_]+$/',
            'display_name_th' => 'required|string|max:100',
            'display_name_en' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'permissions' => 'required|array',
            'color' => 'nullable|string|max:20',
            'icon' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if role name already exists for this academy
        $exists = AcademyRole::where('academy_id', $academy->id)
            ->where('name', $request->name)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'ชื่อบทบาทนี้มีอยู่แล้ว',
            ], 422);
        }

        $maxSort = AcademyRole::forAcademy($academy->id)->max('sort_order') ?? 0;

        $role = AcademyRole::create([
            'academy_id' => $academy->id,
            'name' => $request->name,
            'display_name_th' => $request->display_name_th,
            'display_name_en' => $request->display_name_en,
            'description' => $request->description,
            'permissions' => $request->permissions,
            'color' => $request->color ?? 'gray',
            'icon' => $request->icon ?? 'fluent:person-24-regular',
            'sort_order' => $maxSort + 1,
            'is_system' => false,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'สร้างบทบาทสำเร็จ',
            'role' => $role,
        ], 201);
    }

    /**
     * Update a role
     */
    public function update(Request $request, Academy $academy, AcademyRole $role)
    {
        if (! $this->canManageRoles($academy)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์แก้ไขบทบาท',
            ], 403);
        }

        // Cannot edit system roles
        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถแก้ไขบทบาทระบบได้',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'display_name_th' => 'sometimes|string|max:100',
            'display_name_en' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'permissions' => 'sometimes|array',
            'color' => 'nullable|string|max:20',
            'icon' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($academy->user_id !== Auth::id()
            && $request->has('permissions')
            && ! in_array('members.roles.manage', $request->input('permissions', []), true)
            && AcademyMember::where('academy_id', $academy->id)
                ->where('user_id', Auth::id())
                ->where('academy_role_id', $role->id)
                ->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถแก้ไขสิทธิ์ของบทบาทตัวเองจนสูญเสียสิทธิ์จัดการบทบาท',
            ], 403);
        }

        $role->update($request->only([
            'display_name_th',
            'display_name_en',
            'description',
            'permissions',
            'color',
            'icon',
            'is_active',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทบทบาทสำเร็จ',
            'role' => $role->fresh(),
        ]);
    }

    /**
     * Delete a custom role
     */
    public function destroy(Academy $academy, AcademyRole $role)
    {
        if (! $this->canManageRoles($academy)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์ลบบทบาท',
            ], 403);
        }

        // Cannot delete system roles
        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบบทบาทระบบได้',
            ], 403);
        }

        if ($academy->user_id !== Auth::id()
            && AcademyMember::where('academy_id', $academy->id)
                ->where('user_id', Auth::id())
                ->where('academy_role_id', $role->id)
                ->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบบทบาทของตัวเอง',
            ], 403);
        }

        $studentRole = null;
        $roleSnapshot = [];
        $reassignedCount = DB::transaction(function () use ($academy, $role, &$studentRole, &$roleSnapshot) {
            $studentRole = AcademyRole::where('name', 'student')
                ->whereNull('academy_id')
                ->first();

            if (! $studentRole) {
                return null;
            }

            $reassignedCount = AcademyMember::where('academy_id', $academy->id)
                ->where('academy_role_id', $role->id)
                ->update([
                    'academy_role_id' => $studentRole->id,
                    'role' => 'student',
                ]);
            $roleSnapshot = $role->only(['id', 'name', 'display_name_th', 'permissions']);

            $role->delete();

            return $reassignedCount;
        });

        if (! $studentRole) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบบทบาทเริ่มต้น student — ติดต่อผู้ดูแลระบบ',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => "ลบบทบาทสำเร็จ (โอน {$reassignedCount} สมาชิกไปเป็นนักเรียน)",
            'reassigned_count' => $reassignedCount,
        ]);
    }

    /**
     * Assign role to a member
     */
    public function assignRole(Request $request, Academy $academy, AcademyMember $member)
    {
        if (! $this->canManageRoles($academy)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์กำหนดบทบาท',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:academy_roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify role belongs to this academy or is a system role
        $role = AcademyRole::where('id', $request->role_id)
            ->where(function ($q) use ($academy) {
                $q->whereNull('academy_id')
                    ->orWhere('academy_id', $academy->id);
            })
            ->first();

        if (! $role) {
            return response()->json([
                'success' => false,
                'message' => 'บทบาทไม่ถูกต้อง',
            ], 422);
        }

        $member->update([
            'academy_role_id' => $role->id,
            'role' => $role->name, // Also update the legacy 'role' column
        ]);

        return response()->json([
            'success' => true,
            'message' => 'กำหนดบทบาทสำเร็จ',
            'member' => $member->load('academyRole'),
        ]);
    }

    /**
     * Bulk assign roles to members
     */
    public function bulkAssignRole(Request $request, Academy $academy)
    {
        if (! $this->canManageRoles($academy)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์กำหนดบทบาท',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'member_ids' => 'required|array',
            'member_ids.*' => 'exists:academy_members,id',
            'role_id' => 'required|exists:academy_roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = AcademyRole::where('id', $request->role_id)
            ->where(function ($q) use ($academy) {
                $q->whereNull('academy_id')
                    ->orWhere('academy_id', $academy->id);
            })
            ->first();

        if (! $role) {
            return response()->json([
                'success' => false,
                'message' => 'บทบาทไม่ถูกต้อง',
            ], 422);
        }

        $updated = AcademyMember::where('academy_id', $academy->id)
            ->whereIn('id', $request->member_ids)
            ->update([
                'academy_role_id' => $role->id,
                'role' => $role->name,
            ]);

        return response()->json([
            'success' => true,
            'message' => "กำหนดบทบาทสำเร็จ {$updated} คน",
            'updated_count' => $updated,
        ]);
    }

    /**
     * Check if current user can manage roles in the academy
     */
    private function canManageRoles(Academy $academy): bool
    {
        $user = Auth::user();

        // Owner can manage everything
        if ($academy->user_id === $user->id) {
            return true;
        }

        // Admin can manage roles
        if ($academy->academyAdmins()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Check member permission
        $member = AcademyMember::where('academy_id', $academy->id)
            ->where('user_id', $user->id)
            ->with('academyRole')
            ->first();

        if ($member && $member->academyRole) {
            return $member->academyRole->hasPermission('members.roles.manage');
        }

        return false;
    }
}
