<?php

namespace App\Http\Controllers\Api\Learn\Course\attendances;

use App\Models\Course;
use App\Models\CourseGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\CourseAttendance;
use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Http\Resources\LessonResource;
use App\Http\Resources\CourseGroupResource;
use App\Http\Resources\CourseAttendanceResource;

class CourseAttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Course $course, Request $request)
    {
        $courseMemberOfAuth = $course->courseMembers()->where('user_id', auth()->id())->first();

        return response()->json([
            'course'        => new CourseResource($course),
            'groups'        => CourseGroupResource::collection($course->courseGroups),
            'courseMemberOfAuth'  => $courseMemberOfAuth,
            'isCourseAdmin' => $course->user_id === auth()->id(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Course $course, CourseGroup $group, Request $request)
    {
        $request->validate([
            'start_at' => 'required|date',
            'finish_at' => 'required|date',
            'late_time' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        $attendance = $course->courseAttendances()->create([
            'instructor_id' => auth()->id(),
            'group_id'      => $group->id,
            'date'          => Carbon::parse($request->start_at)->setTimezone('Asia/Bangkok')->format('Y-m-d H:i:s'),
            'start_at'      => Carbon::parse($request->start_at)->setTimezone('Asia/Bangkok')->format('Y-m-d H:i:s'),
            'finish_at'     => Carbon::parse($request->finish_at)->setTimezone('Asia/Bangkok')->format('Y-m-d H:i:s'),
            'late_time'     => $request->late_time,
            'description'   => $request->description,
        ]);

        return response()->json([
            'success'       => true,
            'attendance'    => new CourseAttendanceResource($attendance),
        ], 200);
    }

    // Update Attendance
    public function update(CourseAttendance $attendance, Request $request)
    {
        $request->validate([
            'start_at' => 'required|date',
            'finish_at' => 'required|date',
            'late_time' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        $attendance->update([
            'date'          => Carbon::parse($request->start_at)->setTimezone('Asia/Bangkok')->format('Y-m-d H:i:s'),
            'start_at'      => Carbon::parse($request->start_at)->setTimezone('Asia/Bangkok')->format('Y-m-d H:i:s'),
            'finish_at'     => Carbon::parse($request->finish_at)->setTimezone('Asia/Bangkok')->format('Y-m-d H:i:s'),
            'late_time'    => $request->late_time,
            'description'   => $request->description,
        ]);

        return response()->json([
            'success'       => true,
            'attendance'    => new CourseAttendanceResource($attendance),
        ], 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CourseAttendance $attendance)
    {
        //delete attendance details
        $attendance->details()->delete();
        //delete attendance
        $attendance->delete();

        return response()->json([
            'success' => true,
        ], 200);
    }

    // Get Course Attendance by Course and CourseGroup
    public function getCourseGroupAttendances(Course $course, CourseGroup $group, Request $request)
    {
        return response()->json([
            'success' => true,
            'attendances'   => CourseAttendanceResource::collection($course->courseAttendances->where('group_id', $group->id))
        ], 200);
    }

    /**
     * Update member attendance status by course admin
     * Status: 0 = ขาด (ลบ record), 1 = มา, 2 = สาย, 3 = ลา
     */
    public function updateMemberStatus(CourseAttendance $attendance, $memberId, Request $request)
    {
        // Validate request
        $request->validate([
            'status' => 'required|integer|in:0,1,2,3',
        ]);

        // Check if user is course admin
        $course = $attendance->course;
        if ($course->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์แก้ไขสถานะการเข้าร่วม'
            ], 403);
        }

        // Find attendance detail
        $attendanceDetail = $attendance->details()
            ->where('course_member_id', $memberId)
            ->first();

        // Status 0 = ขาด (ไม่บันทึก record หรือลบ record ถ้ามี)
        if ($request->status === 0) {
            if ($attendanceDetail) {
                $attendanceDetail->delete();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'ตั้งสถานะเป็น "ขาด" สำเร็จ (ลบ record)',
                'status' => 0,
            ], 200);
        }

        // Status 1, 2, 3 = สร้างหรืออัพเดท record
        if (!$attendanceDetail) {
            // Create new attendance detail
            $attendanceDetail = $attendance->details()->create([
                'attendanceable_type' => 'App\\Models\\CourseMember',
                'attendanceable_id' => $memberId,
                'course_attendance_id' => $attendance->id,
                'course_id' => $attendance->course_id,
                'group_id' => $attendance->group_id,
                'course_member_id' => $memberId,
                'status' => $request->status,
            ]);
        } else {
            // Update existing attendance detail
            $attendanceDetail->update([
                'status' => $request->status,
            ]);
        }

        $statusLabel = [
            1 => 'มา',
            2 => 'สาย',
            3 => 'ลา',
        ];

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทสถานะเป็น "' . ($statusLabel[$request->status] ?? 'ไม่ทราบ') . '" สำเร็จ',
            'status' => $request->status,
        ], 200);
    }

}
