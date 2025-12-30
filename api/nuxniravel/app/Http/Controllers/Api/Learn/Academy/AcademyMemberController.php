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
}
