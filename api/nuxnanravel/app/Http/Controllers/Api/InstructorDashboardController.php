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
use Illuminate\Support\Collection;
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
        // course_members ไม่มี soft delete · active = อนุมัติแล้ว (course_member_status = 1)
        $activeMembers = $course->courseMembers()->where('course_member_status', 1)->count();

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
                    // งานของคอร์ส = แนบกับคอร์สโดยตรง + แนบกับบทเรียน (ให้ตรงกับ assignments.total ด้านล่าง)
                    'total_assignments' => $this->courseAssignmentIds($course)->count(),
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

        // Get assignment submissions per day — assignment_answers ของงานในคอร์สนี้
        $submissions = DB::table('assignment_answers')
            ->whereIn('assignment_id', $this->courseAssignmentIds($course))
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get attendance per day — attendance_details มี course_id ตรง
        $attendance = DB::table('attendance_details')
            ->where('course_id', $course->id)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present'),
                DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get quiz completions per day — course_quiz_results มี course_id ตรง
        $quizzes = DB::table('course_quiz_results')
            ->where('course_id', $course->id)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(DISTINCT user_id) as completions')
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

    /**
     * id ของ "งาน" ทั้งหมดของคอร์สนี้ — Assignment เป็น polymorphic (assignmentable)
     * แนบได้ทั้งกับคอร์สโดยตรง (Course::courseAssignments) และกับบทเรียนของคอร์ส (Lesson::assignments)
     * ไม่มีตาราง course_assignments/course_assignment_files จริง — คำตอบอยู่ที่ assignment_answers (assignment_id)
     */
    protected function courseAssignmentIds(Course $course): Collection
    {
        $lessons = $course->courseLessons()->with('assignments:id,assignmentable_id,assignmentable_type')->get();

        return $course->courseAssignments()->pluck('id')
            ->merge($lessons->flatMap->assignments->pluck('id'))
            ->unique()
            ->values();
    }

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
        $assignmentIds = $this->courseAssignmentIds($course);
        $totalAssignments = $assignmentIds->count();
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

        $submissions = DB::table('assignment_answers')
            ->whereIn('assignment_id', $assignmentIds)
            ->count();

        $expectedSubmissions = $totalAssignments * $totalMembers;
        $submissionRate = $expectedSubmissions > 0
            ? round(($submissions / $expectedSubmissions) * 100, 1)
            : 0;

        // ตรวจแล้ว (graded) = ให้คะแนนแล้ว (points ไม่ null)
        $graded = DB::table('assignment_answers')
            ->whereIn('assignment_id', $assignmentIds)
            ->whereNotNull('points')
            ->count();

        $averageScore = DB::table('assignment_answers')
            ->whereIn('assignment_id', $assignmentIds)
            ->whereNotNull('points')
            ->avg('points') ?? 0;

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

        // 1 แถวใน course_quiz_results = 1 ครั้งที่ทำแบบทดสอบ (มี course_id ตรง) — นับคู่ (quiz,user) ที่ไม่ซ้ำ
        $attempts = DB::table('course_quiz_results')
            ->where('course_id', $course->id)
            ->select('quiz_id', 'user_id')
            ->distinct()
            ->get();

        $expectedAttempts = $totalQuizzes * $totalMembers;
        $completionRate = $expectedAttempts > 0
            ? round(($attempts->count() / $expectedAttempts) * 100, 1)
            : 0;

        // percentage เก็บเป็น 0–100 ต่อครั้งอยู่แล้ว — เฉลี่ยตรง ๆ
        $averageScore = DB::table('course_quiz_results')
            ->where('course_id', $course->id)
            ->avg('percentage') ?? 0;

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

        // ไม่มีตาราง lesson_member_completes จริง — ความคืบหน้าอยู่ที่ lesson_progress (status = completed)
        $completions = DB::table('lesson_progress')
            ->whereIn('lesson_id', $lessons->pluck('id'))
            ->where('status', 'completed')
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

        // Recent assignment submissions — assignment_answers → assignments (title)
        $submissions = DB::table('assignment_answers')
            ->join('assignments', 'assignment_answers.assignment_id', '=', 'assignments.id')
            ->join('users', 'assignment_answers.user_id', '=', 'users.id')
            ->whereIn('assignment_answers.assignment_id', $this->courseAssignmentIds($course))
            ->orderByDesc('assignment_answers.created_at')
            ->limit(5)
            ->select(
                'assignment_answers.id',
                'users.name as user_name',
                'assignments.title as assignment_name',
                'assignment_answers.created_at',
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

        // Recent quiz completions — course_quiz_results มี course_id ตรง · course_quizzes ใช้คอลัมน์ title
        $quizzes = DB::table('course_quiz_results')
            ->join('course_quizzes', 'course_quiz_results.quiz_id', '=', 'course_quizzes.id')
            ->join('users', 'course_quiz_results.user_id', '=', 'users.id')
            ->where('course_quiz_results.course_id', $course->id)
            ->orderByDesc('course_quiz_results.created_at')
            ->limit(5)
            ->select(
                'course_quiz_results.id',
                'users.name as user_name',
                'course_quizzes.title as quiz_name',
                'course_quiz_results.created_at',
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
        // Pending assignment grading = คำตอบที่ยังไม่ให้คะแนน (points null)
        $pendingAssignments = DB::table('assignment_answers')
            ->whereIn('assignment_id', $this->courseAssignmentIds($course))
            ->whereNull('points')
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
        $issuedMemberIds = $certificates->pluck('course_member_id')->filter()->unique();

        // course_members ไม่มีคอลัมน์ certificate_eligible และ CourseMember ไม่มี relation certificates()
        // ใช้ "เรียนจบแล้ว" (completion_status = completed) เป็นเกณฑ์มีสิทธิ์รับเกียรติบัตร · certificate ผูกด้วย course_member_id
        $eligibleNotIssued = $course->courseMembers()
            ->where('completion_status', 'completed')
            ->whereNotIn('id', $issuedMemberIds)
            ->count();

        return [
            'total_issued' => $certificates->count(),
            'downloaded' => $certificates->where('download_count', '>', 0)->count(),
            'eligible_not_issued' => $eligibleNotIssued,
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
        $assignmentIds = $this->courseAssignmentIds($course);
        $totalAssignments = $assignmentIds->count();

        if ($totalAssignments === 0) {
            return 100;
        }

        $submitted = DB::table('assignment_answers')
            ->whereIn('assignment_id', $assignmentIds)
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
