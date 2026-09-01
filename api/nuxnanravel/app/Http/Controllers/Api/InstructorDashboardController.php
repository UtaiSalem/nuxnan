<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseAttendance;
use App\Models\CourseCertificate;
use App\Models\CourseMember;
use App\Models\GradeAppeal;
use App\Services\CourseGradingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Instructor Dashboard Controller
 *
 * ให้ข้อมูลสรุปภาพรวมสำหรับผู้สอน
 */
class InstructorDashboardController extends Controller
{
    protected CourseGradingService $gradingService;

    public function __construct(CourseGradingService $gradingService)
    {
        $this->gradingService = $gradingService;
    }

    /**
     * Get dashboard summary for a specific course
     */
    public function courseDashboard(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);

        // Load relationships
        $course->load([
            'courseGroups',
            'courseMembers',
            'courseLessons',
            'courseAssignments',
            'courseQuizzes',
        ]);

        // Basic counts
        $totalMembers = $course->courseMembers()->count();
        $activeMembers = $course->courseMembers()->whereNull('deleted_at')->count();

        // Get members with their progress
        $members = $course->courseMembers()
            ->with('user:id,name,profile_photo_path')
            ->get();

        // Calculate grade statistics using existing service
        $gradeStats = $this->gradingService->calculateGradeStatistics($course);

        // Attendance statistics
        $attendanceStats = $this->calculateAttendanceStats($course);

        // Assignment statistics
        $assignmentStats = $this->calculateAssignmentStats($course, $members);

        // Quiz statistics
        $quizStats = $this->calculateQuizStats($course, $members);

        // Lesson progress
        $lessonStats = $this->calculateLessonStats($course, $members);

        // Recent activity
        $recentActivity = $this->getRecentActivity($course);

        // Pending items for instructor
        $pendingItems = $this->getPendingItems($course);

        // Appeals summary
        $appealsStats = $this->getAppealsStats($course);

        // Certificate stats
        $certificateStats = $this->getCertificateStats($course);

        return response()->json([
            'success' => true,
            'data' => [
                'course' => [
                    'id' => $course->id,
                    'name' => $course->name,
                    'code' => $course->code,
                    'finalization_status' => $course->finalization_status,
                    'total_score' => $course->total_score,
                ],
                'overview' => [
                    'total_members' => $totalMembers,
                    'active_members' => $activeMembers,
                    'total_groups' => $course->courseGroups()->count(),
                    'total_lessons' => $course->courseLessons()->count(),
                    'total_assignments' => $course->courseAssignments()->count(),
                    'total_quizzes' => $course->courseQuizzes()->count(),
                ],
                'grades' => $gradeStats,
                'attendance' => $attendanceStats,
                'assignments' => $assignmentStats,
                'quizzes' => $quizStats,
                'lessons' => $lessonStats,
                'recent_activity' => $recentActivity,
                'pending_items' => $pendingItems,
                'appeals' => $appealsStats,
                'certificates' => $certificateStats,
            ],
        ]);
    }

    /**
     * Get performance trends over time
     */
    public function getPerformanceTrends(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);

        $days = $request->get('days', 30);
        $startDate = now()->subDays($days);

        // Get assignment submissions per day
        $submissions = DB::table('course_assignment_files')
            ->join('course_assignments', 'course_assignment_files.course_assignment_id', '=', 'course_assignments.id')
            ->where('course_assignments.course_id', $course->id)
            ->where('course_assignment_files.created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(course_assignment_files.created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get attendance per day
        $attendance = DB::table('course_group_attendance_details')
            ->join('course_group_attendances', 'course_group_attendance_details.course_group_attendance_id', '=', 'course_group_attendances.id')
            ->where('course_group_attendances.course_id', $course->id)
            ->where('course_group_attendance_details.created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(course_group_attendance_details.created_at) as date'),
                DB::raw('SUM(CASE WHEN course_group_attendance_details.status = "present" THEN 1 ELSE 0 END) as present'),
                DB::raw('SUM(CASE WHEN course_group_attendance_details.status = "absent" THEN 1 ELSE 0 END) as absent'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get quiz completions per day
        $quizzes = DB::table('course_quiz_answers')
            ->join('course_quizzes', 'course_quiz_answers.course_quiz_id', '=', 'course_quizzes.id')
            ->where('course_quizzes.course_id', $course->id)
            ->where('course_quiz_answers.created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(course_quiz_answers.created_at) as date'),
                DB::raw('COUNT(DISTINCT course_quiz_answers.user_id) as completions')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end' => now()->toDateString(),
                    'days' => $days,
                ],
                'submissions' => $submissions,
                'attendance' => $attendance,
                'quiz_completions' => $quizzes,
            ],
        ]);
    }

    /**
     * Get at-risk students (low performance)
     */
    public function getAtRiskStudents(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);

        $threshold = $request->get('threshold', 50); // Below 50% is at-risk

        $members = $course->courseMembers()
            ->with('user:id,name,email,profile_photo_path')
            ->get();

        $atRisk = collect();

        foreach ($members as $member) {
            $risks = [];
            $riskScore = 0;

            // Check attendance
            $attendanceRate = $this->getMemberAttendanceRate($member, $course);
            if ($attendanceRate < $threshold) {
                $risks[] = [
                    'type' => 'attendance',
                    'value' => $attendanceRate,
                    'message' => "อัตราการเข้าเรียนต่ำ ({$attendanceRate}%)",
                ];
                $riskScore += (100 - $attendanceRate) / 2;
            }

            // Check assignment completion
            $assignmentRate = $this->getMemberAssignmentRate($member, $course);
            if ($assignmentRate < $threshold) {
                $risks[] = [
                    'type' => 'assignments',
                    'value' => $assignmentRate,
                    'message' => "การส่งงานต่ำ ({$assignmentRate}%)",
                ];
                $riskScore += (100 - $assignmentRate) / 2;
            }

            // Check current score
            $computedMax = $this->getComputedMaxTotal($course);
            $scorePercentage = $computedMax > 0
                ? (($member->achieved_score ?? 0) / $computedMax) * 100
                : 0;
            if ($scorePercentage < $threshold) {
                $risks[] = [
                    'type' => 'score',
                    'value' => round($scorePercentage, 1),
                    'message' => "คะแนนรวมต่ำ ({$scorePercentage}%)",
                ];
                $riskScore += (100 - $scorePercentage) / 2;
            }

            if (count($risks) > 0) {
                $atRisk->push([
                    'member' => [
                        'id' => $member->id,
                        'user_id' => $member->user_id,
                        'name' => $member->user->name ?? 'Unknown',
                        'email' => $member->user->email ?? null,
                        'avatar' => $member->user->avatar ?? null,
                    ],
                    'risks' => $risks,
                    'risk_score' => round($riskScore),
                    'attendance_rate' => $attendanceRate,
                    'assignment_rate' => $assignmentRate,
                    'score_percentage' => round($scorePercentage, 1),
                ]);
            }
        }

        // Sort by risk score descending
        $atRisk = $atRisk->sortByDesc('risk_score')->values();

        return response()->json([
            'success' => true,
            'data' => [
                'threshold' => $threshold,
                'total_students' => $members->count(),
                'at_risk_count' => $atRisk->count(),
                'at_risk_percentage' => $members->count() > 0
                    ? round(($atRisk->count() / $members->count()) * 100, 1)
                    : 0,
                'students' => $atRisk,
            ],
        ]);
    }

    /**
     * Get top performing students
     */
    public function getTopPerformers(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);

        $limit = $request->get('limit', 10);

        $members = $course->courseMembers()
            ->with('user:id,name,email,profile_photo_path')
            ->orderByDesc('achieved_score')
            ->limit($limit)
            ->get();

        $topPerformers = $members->map(function ($member) use ($course) {
            $computedMax = $this->getComputedMaxTotal($course);
            $scorePercentage = $computedMax > 0
                ? (($member->achieved_score ?? 0) / $computedMax) * 100
                : 0;

            return [
                'member' => [
                    'id' => $member->id,
                    'user_id' => $member->user_id,
                    'name' => $member->user->name ?? 'Unknown',
                    'avatar' => $member->user->avatar ?? null,
                ],
                'achieved_score' => $member->achieved_score ?? 0,
                'score_percentage' => round($scorePercentage, 1),
                'grade' => $member->edited_grade ?? CourseMember::calculateGradeFromPercentage($scorePercentage),
                'attendance_rate' => $this->getMemberAttendanceRate($member, $course),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $topPerformers,
        ]);
    }

    // =====================================================
    // Helper Methods
    // =====================================================

    protected function calculateAttendanceStats(Course $course): array
    {
        $attendances = CourseAttendance::where('course_id', $course->id)->get();
        $totalSessions = $attendances->count();

        if ($totalSessions === 0) {
            return [
                'total_sessions' => 0,
                'average_rate' => 0,
                'present_rate' => 0,
                'absent_rate' => 0,
                'late_rate' => 0,
            ];
        }

        $details = DB::table('attendance_details')
            ->whereIn('course_attendance_id', $attendances->pluck('id'))
            ->select(
                DB::raw('SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present'),
                DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent'),
                DB::raw('SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late'),
                DB::raw('COUNT(*) as total')
            )
            ->first();

        $total = $details->total ?: 1;

        return [
            'total_sessions' => $totalSessions,
            'total_records' => $details->total,
            'present_count' => $details->present,
            'absent_count' => $details->absent,
            'late_count' => $details->late,
            'present_rate' => round(($details->present / $total) * 100, 1),
            'absent_rate' => round(($details->absent / $total) * 100, 1),
            'late_rate' => round(($details->late / $total) * 100, 1),
            'average_rate' => round((($details->present + ($details->late * 0.5)) / $total) * 100, 1),
        ];
    }

    protected function calculateAssignmentStats(Course $course, $members): array
    {
        $assignments = $course->courseAssignments;
        $totalAssignments = $assignments->count();
        $totalMembers = $members->count();

        if ($totalAssignments === 0 || $totalMembers === 0) {
            return [
                'total' => $totalAssignments,
                'submission_rate' => 0,
                'average_score' => 0,
                'graded_count' => 0,
                'pending_count' => 0,
            ];
        }

        $submissions = DB::table('course_assignment_files')
            ->whereIn('course_assignment_id', $assignments->pluck('id'))
            ->count();

        $expectedSubmissions = $totalAssignments * $totalMembers;
        $submissionRate = $expectedSubmissions > 0
            ? round(($submissions / $expectedSubmissions) * 100, 1)
            : 0;

        // Get graded vs pending
        $graded = DB::table('course_assignment_files')
            ->whereIn('course_assignment_id', $assignments->pluck('id'))
            ->whereNotNull('score')
            ->count();

        $averageScore = DB::table('course_assignment_files')
            ->whereIn('course_assignment_id', $assignments->pluck('id'))
            ->whereNotNull('score')
            ->avg('score') ?? 0;

        return [
            'total' => $totalAssignments,
            'total_submissions' => $submissions,
            'expected_submissions' => $expectedSubmissions,
            'submission_rate' => $submissionRate,
            'average_score' => round($averageScore, 1),
            'graded_count' => $graded,
            'pending_count' => $submissions - $graded,
        ];
    }

    protected function calculateQuizStats(Course $course, $members): array
    {
        $quizzes = $course->courseQuizzes;
        $totalQuizzes = $quizzes->count();
        $totalMembers = $members->count();

        if ($totalQuizzes === 0 || $totalMembers === 0) {
            return [
                'total' => $totalQuizzes,
                'completion_rate' => 0,
                'average_score' => 0,
                'pass_rate' => 0,
            ];
        }

        $attempts = DB::table('course_quiz_answers')
            ->whereIn('course_quiz_id', $quizzes->pluck('id'))
            ->select('course_quiz_id', 'user_id')
            ->groupBy('course_quiz_id', 'user_id')
            ->get();

        $expectedAttempts = $totalQuizzes * $totalMembers;
        $completionRate = $expectedAttempts > 0
            ? round(($attempts->count() / $expectedAttempts) * 100, 1)
            : 0;

        // Calculate average score per quiz
        $avgScores = DB::table('course_quiz_answers')
            ->whereIn('course_quiz_id', $quizzes->pluck('id'))
            ->select('course_quiz_id', 'user_id', DB::raw('SUM(score) as total_score'))
            ->groupBy('course_quiz_id', 'user_id')
            ->get();

        $averageScore = $avgScores->avg('total_score') ?? 0;

        return [
            'total' => $totalQuizzes,
            'total_attempts' => $attempts->count(),
            'completion_rate' => $completionRate,
            'average_score' => round($averageScore, 1),
        ];
    }

    protected function calculateLessonStats(Course $course, $members): array
    {
        $lessons = $course->courseLessons;
        $totalLessons = $lessons->count();
        $totalMembers = $members->count();

        if ($totalLessons === 0 || $totalMembers === 0) {
            return [
                'total' => $totalLessons,
                'completion_rate' => 0,
            ];
        }

        $completions = DB::table('lesson_member_completes')
            ->whereIn('course_lesson_id', $lessons->pluck('id'))
            ->count();

        $expectedCompletions = $totalLessons * $totalMembers;
        $completionRate = $expectedCompletions > 0
            ? round(($completions / $expectedCompletions) * 100, 1)
            : 0;

        return [
            'total' => $totalLessons,
            'total_completions' => $completions,
            'expected_completions' => $expectedCompletions,
            'completion_rate' => $completionRate,
        ];
    }

    protected function getRecentActivity(Course $course): array
    {
        $activities = collect();

        // Recent assignment submissions
        $submissions = DB::table('course_assignment_files')
            ->join('course_assignments', 'course_assignment_files.course_assignment_id', '=', 'course_assignments.id')
            ->join('users', 'course_assignment_files.user_id', '=', 'users.id')
            ->where('course_assignments.course_id', $course->id)
            ->orderByDesc('course_assignment_files.created_at')
            ->limit(5)
            ->select(
                'course_assignment_files.id',
                'users.name as user_name',
                'course_assignments.name as assignment_name',
                'course_assignment_files.created_at',
                DB::raw("'assignment_submitted' as type")
            )
            ->get();

        foreach ($submissions as $s) {
            $activities->push([
                'type' => 'assignment_submitted',
                'icon' => 'heroicons:document-arrow-up',
                'color' => 'blue',
                'message' => "{$s->user_name} ส่งงาน \"{$s->assignment_name}\"",
                'created_at' => $s->created_at,
            ]);
        }

        // Recent quiz completions
        $quizzes = DB::table('course_quiz_answers')
            ->join('course_quizzes', 'course_quiz_answers.course_quiz_id', '=', 'course_quizzes.id')
            ->join('users', 'course_quiz_answers.user_id', '=', 'users.id')
            ->where('course_quizzes.course_id', $course->id)
            ->orderByDesc('course_quiz_answers.created_at')
            ->limit(5)
            ->select(
                'course_quiz_answers.id',
                'users.name as user_name',
                'course_quizzes.name as quiz_name',
                'course_quiz_answers.created_at',
                DB::raw("'quiz_completed' as type")
            )
            ->get();

        foreach ($quizzes as $q) {
            $activities->push([
                'type' => 'quiz_completed',
                'icon' => 'heroicons:academic-cap',
                'color' => 'green',
                'message' => "{$q->user_name} ทำแบบทดสอบ \"{$q->quiz_name}\"",
                'created_at' => $q->created_at,
            ]);
        }

        // Sort by date and take 10
        return $activities
            ->sortByDesc('created_at')
            ->take(10)
            ->values()
            ->toArray();
    }

    protected function getPendingItems(Course $course): array
    {
        // Pending assignment grading
        $pendingAssignments = DB::table('course_assignment_files')
            ->join('course_assignments', 'course_assignment_files.course_assignment_id', '=', 'course_assignments.id')
            ->where('course_assignments.course_id', $course->id)
            ->whereNull('course_assignment_files.score')
            ->count();

        // Pending appeals
        $pendingAppeals = GradeAppeal::where('course_id', $course->id)
            ->whereIn('status', ['pending', 'reviewing'])
            ->count();

        return [
            'pending_assignment_grading' => $pendingAssignments,
            'pending_appeals' => $pendingAppeals,
            'total_pending' => $pendingAssignments + $pendingAppeals,
        ];
    }

    protected function getAppealsStats(Course $course): array
    {
        $appeals = GradeAppeal::where('course_id', $course->id)->get();

        return [
            'total' => $appeals->count(),
            'pending' => $appeals->where('status', 'pending')->count(),
            'reviewing' => $appeals->where('status', 'reviewing')->count(),
            'approved' => $appeals->where('status', 'approved')->count(),
            'rejected' => $appeals->where('status', 'rejected')->count(),
        ];
    }

    protected function getCertificateStats(Course $course): array
    {
        $certificates = CourseCertificate::where('course_id', $course->id)->get();

        return [
            'total_issued' => $certificates->count(),
            'downloaded' => $certificates->where('downloaded_at', '!=', null)->count(),
            'eligible_not_issued' => $course->courseMembers()
                ->where('certificate_eligible', true)
                ->whereDoesntHave('certificates', function ($q) use ($course) {
                    $q->where('course_id', $course->id);
                })
                ->count(),
        ];
    }

    protected function getMemberAttendanceRate(CourseMember $member, Course $course): float
    {
        $attendances = CourseAttendance::where('course_id', $course->id)
            ->where('group_id', $member->group)
            ->pluck('id');

        if ($attendances->isEmpty()) {
            return 100; // No attendance required
        }

        $present = DB::table('attendance_details')
            ->whereIn('course_attendance_id', $attendances)
            ->where('course_member_id', $member->id)
            ->whereIn('status', ['present', 'late'])
            ->count();

        $total = DB::table('attendance_details')
            ->whereIn('course_attendance_id', $attendances)
            ->where('course_member_id', $member->id)
            ->count();

        return $total > 0 ? round(($present / $total) * 100, 1) : 100;
    }

    protected function getMemberAssignmentRate(CourseMember $member, Course $course): float
    {
        $totalAssignments = $course->courseAssignments()->count();

        if ($totalAssignments === 0) {
            return 100;
        }

        $submitted = DB::table('course_assignment_files')
            ->whereIn('course_assignment_id', $course->courseAssignments()->pluck('id'))
            ->where('user_id', $member->user_id)
            ->count();

        return round(($submitted / $totalAssignments) * 100, 1);
    }

    protected function getComputedMaxTotal(Course $course): float
    {
        $lessons = $course->courseLessons()->with(['assignments', 'questions'])->get();

        return $course->courseAssignments->sum('points')
            + $lessons->flatMap->assignments->sum('points')
            + $course->courseQuizzes->sum('total_score')
            + $lessons->flatMap->questions->sum('points');
    }
}
