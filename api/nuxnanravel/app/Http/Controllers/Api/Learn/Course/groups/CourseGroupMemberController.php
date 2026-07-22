<?php

namespace App\Http\Controllers\Api\Learn\Course\groups;

use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Course\groups\CourseGroupResource;
use App\Http\Resources\Learn\Course\members\CourseMemberResource;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\CourseGroupMember;
use App\Models\CourseMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseGroupMemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Course $course, CourseGroup $group)
    {
        $user = auth()->user();

        // Check if already a member
        $existingMember = CourseGroupMember::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingMember) {
            if ($existingMember->request_status === 'pending') {
                return response()->json(['success' => false, 'message' => 'คำขอเข้าร่วมกลุ่มของท่านกำลังรอการอนุมัติ'], 400);
            }
            // If already approved, we proceed to ensure state consistency (self-healing)
            // instead of returning error. This handles cases where they might be stuck
            // with multiple group memberships or out-of-sync CourseMember data.
            $requestStatus = 'approved';
            $status = 1;
        } else {
            // Determine status based on privacy
            $requestStatus = 'approved';
            $status = 1;

            if ($group->privacy === 'private') {
                $requestStatus = 'pending';
                $status = 0;
            }
        }

        // If approved (either new or existing), remove from other groups to enforce multiple-group restriction
        if ($requestStatus === 'approved') {
            CourseGroupMember::where('course_id', $course->id)
                ->where('user_id', $user->id)
                ->where('group_id', '!=', $group->id)
                ->delete();
        }

        // Create or Update CourseGroupMember
        $groupMember = CourseGroupMember::updateOrCreate(
            ['group_id' => $group->id, 'user_id' => $user->id],
            [
                'course_id' => $course->id,
                'status' => $status,
                'request_status' => $requestStatus,
                'role' => 'member',
            ]
        );

        // Sync with CourseMember (Legacy support? Or main truth?)
        // Ideally CourseMember just tracks "current active group" or similar.
        // For now, let's keep logic similar to before but safe.
        $courseAutoAcceptMembers = ($course->courseSettings->auto_accept_members ?? 1) === 0 ? 0 : 1;

        $courseMember = CourseMember::where('course_id', $course->id)->where('user_id', $user->id)->first();
        if ($courseMember) {
            // Only switch focus if approved
            if ($requestStatus === 'approved') {
                $courseMember->group_id = $group->id;
                $courseMember->group_member_status = 1;
                $courseMember->save();
            }
        } else {
            // Create CourseMember if not exists (e.g. joined group directly?)
            // Usually user joins course first. But if logic allows:
            $courseMember = new CourseMember;
            $courseMember->user_id = $user->id;
            $courseMember->course_id = $course->id;
            $courseMember->course_member_status = $courseAutoAcceptMembers; // Respect course setting
            $courseMember->status = $courseAutoAcceptMembers; // Also sync main status
            $courseMember->group_id = ($requestStatus === 'approved') ? $group->id : null;
            $courseMember->group_member_status = ($requestStatus === 'approved') ? 1 : 0;
            $courseMember->save();
        }

        if ($courseMember) {
            $courseMember->refresh();
        }

        return response()->json([
            'success' => true,
            'message' => ($requestStatus === 'pending') ? 'ส่งคำขอเข้าร่วมกลุ่มแล้ว รอการอนุมัติ' : 'เข้าร่วมกลุ่มสำเร็จ',
            'status' => $requestStatus,
            'group' => new CourseGroupResource($group),
            'courseMemberOfAuth' => new CourseMemberResource($courseMember),
        ], 200);
    }

    public function approveRequest(Course $course, CourseGroup $group, $memberId)
    {
        $groupMember = CourseGroupMember::findOrFail($memberId);

        // Authorization check (Admin/Moderator)
        // Check if auth user is group admin
        $authMember = CourseGroupMember::where('group_id', $group->id)->where('user_id', auth()->id())->first();
        $isCourseAdmin = $course->isAdmin(auth()->user());

        if (! $isCourseAdmin && (! $authMember || $authMember->role !== 'admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $groupMember->request_status = 'approved';
        $groupMember->status = 1;
        $groupMember->save();

        // Remove from other groups
        CourseGroupMember::where('course_id', $course->id)
            ->where('user_id', $groupMember->user_id)
            ->where('group_id', '!=', $group->id)
            ->delete();

        // Update CourseMember
        $courseMember = CourseMember::where('course_id', $course->id)->where('user_id', $groupMember->user_id)->first();
        if ($courseMember) {
            $price = $course->tuition_fees ?? $course->price ?? 0;
            $courseMember->group_id = $group->id;
            $courseMember->group_member_status = 1;
            $courseMember->course_member_status = 1;
            $courseMember->status = ($price <= 0) ? 1 : 0;
            $courseMember->save();
        }

        return response()->json(['success' => true, 'message' => 'อนุมัติสมาชิกเรียบร้อยแล้ว']);
    }

    public function rejectRequest(Course $course, CourseGroup $group, $memberId)
    {
        $groupMember = CourseGroupMember::findOrFail($memberId);

        // Authorization check
        $authMember = CourseGroupMember::where('group_id', $group->id)->where('user_id', auth()->id())->first();
        $isCourseAdmin = $course->isAdmin(auth()->user());

        if (! $isCourseAdmin && (! $authMember || $authMember->role !== 'admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $groupMember->request_status = 'rejected';
        $groupMember->status = 0;
        $groupMember->save();
        // Or delete? Let's keep as rejected for history or delete.
        // Usually reject means remove.
        $groupMember->delete();

        return response()->json(['success' => true, 'message' => 'ปฏิเสธคำขอเรียบร้อยแล้ว']);
    }

    public function getRequesters(Course $course, CourseGroup $group)
    {
        // Authorization check
        $isCourseAdmin = $course->isAdmin(auth()->user());
        $authMember = CourseGroupMember::where('group_id', $group->id)->where('user_id', auth()->id())->first();

        if (! $isCourseAdmin && (! $authMember || $authMember->role === 'member')) {
            // Members can't see requesters
            // Unless public? No.
            if ($authMember && $authMember->role !== 'admin' && $authMember->role !== 'moderator') {
                return response()->json(['data' => []]);
            }
        }

        $requesters = CourseGroupMember::where('group_id', $group->id)
            ->where('request_status', 'pending')
            ->with('user')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requesters,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(CourseGroupMember $courseGroupMember)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CourseGroupMember $courseGroupMember)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CourseGroupMember $courseGroupMember)
    {
        //
    }

    /**
     * Remove a member from the group (admin / course owner action).
     *
     * The route param is the target member's user_id — this is what the group
     * members list on the frontend carries (CourseGroupResource exposes user_id
     * for every member). Keying on user_id avoids the id-type mismatch between
     * course_members.id, course_group_members.id and users.id.
     */
    public function destroy(Course $course, CourseGroup $group, $userId)
    {
        // Authorization: course admin/owner OR group admin
        $isCourseAdmin = $course->isAdmin(auth()->user());
        $authMember = CourseGroupMember::where('group_id', $group->id)->where('user_id', auth()->id())->first();

        if (! $isCourseAdmin && (! $authMember || $authMember->role !== 'admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $removed = DB::transaction(function () use ($course, $group, $userId) {
            // Remove the group membership row (both approved and pending requests)
            $deletedGroupMembers = CourseGroupMember::where('course_id', $course->id)
                ->where('group_id', $group->id)
                ->where('user_id', $userId)
                ->delete();

            // Detach the CourseMember from this group (keep them in the course)
            $updatedCourseMembers = CourseMember::where('course_id', $course->id)
                ->where('user_id', $userId)
                ->where('group_id', $group->id)
                ->update(['group_id' => null, 'group_member_status' => 0]);

            return $deletedGroupMembers > 0 || $updatedCourseMembers > 0;
        });

        if (! $removed) {
            return response()->json(['success' => false, 'message' => 'ไม่พบสมาชิกในกลุ่มนี้'], 404);
        }

        return response()->json(['success' => true, 'message' => 'ลบสมาชิกเรียบร้อยแล้ว']);
    }

    public function leave(Course $course, CourseGroup $group)
    {
        $userId = auth()->id();

        $left = DB::transaction(function () use ($course, $group, $userId) {
            // Remove the group membership row (approved or pending)
            $deletedGroupMembers = CourseGroupMember::where('course_id', $course->id)
                ->where('group_id', $group->id)
                ->where('user_id', $userId)
                ->delete();

            // Detach the CourseMember from this group (keep them in the course)
            $updatedCourseMembers = CourseMember::where('course_id', $course->id)
                ->where('user_id', $userId)
                ->where('group_id', $group->id)
                ->update(['group_id' => null, 'group_member_status' => 0]);

            return $deletedGroupMembers > 0 || $updatedCourseMembers > 0;
        });

        if (! $left) {
            return response()->json(['success' => false, 'message' => 'คุณไม่ได้เป็นสมาชิกในกลุ่มนี้'], 404);
        }

        return response()->json(['success' => true, 'message' => 'ออกจากกลุ่มเรียบร้อยแล้ว']);
    }
}
