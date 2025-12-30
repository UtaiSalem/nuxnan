<?php

namespace App\Http\Controllers\Api\Learn\Course\groups;

use App\Http\Controllers\Controller;

use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\CourseMember;
use Illuminate\Http\Request;
use App\Models\CourseGroupMember;
use App\\Http\\Resources\\Learn\\Course\\groups\\CourseGroupResource;

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
        if (auth()->user()->pp < $course->tuition_fees) {
            return response()->json([
                'success' => false,
                'msg'     => 'แต้มสะสมไม่เพียงพอ กรุณาเติมแต้มสะสมก่อนสมัครสมาชิก'
            ], 201);
        }

        $course_member = CourseMember::where('course_id', $course->id)
                                    ->where('user_id', auth()->id())
                                    ->first();

        if (!$course_member) {
            $new_course_member = new CourseMember();
            $new_course_member->user_id                 = auth()->id();
            $new_course_member->course_id               = $course->id;
            $new_course_member->course_member_status    = $course->courseSettings->auto_accept_members;
            $new_course_member->group_id                = $group->id;
            $new_course_member->group_member_status     = $group->auto_accept_member;
            $new_course_member->save();
            $new_course_member->refresh();

            // Observer will handle enrolled_students increment

            $newCourseGroupMember = new CourseGroupMember();
            $newCourseGroupMember->user_id      = auth()->id();
            $newCourseGroupMember->course_id    = $new_course_member->course_id;
            $newCourseGroupMember->group_id     = $group->id;
            $newCourseGroupMember->status       = $group->auto_accept_member;
            $newCourseGroupMember->save();

        }else{           
            $course_member->course_member_status    = $course->courseSettings->auto_accept_members === 0 ? 0 : 1;
            $course_member->group_id                = $group->id;
            $course_member->group_member_status     = $group->auto_accept_member === 0 ? 0: 1;
            $course_member->save();
            $course_member->refresh();
        }

        return response()->json([
            'success' => true,
            'courseMemberOfAuth'    => $course->courseMembers()->where('user_id', auth()->id())->first(),
            'group'                 => new CourseGroupResource($group),
        ], 200);
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
     * Remove the specified resource from storage.
     */
    public function destroy(CourseGroupMember $courseGroupMember)
    {
        //
    }

    public function unMemberGroup(Course $course, CourseGroup $group, CourseMember $member)
    {
        $member->group_id               = null;
        $member->group_member_status    = 0;
        $member->save();
        $member->refresh();

        // $courseGroupMember = CourseGroupMember::where('group_id', $group->id)->where('user_id', auth()->id())->first();
        // $courseGroupMember->group_id    = null;
        // $courseGroupMember->status      = 0;
        // $courseGroupMember->save();

        return response()->json([
            'success'       => true,
            'courseMember'  => $member,
            'group'         => new CourseGroupResource($group),
        ], 200);
    }
}
