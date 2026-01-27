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
     * List pending invitations for the authenticated user.
     */
    public function myInvitations(Request $request)
    {
        $invitations = CourseInvitation::where('invitee_id', auth()->id())
            ->where('status', 'pending')
            ->with(['course', 'inviter'])
            ->latest()
            ->get()
            ->map(function ($invitation) {
                return [
                    'id' => $invitation->id,
                    'course' => [
                        'id' => $invitation->course->id,
                        'name' => $invitation->course->name,
                        'cover' => $invitation->course->cover,
                    ],
                    'inviter' => [
                        'id' => $invitation->inviter->id,
                        'name' => $invitation->inviter->name,
                        'avatar' => $invitation->inviter->profile_photo_url,
                    ],
                    'role' => $invitation->role,
                    'role_name' => $invitation->role === 3 ? 'ผู้ช่วยสอน (TA)' : 'ผู้ดูแลระบบ (Admin)',
                    'created_at' => $invitation->created_at,
                    'time_ago' => $invitation->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'invitations' => $invitations,
        ]);
    }

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
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'user' => $member->user,
                    'role' => $member->role,
                    'role_name' => $member->role === 3 ? 'ผู้ช่วยสอน' : 'ผู้ดูแลระบบ',
                    'permissions' => $member->getPermissions(),
                    'created_at' => $member->created_at,
                ];
            });

        // Also get pending invitations
        $invitations = CourseInvitation::where('course_id', $course->id)
            ->where('status', 'pending')
            ->with('invitee')
            ->get()
            ->map(function ($invitation) {
                return [
                    'id' => $invitation->id,
                    'invitee' => $invitation->invitee,
                    'role' => $invitation->role,
                    'role_name' => $invitation->role === 3 ? 'ผู้ช่วยสอน' : 'ผู้ดูแลระบบ',
                    'permissions' => json_decode($invitation->permissions ?? '[]', true),
                    'created_at' => $invitation->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'admins' => $admins,
            'invitations' => $invitations,
            'available_permissions' => \App\Models\CoursePermission::PERMISSIONS,
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
     * Invite a user as Admin or TA with specific permissions.
     */
    public function store(Request $request, Course $course)
    {
        if (!$course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:3,4', // 3: Teacher/TA, 4: Admin
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:' . implode(',', array_keys(\App\Models\CoursePermission::PERMISSIONS)),
        ]);

        $userId = $request->user_id;
        $role = $request->role;
        $permissions = $request->permissions ?? [];

        // Check if already a member
        if ($course->courseMembers()->where('user_id', $userId)->exists()) {
            return response()->json(['message' => 'User is already a member of this course.'], 422);
        }

        // Check if already invited
        if (CourseInvitation::where('course_id', $course->id)->where('invitee_id', $userId)->where('status', 'pending')->exists()) {
              return response()->json(['message' => 'User already has a pending invitation.'], 422);
        }

        // Store permissions in invitation for later assignment
        $invitation = CourseInvitation::create([
            'course_id' => $course->id,
            'inviter_id' => auth()->id(),
            'invitee_id' => $userId,
            'status' => 'pending',
            'role' => $role,
            'permissions' => json_encode($permissions),
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
            return response()->json(['message' => 'Invitation is not valid'], 400);
        }

        DB::transaction(function () use ($invitation, $course) {
            $invitation->update(['status' => 'accepted']);

            // Create Course Member with specified role
            $member = CourseMember::create([
                'course_id' => $course->id,
                'user_id' => $invitation->invitee_id,
                'role' => $invitation->role ?? 4, // Default to Admin
                'status' => 1, // Active
                'course_member_status' => 1,
                'enrollment_date' => now(),
            ]);

            // Assign permissions
            $permissions = json_decode($invitation->permissions ?? '[]', true);
            if (!empty($permissions)) {
                foreach ($permissions as $permission) {
                    \App\Models\CoursePermission::create([
                        'course_member_id' => $member->id,
                        'permission' => $permission,
                        'granted_by' => $invitation->inviter_id,
                    ]);
                }
            } else {
                // Grant default permissions based on role
                $member->grantDefaultPermissions();
            }
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
     * Update permissions for an admin/TA.
     */
    public function updatePermissions(Request $request, Course $course, CourseMember $member)
    {
        if (!$course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Can only manage permissions for admins/TAs, not the owner
        if ($member->user_id == $course->user_id) {
            return response()->json(['success' => false, 'message' => 'Cannot modify owner permissions.'], 403);
        }

        if (!in_array($member->role, [3, 4])) {
            return response()->json(['success' => false, 'message' => 'Member is not an admin or TA.'], 403);
        }

        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|in:' . implode(',', array_keys(\App\Models\CoursePermission::PERMISSIONS)),
        ]);

        DB::transaction(function () use ($member, $request) {
            // Remove existing permissions
            $member->permissions()->delete();

            // Add new permissions
            foreach ($request->permissions as $permission) {
                \App\Models\CoursePermission::create([
                    'course_member_id' => $member->id,
                    'permission' => $permission,
                    'granted_by' => auth()->id(),
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Permissions updated successfully.',
            'permissions' => $request->permissions,
        ]);
    }

    /**
     * Get permissions for a specific admin/TA.
     */
    public function getPermissions(Course $course, CourseMember $member)
    {
        if (!$course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $permissions = $member->getPermissions();
        $availablePermissions = \App\Models\CoursePermission::PERMISSIONS;

        return response()->json([
            'success' => true,
            'member' => [
                'id' => $member->id,
                'user' => $member->user,
                'role' => $member->role,
            ],
            'permissions' => $permissions,
            'available_permissions' => $availablePermissions,
        ]);
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

        // Clean up permissions before deleting member
        $member->permissions()->delete();
        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'Removed successfully.',
        ]);
    }
}
