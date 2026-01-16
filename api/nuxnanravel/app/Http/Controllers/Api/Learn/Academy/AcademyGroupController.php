<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;

use App\Models\Academy;
use App\Models\AcademyGroup;
use Illuminate\Http\Request;

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
            'groups' => $academy->academyGroups()->get()
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
}
