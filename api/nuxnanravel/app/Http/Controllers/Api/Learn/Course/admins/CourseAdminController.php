<?php

namespace App\Http\Controllers\Api\Learn\Course\admins;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\Learn\Course\members\CourseMemberResource;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\DB;

class CourseAdminController extends Controller
{
    /**
     * List all Admins and TAs for the course.
     */
    public function index(Course $course)
    {
        if (!$course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $admins = $course->courseMembers()
            ->whereIn('role', [3, 4]) // 3: Teacher (TA), 4: Admin
            ->orderBy('role', 'desc')
            ->with('user')
            ->get();
            
        // Also get pending invitations
        $invitations = CourseInvitation::where('course_id', $course->id)
            ->where('status', 'pending')
            ->with('invitee')
            ->get();

        return response()->json([
            'success' => true,
            'admins' => CourseMemberResource::collection($admins),
            'invitations' => $invitations,
        ]);
    }

    /**
     * Search for users to invite.
     * Exclude current members and pending invites.
     */
    public function search(Request $request, Course $course)
    {
        if (!$course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $query = $request->input('query');

        // IDs of users who are already members
        $existingMemberIds = $course->courseMembers()->pluck('user_id')->toArray();
        
        // IDs of pending invites
        $invitedIds = CourseInvitation::where('course_id', $course->id)
            ->where('status', 'pending')
            ->pluck('invitee_id')
            ->toArray();
            
        $excludeIds = array_merge($existingMemberIds, $invitedIds);

        $users = User::where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('reference_code', 'like', "%{$query}%");
            })
            ->whereNotIn('id', $excludeIds)
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'users' => UserResource::collection($users),
        ]);
    }

    /**
     * Invite a user as Admin or TA.
     */
    public function store(Request $request, Course $course)
    {
        if (!$course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            // 'role' => 'required|in:3,4', // 3: Teacher/TA, 4: Admin (Optional if we force admin)
        ]);

        $userId = $request->user_id;
        $role = $request->input('role', 4); // Default to Admin if not specified

        // Check if already a member
        if ($course->courseMembers()->where('user_id', $userId)->exists()) {
            return response()->json(['message' => 'User is already a member of this course.'], 422);
        }

        // Check if already invited
        if (CourseInvitation::where('course_id', $course->id)->where('invitee_id', $userId)->where('status', 'pending')->exists()) {
             return response()->json(['message' => 'User already has a pending invitation.'], 422);
        }

        $invitation = CourseInvitation::create([
            'course_id' => $course->id,
            'inviter_id' => auth()->id(),
            'invitee_id' => $userId,
            'status' => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Invitation sent successfully.',
            'invitation' => $invitation,
        ]);
    }

    public function acceptInvitation(Course $course, CourseInvitation $invitation)
    {
        if (auth()->id() !== $invitation->invitee_id) {
             return response()->json(['success'=>false, 'message'=>'Unauthorized'], 403);
        }
        
        if ($invitation->status !== 'pending') {
            return response()->json(['message' => 'Invitation is valid'], 400);
        }

        DB::transaction(function () use ($invitation, $course) {
            $invitation->update(['status' => 'accepted']);
            
            // Create Course Member (Admin)
            CourseMember::create([
                'course_id' => $course->id,
                'user_id' => $invitation->invitee_id,
                'role' => 4, // Admin
                'status' => 1, // Active
                'course_member_status' => 1,
                'enrollment_date' => now(),
            ]);
        });
        
        return response()->json(['success'=>true, 'message'=>'Invitation accepted']);
    }

    public function declineInvitation(Course $course, CourseInvitation $invitation)
    {
        if (auth()->id() !== $invitation->invitee_id) {
             return response()->json(['success'=>false, 'message'=>'Unauthorized'], 403);
        }
        
        $invitation->update(['status' => 'declined']);
        $invitation->delete(); // Or keep as declined? Let's delete for now to allow re-invite.
        
        return response()->json(['success'=>true, 'message'=>'Invitation declined']);
    }
    
    public function cancelInvitation(Course $course, CourseInvitation $invitation)
    {
        if (!$course->isAdmin(auth()->user()) && $invitation->inviter_id !== auth()->id()) {
             return response()->json(['success'=>false, 'message'=>'Unauthorized'], 403);
        }
        
        $invitation->delete();
        
        return response()->json(['success'=>true, 'message'=>'Invitation cancelled']);
    }

    /**
     * Remove admin/TA.
     */
    public function destroy(Course $course, CourseMember $member)
    {
         // Verify admin permissions
         if (!$course->isAdmin(auth()->user())) {
             return response()->json(['success'=>false, 'message'=>'Unauthorized'], 403);
         }

        // Prevent removing the owner
        if ($member->user_id == $course->user_id) {
             return response()->json(['success'=>false, 'message'=>'Cannot remove owner.'], 403);
        }
        
        // Prevent removing yourself? (optional)
        if ($member->user_id == auth()->id()) {
             // Maybe allow "Leave Course"? But destroy implies admin action.
             // If leaving, use 'leave' endpoint if exists.
        }

        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'Removed successfully.',
        ]);
    }
}
