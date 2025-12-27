<?php

namespace App\Http\Controllers\Api\Learn\Course\attendances;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\AttendanceDetail;
use App\Models\CourseAttendance;
use App\Http\Resources\Learn\attendances\AttendanceDetailResource;
use App\Http\Resources\Learn\attendances\CourseAttendanceResource;

class AttendanceDetailController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(CourseAttendance $attendance, Request $request)
    {
        return response()->json([
            'success' => true,
            'attendance_resource' => new CourseAttendanceResource($attendance),
        ], 200);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(CourseAttendance $attendance, Request $request)
    {
        // compute time in is late or not by compare with attendance finish time+20 minutes
        $isLate = now() > Carbon::parse($attendance->start_at)->addMinutes($attendance->late_time);

        $detail = $attendance->details()->create([
            'course_attendance_id'  => $attendance->id,
            'course_id'             => $attendance->course_id,
            'group_id'              => $attendance->group_id,
            'course_member_id'      => $request->course_member_id,
            'time_in'               => now(),    
            'status'                => $isLate ? 2 : 1,
            'comments'              => $request->comments,
        ]);


        return response()->json([
            'success' => true,
            'attendance_detail' => new AttendanceDetailResource($detail),
        ], 200);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(AttendanceDetail $detail, Request $request)
    {
        $detail->update([
            'status'    => $request->status,
            'comments'  => $request->comments,
        ]);


        return response()->json([
            'success' => true,
            'attendance_detail' => new AttendanceDetailResource($detail),
        ], 200);
    }


    /**
     * Get attendance details for all members in a group attendance
     */
    public function getGroupMembersAttendanceDetails(CourseAttendance $attendance)
    {
        $details = $attendance->details()
            ->with('courseMember.user')
            ->get()
            ->map(function ($detail) {
                return [
                    'course_member_id' => $detail->course_member_id,
                    'member_name' => $detail->courseMember->member_name ?? $detail->courseMember->user->name,
                    'user' => [
                        'id' => $detail->courseMember->user->id,
                        'name' => $detail->courseMember->user->name,
                        'avatar' => $detail->courseMember->user->avatar,
                    ],
                    'status' => $detail->status,
                    'time_in' => $detail->time_in,
                    'comments' => $detail->comments,
                    'order_number' => $detail->courseMember->order_number,
                    'group' => $detail->courseMember->group ? [
                        'id' => $detail->courseMember->group->id,
                        'name' => $detail->courseMember->group->name,
                    ] : null,
                ];
            });

        return response()->json([
            'success' => true,
            'attendance_id' => $attendance->id,
            'details' => $details,
            'attendance_info' => [
                'id' => $attendance->id,
                'title' => $attendance->title,
                'start_at' => $attendance->start_at,
                'finish_at' => $attendance->finish_at,
                'late_time' => $attendance->late_time,
            ]
        ], 200);
    }

}
