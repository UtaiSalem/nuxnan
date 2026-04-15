<?php

namespace App\Services;

use App\Models\ClassroomGroup;
use App\Models\ClassroomMember;
use App\Models\GroupMember;
use Illuminate\Support\Facades\Auth;

/**
 * GroupService — CRUD for classroom sub-groups and their members.
 *
 * Business Rules enforced:
 * - BR-1: User must be a classroom member before being added to a group.
 */
class GroupService
{
    /**
     * List all groups in a classroom.
     */
    public function listGroups(int $classroomId)
    {
        return ClassroomGroup::where('classroom_id', $classroomId)
            ->with('creator')
            ->withCount('groupMembers')
            ->get();
    }

    /**
     * Get a single group with its members.
     */
    public function getGroup(int $groupId): ClassroomGroup
    {
        return ClassroomGroup::with(['groupMembers.classroomMember.user', 'creator'])
            ->withCount('groupMembers')
            ->findOrFail($groupId);
    }

    /**
     * Create a group inside a classroom.
     */
    public function createGroup(int $classroomId, array $data): ClassroomGroup
    {
        $data['classroom_id'] = $classroomId;
        $data['created_by'] = Auth::id();

        return ClassroomGroup::create($data);
    }

    /**
     * Update group info.
     */
    public function updateGroup(ClassroomGroup $group, array $data): ClassroomGroup
    {
        $group->update($data);
        return $group->fresh();
    }

    /**
     * Delete a group (cascade deletes group_members via FK).
     */
    public function deleteGroup(ClassroomGroup $group): void
    {
        $group->delete();
    }

    /**
     * Add a classroom member to a group.
     * BR-1: The user must already be an active classroom member.
     *
     * @param  int $groupId
     * @param  int $memberId  ClassroomMember.id
     * @return GroupMember
     */
    public function addGroupMember(int $groupId, int $memberId): GroupMember
    {
        $group = ClassroomGroup::findOrFail($groupId);

        // BR-1: Verify membership in the same classroom
        $classroomMember = ClassroomMember::where('id', $memberId)
            ->where('classroom_id', $group->classroom_id)
            ->where('is_active', true)
            ->first();

        if (!$classroomMember) {
            abort(422, 'ผู้ใช้ต้องเป็นสมาชิกของห้องเรียนก่อนจึงจะเข้ากลุ่มได้');
        }

        return GroupMember::firstOrCreate(
            ['group_id' => $groupId, 'member_id' => $memberId],
            ['assigned_by' => Auth::id()]
        );
    }

    /**
     * Bulk-add members to a group.
     *
     * @return int Number of members added
     */
    public function bulkAddGroupMembers(int $groupId, array $memberIds): int
    {
        $added = 0;
        foreach ($memberIds as $memberId) {
            try {
                $this->addGroupMember($groupId, $memberId);
                $added++;
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                // Skip non-members (BR-1 violation)
                continue;
            }
        }
        return $added;
    }

    /**
     * Remove a member from a group.
     */
    public function removeGroupMember(int $groupId, int $memberId): void
    {
        GroupMember::where('group_id', $groupId)
            ->where('member_id', $memberId)
            ->delete();
    }
}
