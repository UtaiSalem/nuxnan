<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\GradeAppeal;
use App\Services\CourseGradingService;
use App\Services\GradingNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GradeAppealController extends Controller
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
     * Get appeals for a course (Admin)
     */
    public function index(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);

        $query = GradeAppeal::where('course_id', $course->id)
            ->with(['student:id,name,avatar,email', 'reviewer:id,name']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $appeals = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $appeals,
        ]);
    }

    /**
     * Get my appeals (Student)
     */
    public function myAppeals(Request $request): JsonResponse
    {
        $user = $request->user();

        $appeals = GradeAppeal::where('student_id', $user->id)
            ->with(['course:id,title', 'reviewer:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $appeals,
        ]);
    }

    /**
     * Submit a grade appeal
     */
    public function store(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();

        // Check if user is enrolled
        $member = $course->members()
            ->where('user_id', $user->id)
            ->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลการลงทะเบียน',
            ], 404);
        }

        // Check if appeals are allowed
        if (!$course->allow_grade_appeal) {
            return response()->json([
                'success' => false,
                'message' => 'วิชานี้ไม่เปิดรับอุทธรณ์',
            ], 400);
        }

        // Check deadline
        $deadline = $course->finalized_at?->addDays($course->appeal_deadline_days ?? 7);
        $isLate = $deadline && now()->isAfter($deadline);

        // Check existing pending appeal
        $existingAppeal = GradeAppeal::where('course_member_id', $member->id)
            ->whereIn('status', ['pending', 'reviewing'])
            ->first();

        if ($existingAppeal) {
            return response()->json([
                'success' => false,
                'message' => 'คุณมีคำร้องอุทธรณ์รอพิจารณาอยู่แล้ว',
            ], 400);
        }

        $request->validate([
            'appeal_type' => 'required|in:score_error,attendance_error,grading_criteria,special_circumstance,other',
            'reason' => 'required|string|min:20|max:2000',
            'requested_grade' => 'nullable|string|in:A,B+,B,C+,C,D+,D,F',
            'evidence' => 'nullable|array',
        ]);

        $appeal = GradeAppeal::create([
            'course_member_id' => $member->id,
            'course_id' => $course->id,
            'student_id' => $user->id,
            'appeal_type' => $request->appeal_type,
            'reason' => $request->reason,
            'evidence' => $request->evidence,
            'original_grade' => $member->final_grade ?? $member->draft_grade,
            'original_score' => $member->final_total_score ?? $member->draft_total_score,
            'requested_grade' => $request->requested_grade,
            'status' => 'pending',
            'deadline_at' => $deadline,
            'is_late_appeal' => $isLate,
        ]);

        // Notify course admins
        $this->notificationService->notifyAppealSubmitted($appeal);

        return response()->json([
            'success' => true,
            'message' => 'ยื่นอุทธรณ์เรียบร้อย',
            'data' => $appeal,
        ], 201);
    }

    /**
     * Get appeal details
     */
    public function show(Request $request, GradeAppeal $appeal): JsonResponse
    {
        $user = $request->user();

        // Check access
        if ($appeal->student_id !== $user->id) {
            $this->authorize('manage', $appeal->course);
        }

        $appeal->load(['student:id,name,avatar,email', 'reviewer:id,name', 'courseMember', 'gradeEditLog']);

        return response()->json([
            'success' => true,
            'data' => $appeal,
        ]);
    }

    /**
     * Start reviewing an appeal (Admin)
     */
    public function startReview(Request $request, GradeAppeal $appeal): JsonResponse
    {
        $this->authorize('manage', $appeal->course);

        if ($appeal->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'สถานะไม่ถูกต้อง',
            ], 400);
        }

        $appeal->update([
            'status' => 'reviewing',
            'reviewed_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'เริ่มพิจารณาอุทธรณ์',
            'data' => $appeal,
        ]);
    }

    /**
     * Approve an appeal
     */
    public function approve(Request $request, GradeAppeal $appeal): JsonResponse
    {
        $this->authorize('manage', $appeal->course);

        if (!in_array($appeal->status, ['pending', 'reviewing'])) {
            return response()->json([
                'success' => false,
                'message' => 'สถานะไม่ถูกต้อง',
            ], 400);
        }

        $request->validate([
            'new_grade' => 'required|string|in:A,B+,B,C+,C,D+,D,F',
            'new_score' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $appeal->approve(
            $request->user(),
            $request->new_grade,
            $request->new_score,
            $request->notes
        );

        // Update the member's grade
        $member = $appeal->courseMember;
        $this->gradingService->overrideGrade(
            $member,
            $request->user(),
            $request->new_grade,
            $request->new_score,
            'ผลอุทธรณ์: ' . ($request->notes ?? 'อนุมัติ'),
            \App\Models\GradeEditLog::TYPE_APPEAL_RESULT
        );

        // Notify student
        $this->notificationService->notifyAppealResponded($appeal);

        return response()->json([
            'success' => true,
            'message' => 'อนุมัติอุทธรณ์เรียบร้อย',
            'data' => $appeal->fresh(),
        ]);
    }

    /**
     * Reject an appeal
     */
    public function reject(Request $request, GradeAppeal $appeal): JsonResponse
    {
        $this->authorize('manage', $appeal->course);

        if (!in_array($appeal->status, ['pending', 'reviewing'])) {
            return response()->json([
                'success' => false,
                'message' => 'สถานะไม่ถูกต้อง',
            ], 400);
        }

        $request->validate([
            'reason' => 'required|string|min:10|max:1000',
            'notes' => 'nullable|string|max:1000',
        ]);

        $appeal->reject(
            $request->user(),
            $request->reason,
            $request->notes
        );

        // Notify student
        $this->notificationService->notifyAppealResponded($appeal);

        return response()->json([
            'success' => true,
            'message' => 'ปฏิเสธอุทธรณ์',
            'data' => $appeal->fresh(),
        ]);
    }

    /**
     * Withdraw an appeal (Student)
     */
    public function withdraw(Request $request, GradeAppeal $appeal): JsonResponse
    {
        if ($appeal->student_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์',
            ], 403);
        }

        if (!in_array($appeal->status, ['pending'])) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถถอนอุทธรณ์ได้ เนื่องจากอยู่ระหว่างพิจารณา',
            ], 400);
        }

        $appeal->update([
            'status' => 'withdrawn',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ถอนอุทธรณ์เรียบร้อย',
        ]);
    }

    /**
     * Get appeal statistics for a course
     */
    public function getStatistics(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);

        $appeals = GradeAppeal::where('course_id', $course->id)->get();

        $stats = [
            'total' => $appeals->count(),
            'pending' => $appeals->where('status', 'pending')->count(),
            'reviewing' => $appeals->where('status', 'reviewing')->count(),
            'approved' => $appeals->where('status', 'approved')->count(),
            'rejected' => $appeals->where('status', 'rejected')->count(),
            'withdrawn' => $appeals->where('status', 'withdrawn')->count(),
            'by_type' => $appeals->groupBy('appeal_type')->map->count(),
            'late_appeals' => $appeals->where('is_late_appeal', true)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
