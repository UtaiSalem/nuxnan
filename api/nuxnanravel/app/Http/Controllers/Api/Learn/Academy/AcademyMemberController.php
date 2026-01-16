<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;


use App\Models\Academy;
use Illuminate\Http\Request;
use App\Models\AcademyMember;
use App\Http\Resources\Learn\Course\info\CourseResource;
use App\Http\Resources\Learn\Academy\AcademyResource;

class AcademyMemberController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

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
        $auth_academy = AcademyMember::where('academy_id', $academy->id)->where('user_id', auth()->id())->first();
        if ($auth_academy->status ==='2') {
            $academy->decrement('total_students');
        }

        $academy->academyMembers()->delete();
        return response()->json([
            'success' => true,
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
        $members = $academy->academyMembers()->with('user')->get();
        return response()->json([
            'success' => true,
            'members'  => $members,
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
        // $members = AcademyMember::where('user_id', auth()->id())->with('academy')->get();
        $members = $academy->members()->get();

        return response()->json([
            'success' => true,
            'members'  => $members,
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
            ->with('user:id,name,email,profile_photo_url,reference_code')
            ->get();

        return response()->json([
            'success' => true,
            'pendingRequests' => $pendingRequests,
        ], 200);
    }
}
