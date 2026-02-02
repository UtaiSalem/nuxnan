<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;


use App\Models\Academy;
use Illuminate\Http\Request;
use App\Models\AcademyMember;
use App\Http\Resources\Learn\Course\info\CourseResource;
use App\Http\Resources\Learn\Academy\AcademyResource;

use App\Http\Resources\Learn\Academy\AcademyMemberResource;

class AcademyMemberController extends Controller
{
    //index
    public function index(Academy $academy)
    {
        $courses = $academy->courses;
        $coursesresource = CourseResource::collection($courses);
        $isAcademyAdmin = $academy->user_id == auth()->id();
        
        return response()->json([
            // 'authMemberCourses' => $authMemberCourses,
            'allCourses'        => $coursesresource,
            'courses'           => $coursesresource,
            'authOwnerCourses'  => CourseResource::collection(auth()->user()->courses),
            'authMemberCourses' => [],
            'academy'           => new AcademyResource($academy),
            'isAcademyAdmin'    => $isAcademyAdmin,
        ]);
    }
    public function storemember(Academy $academy)
    {   
        if (auth()->user()->pp < $academy->membership_fees_points) {
            return response()->json([
                'success' => false,
                'msg'     => 'แต้มสะสมไม่เพียงพอ กรุณาเติมแต้มสะสมก่อนสมัครสมาชิก'
            ], 201);
        }

        $curent_member_status = AcademyMember::where('academy_id', $academy->id)->where('user_id', auth()->id())->first();

        if ($academy->academySetting->auto_accept_members === 1) {
            if (!$curent_member_status) {
                $newStatus = $academy->academyMembers()->create([
                    'user_id'   => auth()->id(),
                    'status'    => 2, 
                ]);
                $academy->increment('total_students');
            }
        }else {
            if (!$curent_member_status) {
                $newStatus = $academy->academyMembers()->create([
                    'user_id'   => auth()->id(),
                    'status'    => 1, 
                ]);
            }
        }
        
        // $academy->members()->toggle(auth()->id());
        // $isMember = $academy->isMember(auth()->user());
        // $isMember ? $academy->increment('total_students'): $academy->decrement('total_students');

        return response()->json([
            'success' => true,
            'memberStatus'  => $newStatus->status,
            'totalStudents' => $academy->total_students,
        ], 200);
    }


    public function unmember(Academy $academy)
    {   
        $auth_member = AcademyMember::where('academy_id', $academy->id)
            ->where('user_id', auth()->id())
            ->first();
            
        if (!$auth_member) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่ได้เป็นสมาชิกของโรงเรียนนี้'
            ], 404);
        }
        
        // Check if member is approved (status 2) before decrementing
        if ($auth_member->status == 2) {
            $academy->decrement('total_students');
        }

        // Only delete the current user's membership, not all members
        $auth_member->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'ยกเลิกการเป็นสมาชิกเรียบร้อยแล้ว'
        ], 200);
    }

    public function acceptmember(Academy $academy, AcademyMember $member)
    {
        $member->update([
            'status' => 2,
        ]);
        $academy->increment('total_students');
        return response()->json([
            'success' => true,
            'memberStatus'  => $member->status,
            'totalStudents' => $academy->total_students,
        ], 200);
    }

    public function rejectmember(Academy $academy, AcademyMember $member)
    {
        $member->update([
            'status' => 3,
        ]);
        $academy->decrement('total_students');
        return response()->json([
            'success' => true,
            'memberStatus'  => $member->status,
            'totalStudents' => $academy->total_students,
        ], 200);
    }

    public function memberstatus(Academy $academy)
    {
        $member = AcademyMember::where('academy_id', $academy->id)->where('user_id', auth()->id())->first();
        return response()->json([
            'success' => true,
            'memberStatus'  => $member->status,
        ], 200);
    }

    public function memberlist(Academy $academy)
    {
        $members = $academy->academyMembers()->with(['user', 'student'])->get();
        return response()->json([
            'success' => true,
            'members'  => AcademyMemberResource::collection($members),
        ], 200);
    }

    public function membercount(Academy $academy)
    {
        $members = $academy->academyMembers()->count();
        return response()->json([
            'success' => true,
            'totalStudents'  => $members,
        ], 200);
    }

    public function getAcademyMembers(Academy $academy) {
        $perPage = request()->get('per_page', 20);
        $members = $academy->academyMembers()->with(['user', 'student'])->paginate($perPage);

        return response()->json([
            'success' => true,
            'members'  => AcademyMemberResource::collection($members),
            'pagination' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
            ],
        ], 200);
    }

    /**
     * Invite a user to join the academy
     */
    public function inviteMember(Academy $academy, Request $request)
    {
        // Check if the current user is an admin of this academy
        if ($academy->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์เชิญสมาชิก'
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $userId = $request->user_id;

        // Check if user is already a member
        $existingMember = AcademyMember::where('academy_id', $academy->id)
            ->where('user_id', $userId)
            ->first();

        if ($existingMember) {
            return response()->json([
                'success' => false,
                'message' => 'ผู้ใช้นี้เป็นสมาชิกหรือถูกเชิญอยู่แล้ว'
            ], 422);
        }

        // Create invitation (status 4 = invited)
        $invitation = $academy->academyMembers()->create([
            'user_id' => $userId,
            'status' => 4, // 4 = invited
            'invited_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ส่งคำเชิญเรียบร้อยแล้ว',
            'invitation' => $invitation,
        ], 200);
    }

    /**
     * Accept an invitation to join academy
     */
    public function acceptInvitation(Academy $academy)
    {
        $invitation = AcademyMember::where('academy_id', $academy->id)
            ->where('user_id', auth()->id())
            ->where('status', 4) // invited status
            ->first();

        if (!$invitation) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบคำเชิญ'
            ], 404);
        }

        $invitation->update([
            'status' => 2, // member status
        ]);
        $academy->increment('total_students');

        return response()->json([
            'success' => true,
            'message' => 'ยอมรับคำเชิญเรียบร้อยแล้ว',
            'memberStatus' => 2,
            'totalStudents' => $academy->total_students,
        ], 200);
    }

    /**
     * Decline an invitation to join academy
     */
    public function declineInvitation(Academy $academy)
    {
        $invitation = AcademyMember::where('academy_id', $academy->id)
            ->where('user_id', auth()->id())
            ->where('status', 4) // invited status
            ->first();

        if (!$invitation) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบคำเชิญ'
            ], 404);
        }

        $invitation->delete();

        return response()->json([
            'success' => true,
            'message' => 'ปฏิเสธคำเชิญเรียบร้อยแล้ว',
        ], 200);
    }

    /**
     * Get pending invitations for current user
     */
    public function getMyInvitations()
    {
        $invitations = AcademyMember::where('user_id', auth()->id())
            ->where('status', 4) // invited status
            ->with(['academy' => function($query) {
                $query->select('id', 'name', 'logo', 'slogan', 'type');
            }])
            ->get();

        return response()->json([
            'success' => true,
            'invitations' => $invitations,
        ], 200);
    }

    /**
     * Get pending requests (for admin) - users who requested to join
     */
    public function getPendingRequests(Academy $academy)
    {
        // Check if the current user is an admin of this academy
        if ($academy->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์ดูข้อมูลนี้'
            ], 403);
        }

        $pendingRequests = AcademyMember::where('academy_id', $academy->id)
            ->where('status', 1) // pending status
            ->with('user:id,name,email,profile_photo_path,reference_code')
            ->get();

        return response()->json([
            'success' => true,
            'pendingRequests' => $pendingRequests,
        ], 200);
    }

    /**
     * Get academy members with search, filter, and pagination
     */
    public function searchMembers(Academy $academy, Request $request)
    {
        $query = AcademyMember::where('academy_id', $academy->id);

        // Search by name, email, or member code
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('reference_code', 'LIKE', "%{$search}%");
                })
                ->orWhereHas('student', function ($studentQuery) use ($search) {
                    $studentQuery->where('first_name_th', 'LIKE', "%{$search}%")
                        ->orWhere('last_name_th', 'LIKE', "%{$search}%")
                        ->orWhere('first_name_en', 'LIKE', "%{$search}%")
                        ->orWhere('last_name_en', 'LIKE', "%{$search}%")
                        ->orWhere('student_id', 'LIKE', "%{$search}%");
                })
                ->orWhere('member_code', 'LIKE', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        // Filter by role
        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        // Filter by academy_role_id
        if ($request->has('academy_role_id') && $request->academy_role_id) {
            $query->where('academy_role_id', $request->academy_role_id);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 20), 100);
        $members = $query->with(['user', 'student', 'academyRole', 'inviter'])->paginate($perPage);

        return response()->json([
            'success' => true,
            'members' => AcademyMemberResource::collection($members),
            'pagination' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
            ],
        ], 200);
    }

    /**
     * Remove a member from the academy (admin only)
     */
    public function removeMember(Academy $academy, AcademyMember $member)
    {
        // Check if the current user has permission to remove members
        if (!$this->canManageMembers($academy)) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์ลบสมาชิก'
            ], 403);
        }

        // Check if member belongs to this academy
        if ($member->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'สมาชิกไม่ได้อยู่ในโรงเรียนนี้'
            ], 404);
        }

        // Cannot remove the owner
        if ($member->user_id === $academy->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบเจ้าของโรงเรียนได้'
            ], 403);
        }

        // Decrement total_students if member was approved
        if ($member->status == 2) {
            $academy->decrement('total_students');
        }

        $memberName = $member->member_name;
        $member->delete();

        return response()->json([
            'success' => true,
            'message' => "ลบสมาชิก {$memberName} เรียบร้อยแล้ว",
            'totalStudents' => $academy->total_students,
        ], 200);
    }

    /**
     * Suspend a member
     */
    public function suspendMember(Academy $academy, AcademyMember $member, Request $request)
    {
        if (!$this->canManageMembers($academy)) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์ระงับสมาชิก'
            ], 403);
        }

        if ($member->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'สมาชิกไม่ได้อยู่ในโรงเรียนนี้'
            ], 404);
        }

        // Cannot suspend the owner
        if ($member->user_id === $academy->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถระงับเจ้าของโรงเรียนได้'
            ], 403);
        }

        $previousStatus = $member->status;
        $member->update([
            'status' => 5, // 5 = suspended
            'note_comment' => $request->get('reason', 'ถูกระงับโดยผู้ดูแล'),
        ]);

        // Decrement if previously approved
        if ($previousStatus == 2) {
            $academy->decrement('total_students');
        }

        return response()->json([
            'success' => true,
            'message' => 'ระงับสมาชิกเรียบร้อยแล้ว',
            'member' => new AcademyMemberResource($member->load(['user', 'student', 'academyRole'])),
        ], 200);
    }

    /**
     * Unsuspend (reactivate) a member
     */
    public function unsuspendMember(Academy $academy, AcademyMember $member)
    {
        if (!$this->canManageMembers($academy)) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์ยกเลิกการระงับสมาชิก'
            ], 403);
        }

        if ($member->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'สมาชิกไม่ได้อยู่ในโรงเรียนนี้'
            ], 404);
        }

        if ($member->status !== 5) {
            return response()->json([
                'success' => false,
                'message' => 'สมาชิกไม่ได้ถูกระงับ'
            ], 422);
        }

        $member->update([
            'status' => 2, // 2 = approved member
            'note_comment' => null,
        ]);

        $academy->increment('total_students');

        return response()->json([
            'success' => true,
            'message' => 'ยกเลิกการระงับสมาชิกเรียบร้อยแล้ว',
            'member' => new AcademyMemberResource($member->load(['user', 'student', 'academyRole'])),
            'totalStudents' => $academy->total_students,
        ], 200);
    }

    /**
     * Update member details (note, enrollment date, etc.)
     */
    public function updateMember(Academy $academy, AcademyMember $member, Request $request)
    {
        if (!$this->canManageMembers($academy)) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์แก้ไขข้อมูลสมาชิก'
            ], 403);
        }

        if ($member->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'สมาชิกไม่ได้อยู่ในโรงเรียนนี้'
            ], 404);
        }

        $validated = $request->validate([
            'member_code' => 'nullable|string|max:50',
            'note_comment' => 'nullable|string|max:500',
            'enrollment_date' => 'nullable|date',
            'graduation_date' => 'nullable|date',
            'additional_info' => 'nullable|string',
        ]);

        $member->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทข้อมูลสมาชิกเรียบร้อยแล้ว',
            'member' => new AcademyMemberResource($member->load(['user', 'student', 'academyRole'])),
        ], 200);
    }

    /**
     * Get member statistics for the academy
     */
    public function getMemberStats(Academy $academy)
    {
        $stats = [
            'total' => AcademyMember::where('academy_id', $academy->id)->count(),
            'approved' => AcademyMember::where('academy_id', $academy->id)->where('status', 2)->count(),
            'pending' => AcademyMember::where('academy_id', $academy->id)->where('status', 1)->count(),
            'invited' => AcademyMember::where('academy_id', $academy->id)->where('status', 4)->count(),
            'rejected' => AcademyMember::where('academy_id', $academy->id)->where('status', 3)->count(),
            'suspended' => AcademyMember::where('academy_id', $academy->id)->where('status', 5)->count(),
        ];

        // Get role distribution
        $roleDistribution = AcademyMember::where('academy_id', $academy->id)
            ->where('status', 2)
            ->selectRaw('role, count(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role')
            ->toArray();

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'role_distribution' => $roleDistribution,
        ], 200);
    }

    /**
     * Check if current user can manage members in the academy
     */
    private function canManageMembers(Academy $academy): bool
    {
        $user = auth()->user();

        // Owner can manage everything
        if ($academy->user_id === $user->id) {
            return true;
        }

        // Admin can manage members
        if ($academy->academyAdmins()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Check member permission
        $member = AcademyMember::where('academy_id', $academy->id)
            ->where('user_id', $user->id)
            ->with('academyRole')
            ->first();

        if ($member && $member->academyRole) {
            return $member->hasPermission('members.manage');
        }

        return false;
    }

    /**
     * Bulk invite members to the academy
     * Accepts user IDs and/or email addresses
     */
    public function bulkInviteMembers(Academy $academy, Request $request)
    {
        // Check permission
        if (!$this->canManageMembers($academy)) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์เชิญสมาชิก'
            ], 403);
        }

        $request->validate([
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'emails' => 'nullable|array',
            'emails.*' => 'email',
            'role' => 'nullable|string|in:student,parent,teacher,staff,admin',
        ]);

        $userIds = $request->user_ids ?? [];
        $emails = $request->emails ?? [];
        $role = $request->role ?? 'student';
        
        $invitedCount = 0;
        $skippedCount = 0;
        $errors = [];

        // Invite by user IDs
        foreach ($userIds as $userId) {
            $existingMember = AcademyMember::where('academy_id', $academy->id)
                ->where('user_id', $userId)
                ->first();

            if ($existingMember) {
                $skippedCount++;
                continue;
            }

            AcademyMember::create([
                'academy_id' => $academy->id,
                'user_id' => $userId,
                'role' => $role,
                'status' => 4, // invited
                'invited_by' => auth()->id(),
                'invited_at' => now(),
            ]);
            $invitedCount++;
        }

        // Invite by emails (create invitation or find existing user)
        foreach ($emails as $email) {
            $user = \App\Models\User::where('email', $email)->first();
            
            if ($user) {
                // User exists, check if already member
                $existingMember = AcademyMember::where('academy_id', $academy->id)
                    ->where('user_id', $user->id)
                    ->first();

                if ($existingMember) {
                    $skippedCount++;
                    continue;
                }

                AcademyMember::create([
                    'academy_id' => $academy->id,
                    'user_id' => $user->id,
                    'role' => $role,
                    'status' => 4, // invited
                    'invited_by' => auth()->id(),
                    'invited_at' => now(),
                ]);
                $invitedCount++;
            } else {
                // TODO: Send email invitation to non-existing user
                // For now, just skip and report
                $errors[] = "อีเมล {$email} ไม่พบในระบบ";
                $skippedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "ส่งคำเชิญเรียบร้อย {$invitedCount} คน",
            'invited_count' => $invitedCount,
            'skipped_count' => $skippedCount,
            'errors' => $errors,
        ], 200);
    }
}
