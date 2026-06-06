<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\GradeScale;
use App\Services\CourseGradingService;
use App\Services\GradingNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CourseCompletionController extends Controller
{
    protected CourseGradingService $gradingService;
    protected GradingNotificationService $notificationService;

    public function __construct(
        CourseGradingService $gradingService,
        GradingNotificationService $notificationService
    ) {
        $this->gradingService = $gradingService;
        $this->notificationService = $notificationService;
    }

    /**
     * Get grading summary for a course
     */
    public function getSummary(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);

        $summary = $this->gradingService->getGradingSummary($course);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Start grading period
     */
    public function startGrading(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);

        if ($course->finalization_status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถเริ่มช่วงให้เกรดได้ สถานะปัจจุบัน: ' . $course->finalization_status,
            ], 400);
        }

        $course = $this->gradingService->startGradingPeriod($course, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'เริ่มช่วงให้เกรดเรียบร้อย',
            'data' => [
                'course' => $course,
                'summary' => $this->gradingService->getGradingSummary($course),
            ],
        ]);
    }

    /**
     * Calculate and preview draft grades
     */
    public function previewGrades(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);

        $gradeScale = null;
        if ($request->grade_scale_id) {
            $gradeScale = GradeScale::find($request->grade_scale_id);
        }

        $results = $this->gradingService->calculateDraftGrades($course, $gradeScale);

        return response()->json([
            'success' => true,
            'data' => [
                'grades' => $results,
                'statistics' => $this->gradingService->calculateGradeStatistics($course),
            ],
        ]);
    }

    /**
     * Publish draft grades to students
     */
    public function publishGrades(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);

        if (!in_array($course->finalization_status, ['grading'])) {
            return response()->json([
                'success' => false,
                'message' => 'ต้องอยู่ในช่วงให้เกรดก่อน',
            ], 400);
        }

        $gradeScale = null;
        if ($request->grade_scale_id) {
            $gradeScale = GradeScale::find($request->grade_scale_id);
        }

        $result = $this->gradingService->publishDraftGrades($course, $request->user(), $gradeScale);

        // Send notifications to students
        $notificationCount = $this->notificationService->notifyGradePublished($course, $request->user());

        return response()->json([
            'success' => true,
            'message' => "ประกาศเกรดเบื้องต้นเรียบร้อย (แจ้งเตือน {$notificationCount} คน)",
            'data' => $result,
        ]);
    }

    /**
     * Student: Get my grade for a course
     */
    public function getMyGrade(Request $request, Course $course): JsonResponse
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

        // CC-PRIV-1 Fix: Do not show draft grades during 'grading' status
        $canView = in_array($course->finalization_status, ['published', 'finalized', 'archived']);

        return response()->json([
            'success' => true,
            'data' => [
                'can_view' => $canView,
                'finalization_status' => $course->finalization_status,
                'completion_status' => $member->completion_status,
                'draft_grade' => $canView ? $member->draft_grade : null,
                'draft_score' => $canView ? $member->draft_total_score : null,
                'final_grade' => $member->final_grade,
                'final_score' => $member->final_total_score,
                'grade_point' => $member->final_grade_point ?? $member->draft_grade_point,
                'grade_accepted_at' => $member->grade_accepted_at,
                'can_accept' => $member->completion_status === 'pending_acceptance',
                'can_appeal' => $course->allow_grade_appeal && $member->final_grade !== null,
            ],
        ]);
    }

    /**
     * Student: Accept grade
     */
    public function acceptGrade(Request $request, Course $course): JsonResponse
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

        if ($member->completion_status !== 'pending_acceptance') {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถยืนยันเกรดได้ สถานะ: ' . $member->completion_status,
            ], 400);
        }

        $member = $this->gradingService->acceptGrade($member);

        return response()->json([
            'success' => true,
            'message' => 'ยืนยันเกรดเรียบร้อย',
            'data' => [
                'grade' => $member->final_grade,
                'score' => $member->final_total_score,
                'grade_point' => $member->final_grade_point,
                'completion_status' => $member->completion_status,
            ],
        ]);
    }

    /**
     * Finalize all grades
     */
    public function finalizeGrades(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);

        if (!in_array($course->finalization_status, ['grading', 'published'])) {
            return response()->json([
                'success' => false,
                'message' => 'ต้องอยู่ในช่วงให้เกรดหรือประกาศเกรดเบื้องต้นก่อน',
            ], 400);
        }

        // Check for pending appeals
        $pendingAppeals = $course->gradeAppeals()
            ->whereIn('status', ['pending', 'reviewing'])
            ->count();

        if ($pendingAppeals > 0) {
            return response()->json([
                'success' => false,
                'message' => "ยังมีคำร้องอุทธรณ์รอพิจารณา {$pendingAppeals} รายการ",
            ], 400);
        }

        $course = $this->gradingService->finalizeGrades($course, $request->user());

        // Send notifications to students
        $notificationCount = $this->notificationService->notifyGradeFinalized($course, $request->user());

        return response()->json([
            'success' => true,
            'message' => "ยืนยันเกรดสุดท้ายเรียบร้อย (แจ้งเตือน {$notificationCount} คน)",
            'data' => $this->gradingService->getGradingSummary($course),
        ]);
    }

    /**
     * Override a student's grade
     */
    public function overrideGrade(Request $request, Course $course, CourseMember $member): JsonResponse
    {
        $this->authorize('manage', $course);

        $request->validate([
            'grade' => 'required|string|in:A,B+,B,C+,C,D+,D,F',
            'score' => 'nullable|numeric|min:0|max:100',
            'reason' => 'required|string|max:500',
        ]);

        $member = $this->gradingService->overrideGrade(
            $member,
            $request->user(),
            $request->grade,
            $request->score,
            $request->reason
        );

        return response()->json([
            'success' => true,
            'message' => 'แก้ไขเกรดเรียบร้อย',
            'data' => [
                'member_id' => $member->id,
                'grade' => $member->final_grade,
                'score' => $member->final_total_score,
                'grade_point' => $member->final_grade_point,
            ],
        ]);
    }

    /**
     * Reopen grading for corrections
     */
    public function reopenGrading(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);

        if ($course->finalization_status !== 'finalized') {
            return response()->json([
                'success' => false,
                'message' => 'เฉพาะวิชาที่ยืนยันเกรดแล้วเท่านั้น',
            ], 400);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $course = $this->gradingService->reopenGrading($course, $request->user(), $request->reason);

        return response()->json([
            'success' => true,
            'message' => 'เปิดให้แก้ไขเกรดใหม่เรียบร้อย',
            'data' => $course,
        ]);
    }

    /**
     * Archive course
     */
    public function archiveCourse(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);

        if ($course->finalization_status !== 'finalized') {
            return response()->json([
                'success' => false,
                'message' => 'ต้องยืนยันเกรดก่อนจึงจะ Archive ได้',
            ], 400);
        }

        $course = $this->gradingService->archiveCourse($course, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'เก็บวิชาเข้า Archive เรียบร้อย',
            'data' => $course,
        ]);
    }

    /**
     * Get grade edit history for a student
     */
    public function getGradeHistory(Request $request, Course $course, CourseMember $member): JsonResponse
    {
        $this->authorize('manage', $course);

        $logs = $member->gradeEditLogs()
            ->with('editor:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Get all students' grades for export
     */
    public function exportGrades(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);

        $members = $course->members()
            ->with('user:id,name,email,student_id')
            ->get()
            ->map(function ($member) {
                return [
                    'student_id' => $member->user->student_id ?? $member->user->id,
                    'name' => $member->user->name,
                    'email' => $member->user->email,
                    'total_score' => $member->final_total_score ?? $member->draft_total_score,
                    'grade' => $member->final_grade ?? $member->draft_grade,
                    'grade_point' => $member->final_grade_point ?? $member->draft_grade_point,
                    'completion_status' => $member->completion_status,
                    'completed_at' => $member->completed_at?->format('Y-m-d'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'course' => [
                    'id' => $course->id,
                    'title' => $course->title,
                    'finalization_status' => $course->finalization_status,
                ],
                'students' => $members,
                'statistics' => $this->gradingService->calculateGradeStatistics($course),
            ],
        ]);
    }
}
