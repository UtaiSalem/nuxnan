<?php

namespace App\Http\Controllers\Api\Learn\Course\attendances;

use App\Models\Course;
use App\Models\CourseGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\CourseAttendance;
use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Course\info\CourseResource;
use App\Http\Resources\Learn\Course\LessonResource;
use App\Http\Resources\Learn\Course\groups\CourseGroupResource;
use App\Http\Resources\Learn\Course\attendances\CourseAttendanceResource;

class CourseAttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Course $course, Request $request)
    {
        $courseMemberOfAuth = $course->courseMembers()->where('user_id', auth()->id())->first();
        $isCourseAdmin = $course->user_id === auth()->id();
        
        // Get attendances with optional group filter and eager load relationships
        $attendancesQuery = $course->courseAttendances()
            ->with(['group', 'instructor', 'attendanceDetails.courseMember.user']);
        
        // If admin with group_id filter or non-admin student
        if ($request->has('group_id')) {
            // Admin filtering by specific group
            $attendancesQuery->where('group_id', $request->group_id);
        } elseif (!$isCourseAdmin && $courseMemberOfAuth && $courseMemberOfAuth->group_id) {
            // Student: only show attendances from their own group
            $attendancesQuery->where('group_id', $courseMemberOfAuth->group_id);
        }
        
        $attendances = $attendancesQuery->orderBy('date', 'desc')->get();
        
        // Load course groups with members (use 'members' relationship which points to CourseMember)
        // For admin: load all groups, for student: only their group
        if ($isCourseAdmin) {
            $courseGroups = $course->courseGroups()->with(['members.user'])->get();
        } else {
            // Student only sees their own group
            $courseGroups = $courseMemberOfAuth && $courseMemberOfAuth->group_id
                ? $course->courseGroups()->where('id', $courseMemberOfAuth->group_id)->with(['members.user'])->get()
                : collect([]);
        }

        return response()->json([
            'course'        => new CourseResource($course),
            'groups'        => CourseGroupResource::collection($courseGroups),
            'data'          => CourseAttendanceResource::collection($attendances),
            'courseMemberOfAuth'  => $courseMemberOfAuth,
            'isCourseAdmin' => $isCourseAdmin,
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

        // Parse datetime as Bangkok timezone (frontend sends local time)
        $startAt = Carbon::parse($request->start_at, 'Asia/Bangkok');
        $finishAt = Carbon::parse($request->finish_at, 'Asia/Bangkok');

        $attendance = $course->courseAttendances()->create([
            'instructor_id' => auth()->id(),
            'group_id'      => $group->id,
            'date'          => $startAt->format('Y-m-d H:i:s'),
            'start_at'      => $startAt->format('Y-m-d H:i:s'),
            'finish_at'     => $finishAt->format('Y-m-d H:i:s'),
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

        // Parse datetime as Bangkok timezone (frontend sends local time)
        $startAt = Carbon::parse($request->start_at, 'Asia/Bangkok');
        $finishAt = Carbon::parse($request->finish_at, 'Asia/Bangkok');

        $attendance->update([
            'date'          => $startAt->format('Y-m-d H:i:s'),
            'start_at'      => $startAt->format('Y-m-d H:i:s'),
            'finish_at'     => $finishAt->format('Y-m-d H:i:s'),
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
        // Get status from request - handle 0 as valid value
        $status = $request->input('status');
        
        // Validate status value manually to handle 0 correctly
        if (!in_array($status, [0, 1, 2, 3], true)) {
            return response()->json([
                'success' => false,
                'message' => 'สถานะไม่ถูกต้อง',
                'errors' => ['status' => ['สถานะต้องเป็น 0, 1, 2 หรือ 3']]
            ], 422);
        }

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
        if ($status === 0) {
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
                'status' => $status,
            ]);
        } else {
            // Update existing attendance detail
            $attendanceDetail->update([
                'status' => $status,
            ]);
        }

        $statusLabel = [
            1 => 'มา',
            2 => 'สาย',
            3 => 'ลา',
        ];

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทสถานะเป็น "' . ($statusLabel[$status] ?? 'ไม่ทราบ') . '" สำเร็จ',
            'status' => $status,
        ], 200);
    }

    /**
     * Update last access group tab for course member
     */
    public function updateLastAccessGroupTab(Course $course, Request $request)
    {
        $request->validate([
            'last_accessed_group_tab' => 'required|integer',
        ]);

        $courseMember = $course->courseMembers()->where('user_id', auth()->id())->first();

        if (!$courseMember) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลสมาชิกในรายวิชานี้',
            ], 404);
        }

        $courseMember->update([
            'last_accessed_group_tab' => $request->last_accessed_group_tab,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทกลุ่มที่เข้าล่าสุดสำเร็จ',
        ], 200);
    }

    /**
     * Student self check-in for attendance
     * Checks time and determines status: 1 = มา (on time), 2 = สาย (late)
     */
    public function studentCheckIn(CourseAttendance $attendance)
    {
        $now = Carbon::now('Asia/Bangkok');
        
        // Get course member of authenticated user
        $courseMember = $attendance->course->courseMembers()
            ->where('user_id', auth()->id())
            ->first();
        
        if (!$courseMember) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลสมาชิกในรายวิชานี้',
            ], 404);
        }
        
        // Check if student is in the same group as the attendance
        if ($courseMember->group_id !== $attendance->group_id) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่ได้อยู่ในกลุ่มที่มีการเช็คชื่อนี้',
            ], 403);
        }
        
        // Parse attendance times (stored as Bangkok timezone without timezone info)
        $startAt = Carbon::parse($attendance->start_at, 'Asia/Bangkok');
        $finishAt = Carbon::parse($attendance->finish_at, 'Asia/Bangkok');
        $lateTime = $attendance->late_time ?? 15; // Default 15 minutes
        $lateThreshold = $startAt->copy()->addMinutes($lateTime);
        
        // Check if attendance session has ended
        if ($now->gt($finishAt)) {
            return response()->json([
                'success' => false,
                'message' => 'เวลาเรียนสิ้นสุดแล้ว ไม่สามารถรายงานตัวได้',
                'ended' => true,
            ], 400);
        }
        
        // Check if attendance session hasn't started yet
        if ($now->lt($startAt)) {
            return response()->json([
                'success' => false,
                'message' => 'ยังไม่ถึงเวลาเริ่มเรียน กรุณารอจนถึงเวลาเริ่ม',
                'not_started' => true,
            ], 400);
        }
        
        // Check if already checked in
        $existingDetail = $attendance->details()
            ->where('course_member_id', $courseMember->id)
            ->first();
        
        if ($existingDetail && in_array($existingDetail->status, [1, 2])) {
            return response()->json([
                'success' => false,
                'message' => 'คุณได้รายงานตัวเข้าเรียนแล้ว',
                'already_checked_in' => true,
                'status' => $existingDetail->status,
            ], 400);
        }
        
        // Determine status based on time
        // If current time is after late threshold = late (2), otherwise = on time (1)
        $status = $now->gt($lateThreshold) ? 2 : 1;
        
        // Create or update attendance detail
        if ($existingDetail) {
            $existingDetail->update([
                'status' => $status,
                'time_in' => $now->format('H:i:s'),
            ]);
        } else {
            $attendance->details()->create([
                'attendanceable_type' => 'App\\Models\\CourseMember',
                'attendanceable_id' => $courseMember->id,
                'course_attendance_id' => $attendance->id,
                'course_id' => $attendance->course_id,
                'group_id' => $attendance->group_id,
                'course_member_id' => $courseMember->id,
                'status' => $status,
                'time_in' => $now->format('H:i:s'),
            ]);
        }
        
        $statusLabel = $status === 1 ? 'มา' : 'สาย';
        
        return response()->json([
            'success' => true,
            'message' => "รายงานตัวสำเร็จ - สถานะ: {$statusLabel}",
            'status' => $status,
            'time_in' => $now->format('H:i:s'),
            'is_late' => $status === 2,
        ], 200);
    }

}
