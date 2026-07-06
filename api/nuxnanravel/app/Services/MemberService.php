<?php

namespace App\Services;

use App\Exceptions\ClassroomCapacityException;
use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\GroupMember;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * MemberService — Add, remove, transfer and query classroom members.
 *
 * Business Rules enforced:
 * - BR-2: Auto-remove from all groups when membership is removed.
 * - BR-5: Capacity enforcement before adding students.
 */
class MemberService
{
    /**
     * List members of a classroom with optional role filter.
     *
     * @param  string|null  $role  Filter by role
     * @return Collection
     */
    public function listMembers(int $classroomId, ?string $role = null)
    {
        $query = ClassroomMember::where('classroom_id', $classroomId)
            ->where('is_active', true)
            ->with('user')
            ->orderBy('role')
            ->orderBy('student_no');

        if ($role) {
            $query->where('role', $role);
        }

        return $query->get();
    }

    /**
     * Add a single member to a classroom.
     *
     *
     * @throws ClassroomCapacityException
     */
    public function addMember(
        Classroom $classroom,
        int $userId,
        string $role = 'student',
        ?int $studentNo = null,
        string $joinMethod = 'admin_assigned'
    ): ClassroomMember {
        // BR-5: capacity check for students
        if ($role === 'student' && $classroom->isFull()) {
            abort(422, 'ห้องเรียนเต็มแล้ว ไม่สามารถเพิ่มนักเรียนได้');
        }

        // Auto-assign next student_no if not provided
        if ($role === 'student' && $studentNo === null) {
            $studentNo = ClassroomMember::where('classroom_id', $classroom->id)
                ->where('role', 'student')
                ->where('is_active', true)
                ->max('student_no') + 1;
        }

        return ClassroomMember::updateOrCreate(
            ['classroom_id' => $classroom->id, 'user_id' => $userId],
            [
                'role' => $role,
                'student_no' => $studentNo,
                'join_method' => $joinMethod,
                'is_active' => true,
                'joined_at' => now(),
                'left_at' => null,
                'added_by' => Auth::id(),
            ]
        );
    }

    /**
     * Bulk-add members to a classroom.
     *
     * @return int Number added
     */
    public function bulkAddMembers(Classroom $classroom, array $userIds, string $role = 'student'): int
    {
        $added = 0;
        foreach ($userIds as $userId) {
            // BR-5: stop adding when capacity is reached (students only)
            if ($role === 'student' && $classroom->isFull()) {
                break;
            }
            $this->addMember($classroom, $userId, $role);
            $added++;
            // Clear cached count so isFull() recalculates
            $classroom->unsetRelation('activeMembers');
        }

        return $added;
    }

    /**
     * Remove a member from a classroom (soft-deactivate).
     * BR-2: Also removes the member from all classroom groups.
     *
     * @param  int  $memberId  ClassroomMember ID
     */
    public function removeMember(int $classroomId, int $memberId): bool
    {
        return DB::transaction(function () use ($classroomId, $memberId) {
            $member = ClassroomMember::where('classroom_id', $classroomId)
                ->where('id', $memberId)
                ->firstOrFail();

            // BR-2: cascade remove from groups
            GroupMember::where('member_id', $member->id)->delete();

            return $member->deactivate();
        });
    }

    /**
     * Update member details (role, student_no).
     */
    public function updateMember(int $memberId, array $data): ClassroomMember
    {
        $member = ClassroomMember::findOrFail($memberId);
        $member->update($data);

        return $member->fresh('user');
    }

    /**
     * Transfer a member from one classroom to another.
     *
     * @param  int  $memberId  ClassroomMember ID
     * @param  int  $targetClassroomId  Destination classroom
     * @return ClassroomMember The new membership record
     */
    public function transferMember(int $memberId, int $targetClassroomId): ClassroomMember
    {
        return DB::transaction(function () use ($memberId, $targetClassroomId) {
            $oldMember = ClassroomMember::findOrFail($memberId);
            $targetClassroom = Classroom::findOrFail($targetClassroomId);

            // BR-5: capacity check
            if ($oldMember->role === 'student' && $targetClassroom->isFull()) {
                abort(422, 'ห้องเรียนปลายทางเต็มแล้ว');
            }

            // Deactivate old membership (BR-2: cascades group removal)
            GroupMember::where('member_id', $oldMember->id)->delete();
            $oldMember->deactivate();

            // Create new membership in target
            return $this->addMember(
                $targetClassroom,
                $oldMember->user_id,
                $oldMember->role,
                null,
                'admin_assigned'
            );
        });
    }
}
