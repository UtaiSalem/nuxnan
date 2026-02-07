<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\ExamEligibilityOverride;
use App\Services\AttendanceEligibilityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExamEligibilityController extends Controller
{
    protected AttendanceEligibilityService $eligibilityService;

    public function __construct(AttendanceEligibilityService $eligibilityService)
    {
        $this->eligibilityService = $eligibilityService;
    }

    /**
     * Get eligibility status for a course member
     */
    public function getStatus(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();
        
        $member = $course->members()
            ->where('user_id', $user->id)
            ->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลการลงทะเบียน',
            ], 404);
        }

        $result = $this->eligibilityService->canTakeExam($member);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get eligibility summary for all students in a course (Admin)
     */
    public function getCourseSummary(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);

        $summary = $this->eligibilityService->getCourseEligibilitySummary($course);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Update eligibility for all students in a course
     */
    public function refreshCourseEligibility(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);

        $results = $this->eligibilityService->updateCourseEligibility($course);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทสถานะสิทธิ์สอบเรียบร้อย',
            'data' => $results,
        ]);
    }

    /**
     * Request unlock by points
     */
    public function requestPointsUnlock(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();
        
        $member = $course->members()
            ->where('user_id', $user->id)
            ->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลการลงทะเบียน',
            ], 404);
        }

        if ($member->eligibility_status === 'unlocked' || $member->exam_eligible) {
            return response()->json([
                'success' => false,
                'message' => 'คุณมีสิทธิ์สอบอยู่แล้ว',
            ], 400);
        }

        try {
            $override = $this->eligibilityService->requestUnlockByPoints($member);

            // TODO: Deduct points from user's wallet
            // $pointsService->deduct($user, $course->unlock_points_cost, 'Unlock exam eligibility');

            // For now, auto-approve if points are available
            // In production, integrate with wallet service
            $override = $this->eligibilityService->processPointsUnlock($override, 0);

            return response()->json([
                'success' => true,
                'message' => 'ปลดล็อคสิทธิ์สอบสำเร็จ',
                'data' => $override,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Request unlock by reading
     */
    public function requestReadingUnlock(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();
        
        $member = $course->members()
            ->where('user_id', $user->id)
            ->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลการลงทะเบียน',
            ], 404);
        }

        try {
            $override = $this->eligibilityService->requestUnlockByReading($member);

            return response()->json([
                'success' => true,
                'message' => 'เริ่มการปลดล็อคด้วยการอ่าน',
                'data' => [
                    'override' => $override,
                    'required_minutes' => $course->unlock_reading_minutes,
                    'completed_minutes' => 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update reading progress
     */
    public function updateReadingProgress(Request $request, ExamEligibilityOverride $override): JsonResponse
    {
        $request->validate([
            'minutes' => 'required|integer|min:1',
            'content_type' => 'required|string',
            'content_id' => 'required',
        ]);

        if ($override->student_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์',
            ], 403);
        }

        $proof = [
            'content_type' => $request->content_type,
            'content_id' => $request->content_id,
            'minutes' => $request->minutes,
            'timestamp' => now()->toIso8601String(),
        ];

        $override = $this->eligibilityService->updateReadingProgress(
            $override,
            $request->minutes,
            $proof
        );

        $course = $override->course;

        return response()->json([
            'success' => true,
            'message' => $override->status === 'approved' ? 'ปลดล็อคสิทธิ์สอบสำเร็จ!' : 'บันทึกความก้าวหน้าแล้ว',
            'data' => [
                'override' => $override,
                'required_minutes' => $course->unlock_reading_minutes,
                'completed_minutes' => $override->reading_minutes_completed,
                'is_completed' => $override->status === 'approved',
            ],
        ]);
    }

    /**
     * Admin: Get pending unlock requests
     */
    public function getPendingRequests(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);

        $requests = ExamEligibilityOverride::where('course_id', $course->id)
            ->where('status', 'pending')
            ->with(['student:id,name,avatar,email', 'courseMember'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    /**
     * Admin: Approve unlock request
     */
    public function approveRequest(Request $request, ExamEligibilityOverride $override): JsonResponse
    {
        $course = $override->course;
        $this->authorize('manage', $course);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $override = $this->eligibilityService->adminApprove(
            $override,
            $request->user(),
            $request->reason
        );

        return response()->json([
            'success' => true,
            'message' => 'อนุมัติการปลดล็อคสำเร็จ',
            'data' => $override,
        ]);
    }

    /**
     * Admin: Reject unlock request
     */
    public function rejectRequest(Request $request, ExamEligibilityOverride $override): JsonResponse
    {
        $course = $override->course;
        $this->authorize('manage', $course);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $override = $this->eligibilityService->adminReject(
            $override,
            $request->user(),
            $request->reason
        );

        return response()->json([
            'success' => true,
            'message' => 'ปฏิเสธคำขอปลดล็อค',
            'data' => $override,
        ]);
    }

    /**
     * Admin: Direct unlock for a student
     */
    public function adminUnlock(Request $request, Course $course, CourseMember $member): JsonResponse
    {
        $this->authorize('manage', $course);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // Create and auto-approve override
        $stats = $this->eligibilityService->calculateAttendanceStats($member);

        $override = ExamEligibilityOverride::create([
            'course_member_id' => $member->id,
            'course_id' => $course->id,
            'student_id' => $member->user_id,
            'unlock_method' => 'admin',
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'admin_reason' => $request->reason,
            'absence_percent_at_unlock' => $stats['absence_rate'],
            'total_sessions_at_unlock' => $stats['total_sessions'],
            'absent_sessions_at_unlock' => $stats['absent'],
        ]);

        $member->update([
            'exam_eligible' => true,
            'eligibility_status' => 'unlocked',
            'eligibility_unlocked_at' => now(),
            'eligibility_unlock_method' => 'admin',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ปลดล็อคสิทธิ์สอบสำเร็จ',
            'data' => $override,
        ]);
    }
}
