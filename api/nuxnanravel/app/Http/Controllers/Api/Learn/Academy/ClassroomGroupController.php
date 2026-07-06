<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\ClassroomGroup;
use App\Services\GroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClassroomGroupController extends Controller
{
    private GroupService $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    /**
     * List all groups in a classroom
     */
    public function index(int $academyId, int $classroomId): JsonResponse
    {
        $groups = $this->groupService->listGroups($classroomId);

        return response()->json([
            'success' => true,
            'data' => $groups,
        ]);
    }

    /**
     * Show a single group with members
     */
    public function show(int $academyId, int $classroomId, int $groupId): JsonResponse
    {
        $group = $this->groupService->getGroup($groupId);

        return response()->json([
            'success' => true,
            'data' => $group,
        ]);
    }

    /**
     * Create a new group
     */
    public function store(Request $request, int $academyId, int $classroomId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);
        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $validated = $request->validate([
            'group_name' => 'required|string|max:255',
            'group_type' => 'in:static,dynamic',
            'description' => 'nullable|string|max:255',
        ]);

        $group = $this->groupService->createGroup($classroomId, $validated);

        return response()->json([
            'success' => true,
            'message' => 'สร้างกลุ่มสำเร็จ',
            'data' => $group,
        ], 201);
    }

    /**
     * Update a group
     */
    public function update(Request $request, int $academyId, int $classroomId, int $groupId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);
        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $group = ClassroomGroup::where('classroom_id', $classroomId)->findOrFail($groupId);

        $validated = $request->validate([
            'group_name' => 'string|max:255',
            'group_type' => 'in:static,dynamic',
            'description' => 'nullable|string|max:255',
        ]);

        $group = $this->groupService->updateGroup($group, $validated);

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตกลุ่มสำเร็จ',
            'data' => $group,
        ]);
    }

    /**
     * Delete a group
     */
    public function destroy(int $academyId, int $classroomId, int $groupId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);
        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $group = ClassroomGroup::where('classroom_id', $classroomId)->findOrFail($groupId);
        $this->groupService->deleteGroup($group);

        return response()->json([
            'success' => true,
            'message' => 'ลบกลุ่มสำเร็จ',
        ]);
    }

    /**
     * Add a member to a group
     */
    public function addMember(Request $request, int $academyId, int $classroomId, int $groupId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);
        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $validated = $request->validate([
            'member_id' => 'required|exists:classroom_members,id',
        ]);

        $groupMember = $this->groupService->addGroupMember($groupId, $validated['member_id']);

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มสมาชิกในกลุ่มสำเร็จ',
            'data' => $groupMember->load('classroomMember.user'),
        ], 201);
    }

    /**
     * Bulk add members to a group
     */
    public function bulkAddMembers(Request $request, int $academyId, int $classroomId, int $groupId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);
        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $validated = $request->validate([
            'member_ids' => 'required|array',
            'member_ids.*' => 'exists:classroom_members,id',
        ]);

        $added = $this->groupService->bulkAddGroupMembers($groupId, $validated['member_ids']);

        return response()->json([
            'success' => true,
            'message' => "เพิ่มสมาชิกสำเร็จ {$added} คน",
            'meta' => ['added' => $added],
        ]);
    }

    /**
     * Remove a member from a group
     */
    public function removeMember(int $academyId, int $classroomId, int $groupId, int $memberId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);
        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $this->groupService->removeGroupMember($groupId, $memberId);

        return response()->json([
            'success' => true,
            'message' => 'ลบสมาชิกออกจากกลุ่มสำเร็จ',
        ]);
    }

    // ───── Auth Helper ─────

    protected function canManage(Academy $academy): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($academy->user_id === $user->id) {
            return true;
        }

        return $academy->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'director', 'admin'])
            ->exists();
    }
}
