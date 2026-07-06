<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseRemediationEnrollment;
use App\Models\CourseRemediationSession;
use App\Services\RemediationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RemediationController extends Controller
{
    private const FEATURE_ENABLED = false;

    protected RemediationService $remediationService;

    public function __construct(RemediationService $remediationService)
    {
        $this->remediationService = $remediationService;
    }

    /**
     * Get remediation sessions for a course
     */
    public function index(Request $request, Course $course): JsonResponse
    {
        if (! self::FEATURE_ENABLED) {
            return $this->featureDisabledResponse();
        }

        $query = CourseRemediationSession::where('course_id', $course->id)
            ->withCount('enrollments');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Non-admin can only see published sessions
        if (! $request->user()->can('manage', $course)) {
            $query->whereIn('status', ['open', 'in_progress', 'completed']);
        }

        $sessions = $query->orderBy('start_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    /**
     * Create a remediation session
     */
    public function store(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);

        if (! self::FEATURE_ENABLED) {
            return $this->featureDisabledResponse();
        }

        if (! $course->allow_remediation) {
            return response()->json([
                'success' => false,
                'message' => 'วิชานี้ไม่อนุญาตให้แก้ตัว',
            ], 400);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_at' => 'required|date|after:now',
            'end_at' => 'required|date|after:start_at',
            'registration_deadline' => 'nullable|date|before:start_at',
            'type' => 'required|in:exam_retake,assignment,project,attendance_makeup,mixed',
            'quiz_id' => 'nullable|exists:course_quizzes,id',
            'eligible_grades' => 'nullable|array',
            'eligible_grades.*' => 'string|in:A,B+,B,C+,C,D+,D,F',
            'max_grade_achievable' => 'nullable|string|in:A,B+,B,C+,C,D+,D',
            'max_participants' => 'nullable|integer|min:1',
            'passing_score' => 'nullable|numeric|min:0|max:100',
            'location' => 'nullable|string|max:255',
            'is_online' => 'boolean',
            'online_link' => 'nullable|url',
            'instructions' => 'nullable|string',
        ]);

        $session = $this->remediationService->createSession(
            $course,
            $request->all(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'สร้างรอบแก้ตัวเรียบร้อย',
            'data' => $session,
        ], 201);
    }

    /**
     * Get session details
     */
    public function show(Request $request, CourseRemediationSession $session): JsonResponse
    {
        if (! self::FEATURE_ENABLED) {
            return $this->featureDisabledResponse();
        }

        $session->load(['enrollments.student:id,name,email,profile_photo_path']);

        $data = [
            'session' => $session,
            'statistics' => $this->remediationService->getSessionStatistics($session),
        ];

        // Add eligible students for admin
        if ($request->user()->can('manage', $session->course)) {
            $data['eligible_students'] = $this->remediationService->getEligibleStudents($session);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Update a session
     */
    public function update(Request $request, CourseRemediationSession $session): JsonResponse
    {
        $this->authorize('manage', $session->course);

        if (! self::FEATURE_ENABLED) {
            return $this->featureDisabledResponse();
        }

        if (! in_array($session->status, ['draft', 'open'])) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถแก้ไขรอบที่เริ่มแล้ว',
            ], 400);
        }

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'quiz_id' => 'nullable|exists:course_quizzes,id',
            'start_at' => 'sometimes|date',
            'end_at' => 'sometimes|date|after:start_at',
            'registration_deadline' => 'nullable|date',
            'type' => 'sometimes|in:exam_retake,assignment,project,attendance_makeup,mixed',
            'eligible_grades' => 'nullable|array',
            'max_grade_achievable' => 'nullable|string|in:A,B+,B,C+,C,D+,D',
            'max_participants' => 'nullable|integer|min:1',
            'passing_score' => 'nullable|numeric|min:0|max:100',
            'location' => 'nullable|string|max:255',
            'is_online' => 'boolean',
            'online_link' => 'nullable|url',
            'instructions' => 'nullable|string',
        ]);

        $session->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทรอบแก้ตัวเรียบร้อย',
            'data' => $session->fresh(),
        ]);
    }

    /**
     * Open session for registration
     */
    public function open(Request $request, CourseRemediationSession $session): JsonResponse
    {
        $this->authorize('manage', $session->course);

        if (! self::FEATURE_ENABLED) {
            return $this->featureDisabledResponse();
        }

        if ($session->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'เฉพาะรอบที่เป็นฉบับร่าง',
            ], 400);
        }

        $session = $this->remediationService->openSession($session);

        return response()->json([
            'success' => true,
            'message' => 'เปิดรับสมัครแก้ตัวเรียบร้อย',
            'data' => $session,
        ]);
    }

    /**
     * Start the session
     */
    public function start(Request $request, CourseRemediationSession $session): JsonResponse
    {
        $this->authorize('manage', $session->course);

        if (! self::FEATURE_ENABLED) {
            return $this->featureDisabledResponse();
        }

        if ($session->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'เฉพาะรอบที่เปิดรับสมัครอยู่',
            ], 400);
        }

        $session = $this->remediationService->startSession($session);

        return response()->json([
            'success' => true,
            'message' => 'เริ่มรอบแก้ตัวเรียบร้อย',
            'data' => $session,
        ]);
    }

    /**
     * Enroll in a remediation session
     */
    public function enroll(Request $request, CourseRemediationSession $session): JsonResponse
    {
        if (! self::FEATURE_ENABLED) {
            return $this->featureDisabledResponse();
        }

        $user = $request->user();
        $course = $session->course;

        $member = $course->members()
            ->where('user_id', $user->id)
            ->first();

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลการลงทะเบียน',
            ], 404);
        }

        // Lifecycle guard: needs an active remediation grant + course not archived.
        $gate = Gate::inspect('submitRemediation', $course);
        if ($gate->denied()) {
            return response()->json([
                'success' => false,
                'code' => $gate->code() ?: 'NO_REMEDIATION_GRANT',
                'message' => $gate->message() ?: 'ไม่สามารถลงทะเบียนสอบแก้ตัวได้',
            ], 422);
        }

        try {
            $enrollment = $this->remediationService->enrollStudent($session, $member);

            return response()->json([
                'success' => true,
                'message' => 'ลงทะเบียนแก้ตัวเรียบร้อย',
                'data' => $enrollment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Bulk enroll students in a remediation session (Admin only)
     */
    public function bulkEnroll(Request $request, CourseRemediationSession $session): JsonResponse
    {
        $this->authorize('manage', $session->course);

        if (! self::FEATURE_ENABLED) {
            return $this->featureDisabledResponse();
        }

        $request->validate([
            'member_ids' => 'required|array',
            'member_ids.*' => 'required|exists:course_members,id',
        ]);

        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($request->member_ids as $memberId) {
            $member = CourseMember::find($memberId);

            if (! $member || $member->course_id !== $session->course_id) {
                $results['failed']++;
                $results['errors'][] = "Member ID {$memberId} does not belong to this course.";

                continue;
            }

            try {
                // Admin bulk enroll forces the enrollment (bypasses deadline/status checks)
                $this->remediationService->enrollStudent($session, $member, true);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Member ID {$memberId}: ".$e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => "ลงทะเบียนสำเร็จ {$results['success']} รายการ, ล้มเหลว {$results['failed']} รายการ",
            'data' => $results,
        ]);
    }

    /**
     * Get my enrollments
     */
    public function myEnrollments(Request $request): JsonResponse
    {
        if (! self::FEATURE_ENABLED) {
            return $this->featureDisabledResponse();
        }

        $enrollments = CourseRemediationEnrollment::where('student_id', $request->user()->id)
            ->with([
                'remediationSession:id,title,type,start_at,end_at,status',
                'remediationSession.course:id,title',
            ])
            ->orderBy('enrolled_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $enrollments,
        ]);
    }

    /**
     * Submit work
     */
    public function submitWork(Request $request, CourseRemediationEnrollment $enrollment): JsonResponse
    {
        if (! self::FEATURE_ENABLED) {
            return $this->featureDisabledResponse();
        }

        if ($enrollment->student_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์',
            ], 403);
        }

        $session = $enrollment->remediationSession;

        if (! in_array($session->type, ['assignment', 'project', 'mixed'])) {
            return response()->json([
                'success' => false,
                'message' => 'รอบนี้ไม่รับงาน',
            ], 400);
        }

        // Lifecycle guard: archived courses block all remediation submissions.
        $course = $session->course;
        if ($course) {
            $gate = Gate::inspect('submitRemediation', $course);
            if ($gate->denied()) {
                return response()->json([
                    'success' => false,
                    'code' => $gate->code() ?: 'WORK_TYPE_LOCKED_AFTER_END',
                    'message' => $gate->message() ?: 'ไม่สามารถส่งงานแก้ตัวได้',
                ], 422);
            }
        }

        $request->validate([
            'files' => 'nullable|array',
            'links' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
        ]);

        $submission = [
            'files' => $request->files ?? [],
            'links' => $request->links ?? [],
            'submitted_at' => now()->toIso8601String(),
        ];

        $enrollment = $this->remediationService->submitWork(
            $enrollment,
            $submission,
            $request->notes
        );

        return response()->json([
            'success' => true,
            'message' => 'ส่งงานเรียบร้อย',
            'data' => $enrollment,
        ]);
    }

    /**
     * Mark attendance for an enrollment
     */
    public function markAttendance(Request $request, CourseRemediationEnrollment $enrollment): JsonResponse
    {
        $this->authorize('manage', $enrollment->remediationSession->course);

        if (! self::FEATURE_ENABLED) {
            return $this->featureDisabledResponse();
        }

        $enrollment = $this->remediationService->markAttendance($enrollment);

        return response()->json([
            'success' => true,
            'message' => 'บันทึกการเข้าร่วมเรียบร้อย',
            'data' => $enrollment,
        ]);
    }

    /**
     * Grade an enrollment
     */
    public function grade(Request $request, CourseRemediationEnrollment $enrollment): JsonResponse
    {
        $this->authorize('manage', $enrollment->remediationSession->course);

        if (! self::FEATURE_ENABLED) {
            return $this->featureDisabledResponse();
        }

        $request->validate([
            'score' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $enrollment = $this->remediationService->gradeEnrollment(
            $enrollment,
            $request->score,
            $request->user(),
            $request->notes
        );

        return response()->json([
            'success' => true,
            'message' => 'ให้คะแนนเรียบร้อย',
            'data' => $enrollment,
        ]);
    }

    /**
     * Bulk grade enrollments
     */
    public function bulkGrade(Request $request, CourseRemediationSession $session): JsonResponse
    {
        $this->authorize('manage', $session->course);

        if (! self::FEATURE_ENABLED) {
            return $this->featureDisabledResponse();
        }

        $request->validate([
            'grades' => 'required|array',
            'grades.*.enrollment_id' => 'required|exists:course_remediation_enrollments,id',
            'grades.*.score' => 'required|numeric|min:0|max:100',
            'grades.*.notes' => 'nullable|string|max:500',
        ]);

        $results = [];

        foreach ($request->grades as $gradeData) {
            $enrollment = CourseRemediationEnrollment::find($gradeData['enrollment_id']);

            if ($enrollment && $enrollment->remediation_session_id === $session->id) {
                $this->remediationService->gradeEnrollment(
                    $enrollment,
                    $gradeData['score'],
                    $request->user(),
                    $gradeData['notes'] ?? null
                );
                $results[] = [
                    'enrollment_id' => $enrollment->id,
                    'success' => true,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'ให้คะแนนเรียบร้อย',
            'data' => $results,
        ]);
    }

    /**
     * Complete the session
     */
    public function complete(Request $request, CourseRemediationSession $session): JsonResponse
    {
        $this->authorize('manage', $session->course);

        if (! self::FEATURE_ENABLED) {
            return $this->featureDisabledResponse();
        }

        if (! in_array($session->status, ['in_progress', 'grading'])) {
            return response()->json([
                'success' => false,
                'message' => 'เฉพาะรอบที่กำลังดำเนินการ',
            ], 400);
        }

        $session = $this->remediationService->completeSession($session);

        return response()->json([
            'success' => true,
            'message' => 'ปิดรอบแก้ตัวเรียบร้อย',
            'data' => [
                'session' => $session,
                'statistics' => $this->remediationService->getSessionStatistics($session),
            ],
        ]);
    }

    private function featureDisabledResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 'REMEDIATION_SESSIONS_DISABLED',
            'message' => 'ฟีเจอร์รอบแก้ตัวถูกปิดชั่วคราว กรุณาใช้การส่งงานและทำแบบทดสอบตามกระบวนการเรียนปกติ',
        ], 423);
    }

    /**
     * Cancel an enrollment
     */
    public function cancelEnrollment(Request $request, CourseRemediationEnrollment $enrollment): JsonResponse
    {
        if (! self::FEATURE_ENABLED) {
            return $this->featureDisabledResponse();
        }

        $user = $request->user();

        // Student can cancel their own, admin can cancel any
        if ($enrollment->student_id !== $user->id) {
            $this->authorize('manage', $enrollment->remediationSession->course);
        }

        if (! in_array($enrollment->status, ['enrolled', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถยกเลิกได้',
            ], 400);
        }

        $enrollment->cancel();

        // Reset member status
        $member = $enrollment->courseMember;
        $member->update([
            'completion_status' => 'failed',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ยกเลิกการลงทะเบียนแก้ตัว',
        ]);
    }
}
