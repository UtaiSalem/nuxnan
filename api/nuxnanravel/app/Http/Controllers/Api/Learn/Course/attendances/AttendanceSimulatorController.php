<?php

namespace App\Http\Controllers\Api\Learn\Course\attendances;

use App\Http\Controllers\Controller;
use App\Models\CourseAttendance;
use Illuminate\Http\JsonResponse;

class AttendanceSimulatorController extends Controller
{
    /**
     * Show the classroom seat simulator for a given attendance session.
     *
     * Returns a dense seat map for all members in the attendance's group,
     * ordered by order_number (null last), with their current attendance
     * status. The `arriving` state is derived client-side from checked_in_at
     * + grace window relative to server_time.
     *
     * Authorization:
     *   - Course admins may view any attendance in their course.
     *   - Students may only view attendances in their own group.
     */
    public function show(CourseAttendance $attendance): JsonResponse
    {
        $user = auth()->user();
        $course = $attendance->course;
        $isCourseAdmin = $course->isAdmin($user);
        $authMember = $course->courseMembers()->where('user_id', $user->id)->first();

        // Authorize
        if (! $isCourseAdmin && (! $authMember || $authMember->group_id !== $attendance->group_id)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์เข้าถึง',
            ], 403);
        }

        // Load group members with user info, ordered by order_number (null last)
        $members = $course->courseMembers()
            ->where('group_id', $attendance->group_id)
            ->with('user:id,name,profile_photo_path')
            ->orderByRaw('COALESCE(order_number, 2147483647), order_number ASC, id ASC')
            ->get();

        // Load attendance details via hasMany (more reliable than morphMany)
        $details = $attendance->attendanceDetails()
            ->get()
            ->keyBy('course_member_id');

        // Build dense seat map
        $seats = $members->values()->map(function ($member, $index) use ($details) {
            $detail = $details->get($member->id);

            return [
                'seat_number' => $index + 1,
                'order_number' => $member->order_number,
                'course_member_id' => $member->id,
                'member_code' => $member->member_code,
                'name' => $member->member_name ?? $member->user?->name,
                'avatar' => $member->user?->avatar,
                'status' => $detail?->status ?? 0,   // 0=absent, 1=present, 2=late, 3=leave
                'time_in' => $detail?->time_in,
                'checked_in_at' => $detail?->updated_at?->toIso8601String(),
            ];
        });

        $totalSeats = $seats->count();
        // Wider room for big groups so the grid doesn't get too tall (full width, no sidebar)
        $cols = $totalSeats > 20 ? 10 : 4;
        $rows = max(1, (int) ceil(max($totalSeats, 1) / $cols));

        return response()->json([
            'success' => true,
            'attendance' => [
                'id' => $attendance->id,
                'title' => $attendance->description,
                'start_at' => $attendance->start_at,
                'finish_at' => $attendance->finish_at,
                'late_time' => $attendance->late_time,
                'description' => $attendance->description,
                'group' => [
                    'id' => $attendance->group_id,
                    'name' => $attendance->group?->name,
                ],
            ],
            'layout' => [
                'cols' => $cols,
                'rows' => $rows,
                'total_seats' => max(20, $totalSeats),
            ],
            'seats' => $seats,
            'meta' => [
                'is_course_admin' => $isCourseAdmin,
                'auth_member_id' => $authMember?->id,
                'server_time' => now('Asia/Bangkok')->toIso8601String(),
            ],
        ]);
    }
}
