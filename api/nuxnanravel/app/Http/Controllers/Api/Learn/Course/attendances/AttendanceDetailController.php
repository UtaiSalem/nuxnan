<?php

namespace App\Http\Controllers\Api\Learn\Course\attendances;

use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Course\attendances\AttendanceDetailResource;
use App\Http\Resources\Learn\Course\attendances\CourseAttendanceResource;
use App\Models\AttendanceDetail;
use App\Models\CourseAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
     * Uses updateOrCreate to prevent duplicate records for same member and attendance session
     */
    public function store(CourseAttendance $attendance, Request $request)
    {
        // Check if record already exists for this session and member
        $existingDetail = AttendanceDetail::where('course_attendance_id', $attendance->id)
            ->where('course_member_id', $request->course_member_id)
            ->first();

        if ($existingDetail) {
            // Return existing record without creating duplicate
            return response()->json([
                'success' => true,
                'message' => 'Already checked in',
                'attendance_detail' => new AttendanceDetailResource($existingDetail),
                'already_exists' => true,
            ], 200);
        }

        // Compute if late by comparing with attendance start time + late threshold
        $isLate = now() > Carbon::parse($attendance->start_at)->addMinutes($attendance->late_time);

        $detail = $attendance->details()->create([
            'course_attendance_id' => $attendance->id,
            'course_id' => $attendance->course_id,
            'group_id' => $attendance->group_id,
            'course_member_id' => $request->course_member_id,
            'time_in' => now(),
            'status' => $isLate ? 2 : 1,
            'comments' => $request->comments,
        ]);

        return response()->json([
            'success' => true,
            'attendance_detail' => new AttendanceDetailResource($detail),
            'already_exists' => false,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AttendanceDetail $detail, Request $request)
    {
        $detail->update([
            'status' => $request->status,
            'comments' => $request->comments,
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
                'title' => $attendance->description,
                'start_at' => $attendance->start_at,
                'finish_at' => $attendance->finish_at,
                'late_time' => $attendance->late_time,
            ],
        ], 200);
    }
}
