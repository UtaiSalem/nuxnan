<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;

use App\Models\Academy;
use App\Models\AcademyGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademyGroupController extends Controller
{
    // Middleware is handled in route definitions (routes/learn/academy.php)

    /**

     * Display a listing of the resource.
     */
    public function index(Academy $academy)
    {
        return response()->json([
            'success' => true,
            'groups' => $academy->academyGroups()->withCount('members')->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Academy $academy, Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:department,classroom,club',
            'settings' => 'nullable|array'
        ]);

        $group = $academy->academyGroups()->create($validated);

        return response()->json([
            'success' => true,
            'group' => $group
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(AcademyGroup $academyGroup)
    {
        return response()->json([
            'success' => true,
            'group' => $academyGroup->load('academy')
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AcademyGroup $academyGroup)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string|in:department,classroom,club',
            'settings' => 'nullable|array'
        ]);

        $academyGroup->update($validated);

        return response()->json([
            'success' => true,
            'group' => $academyGroup
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AcademyGroup $academyGroup)
    {
        $academyGroup->delete();

        return response()->json([
            'success' => true,
            'message' => 'Group deleted successfully'
        ]);
    }

    /**
     * Get all members of a group.
     */
    public function getMembers(AcademyGroup $academyGroup)
    {
        $members = $academyGroup->members()->with('profile')->get();

        return response()->json([
            'success' => true,
            'members' => $members,
            'members_count' => $members->count()
        ]);
    }

    /**
     * Add a member to a group.
     */
    public function addMember(AcademyGroup $academyGroup, Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|string|in:student,teacher,admin'
        ]);

        // Check if already a member
        if ($academyGroup->members()->where('user_id', $validated['user_id'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'ผู้ใช้นี้เป็นสมาชิกของกลุ่มอยู่แล้ว'
            ], 400);
        }

        $academyGroup->members()->attach($validated['user_id'], [
            'role' => $validated['role'] ?? 'student'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มสมาชิกเรียบร้อยแล้ว'
        ]);
    }

    /**
     * Remove a member from a group.
     */
    public function removeMember(AcademyGroup $academyGroup, Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $academyGroup->members()->detach($validated['user_id']);

        return response()->json([
            'success' => true,
            'message' => 'ลบสมาชิกออกจากกลุ่มเรียบร้อยแล้ว'
        ]);
    }

    /**
     * Update a member's role in the group.
     */
    public function updateMemberRole(AcademyGroup $academyGroup, Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string|in:student,teacher,admin'
        ]);

        $academyGroup->members()->updateExistingPivot($validated['user_id'], [
            'role' => $validated['role']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตบทบาทสมาชิกเรียบร้อยแล้ว'
        ]);
    }

    /**
     * Get groups by type (departments, classrooms, or clubs).
     */
    public function getByType(Academy $academy, string $type)
    {
        if (!in_array($type, ['department', 'classroom', 'club'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid group type'
            ], 400);
        }

        $groups = $academy->academyGroups()
            ->where('type', $type)
            ->withCount('members')
            ->get();

        return response()->json([
            'success' => true,
            'groups' => $groups
        ]);
    }
    /**
     * Get groups for a specific user.
     */
    public function getUserGroups(\App\Models\User $user)
    {
        $groups = \App\Models\AcademyGroup::whereHas('members', function($q) use ($user) {
            $q->where('users.id', $user->id);
        })->withCount('members')->take(20)->get();

        return response()->json([
            'success' => true,
            'groups' => $groups->map(function($group) use ($user) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'slug' => (string)$group->id,
                    'description' => $group->description,
                    'members_count' => $group->members_count,
                    'category' => $group->type,
                    'privacy' => 'public',
                    'is_member' => true,
                    'is_admin' => $group->admins()->where('users.id', $user->id)->exists(),
                ];
            })
        ]);
    }

    /**
     * Reorder academy groups
     */
    public function reorder(Academy $academy, Request $request)
    {
        try {
            // Permission check (Academy Admin or Super Admin)
            if (!$academy->isAdmin(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์จัดลำดับกลุ่มในสถาบันนี้',
                ], 403);
            }

            // Validate request
            $validated = $request->validate([
                'groups' => 'required|array|min:1',
                'groups.*' => 'required|integer|exists:academy_groups,id',
            ]);

            // Verify group IDs belong to this academy and all are present
            $academyGroupIds = $academy->academyGroups()->pluck('id')->toArray();
            $incomingIds = $validated['groups'];

            if (count($incomingIds) !== count($academyGroupIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'จำนวนกลุ่มไม่ถูกต้อง กรุณาส่งรายการกลุ่มทั้งหมดในสถาบันนี้',
                ], 422);
            }

            if (count($incomingIds) !== count(array_unique($incomingIds))) {
                return response()->json([
                    'success' => false,
                    'message' => 'มีไอดีกลุ่มซ้ำในรายการที่ส่งมา',
                ], 422);
            }

            foreach ($incomingIds as $id) {
                if (!in_array($id, $academyGroupIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => "กลุ่ม ID {$id} ไม่ได้อยู่ในสถาบันนี้",
                    ], 422);
                }
            }

            // Perform reorder in transaction
            DB::transaction(function () use ($incomingIds) {
                foreach ($incomingIds as $index => $id) {
                    AcademyGroup::where('id', $id)->update(['sort_order' => $index + 1]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'บันทึกลำดับกลุ่มสำเร็จ',
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error reordering academy groups: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการจัดลำดับกลุ่ม',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
