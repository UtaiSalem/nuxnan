<?php

namespace App\Http\Controllers\Api;

use App\Exports\CourseAttendanceExport;
use App\Exports\CourseCertificatesExport;
use App\Exports\CourseFullReportExport;
use App\Exports\CourseGradesExport;
use App\Exports\CourseProgressExport;
use App\Http\Controllers\Controller;
use App\Models\AttendanceDetail;
use App\Models\Course;
use App\Models\CourseAttendance;
use App\Models\CourseCertificate;
use App\Models\CourseMember;
use App\Services\CourseGradingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Course Report Export Controller
 *
 * สร้างรายงานและ Export ข้อมูลรายวิชา
 */
class CourseReportController extends Controller
{
    use AuthorizesRequests;

    protected CourseGradingService $gradingService;

    public function __construct(CourseGradingService $gradingService)
    {
        $this->gradingService = $gradingService;
    }

    /**
     * Get available report types
     */
    public function getReportTypes(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);

        return response()->json([
            'success' => true,
            'data' => [
                'reports' => [
                    [
                        'id' => 'grades',
                        'name' => 'รายงานผลการเรียน',
                        'description' => 'รายชื่อนักเรียนพร้อมคะแนนและเกรด',
                        'formats' => ['excel', 'pdf'],
                    ],
                    [
                        'id' => 'attendance',
                        'name' => 'รายงานการเข้าเรียน',
                        'description' => 'สถิติการเข้าเรียนของนักเรียนแต่ละคน',
                        'formats' => ['excel', 'pdf'],
                    ],
                    [
                        'id' => 'progress',
                        'name' => 'รายงานความคืบหน้า',
                        'description' => 'สถานะการเรียนรู้ บทเรียน งาน และแบบทดสอบ',
                        'formats' => ['excel'],
                    ],
                    [
                        'id' => 'full',
                        'name' => 'รายงานฉบับเต็ม',
                        'description' => 'รายงานรวมทุกข้อมูลของรายวิชา',
                        'formats' => ['excel', 'pdf'],
                    ],
                    [
                        'id' => 'certificates',
                        'name' => 'รายงานใบประกาศ',
                        'description' => 'รายชื่อผู้ที่ได้รับใบประกาศ',
                        'formats' => ['excel'],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Export grades report
     */
    public function exportGrades(Request $request, Course $course)
    {
        $this->authorize('manage', $course);

        $format = $request->get('format', 'excel');
        $groupId = $request->get('group_id');

        // Get course members with scores
        $query = $course->courseMembers()
            ->with(['user:id,name,email,profile_photo_path', 'group:id,name']);

        if ($groupId) {
            $query->where('group_id', $groupId);
        }

        $members = $query->get();

        // Calculate grades for each member
        $data = $this->prepareGradesData($course, $members);

        $filename = "grades-{$course->code}-".now()->format('Ymd');

        if ($format === 'pdf') {
            return $this->generateGradesPdf($course, $data, $filename);
        }

        return Excel::download(
            new CourseGradesExport($data, $course),
            "{$filename}.xlsx"
        );
    }

    /**
     * Export attendance report
     */
    public function exportAttendance(Request $request, Course $course)
    {
        $this->authorize('manage', $course);

        $format = $request->get('format', 'excel');
        $groupId = $request->get('group_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Get attendance sessions
        $attendanceQuery = CourseAttendance::where('course_id', $course->id);

        if ($groupId) {
            $attendanceQuery->where('group_id', $groupId);
        }
        if ($startDate) {
            $attendanceQuery->whereDate('date', '>=', $startDate);
        }
        if ($endDate) {
            $attendanceQuery->whereDate('date', '<=', $endDate);
        }

        $attendances = $attendanceQuery->orderBy('date')->get();

        // Get members
        $membersQuery = $course->courseMembers()
            ->with(['user:id,name,email', 'group:id,name']);

        if ($groupId) {
            $membersQuery->where('group_id', $groupId);
        }

        $members = $membersQuery->get();

        // Prepare data
        $data = $this->prepareAttendanceData($attendances, $members);

        $filename = "attendance-{$course->code}-".now()->format('Ymd');

        if ($format === 'pdf') {
            return $this->generateAttendancePdf($course, $data, $attendances, $filename);
        }

        return Excel::download(
            new CourseAttendanceExport($data, $course, $attendances),
            "{$filename}.xlsx"
        );
    }

    /**
     * Export progress report
     */
    public function exportProgress(Request $request, Course $course)
    {
        $this->authorize('manage', $course);

        $groupId = $request->get('group_id');

        $membersQuery = $course->courseMembers()
            ->with(['user:id,name,email', 'group:id,name']);

        if ($groupId) {
            $membersQuery->where('group_id', $groupId);
        }

        $members = $membersQuery->get();

        $data = $this->prepareProgressData($course, $members);

        $filename = "progress-{$course->code}-".now()->format('Ymd');

        return Excel::download(
            new CourseProgressExport($data, $course),
            "{$filename}.xlsx"
        );
    }

    /**
     * Export full report
     */
    public function exportFull(Request $request, Course $course)
    {
        $this->authorize('manage', $course);

        $format = $request->get('format', 'excel');
        $groupId = $request->get('group_id');

        $membersQuery = $course->courseMembers()
            ->with(['user:id,name,email,profile_photo_path', 'group:id,name']);

        if ($groupId) {
            $membersQuery->where('group_id', $groupId);
        }

        $members = $membersQuery->get();

        // Prepare all data
        $gradesData = $this->prepareGradesData($course, $members);
        $attendances = CourseAttendance::where('course_id', $course->id)
            ->when($groupId, fn ($q) => $q->where('group_id', $groupId))
            ->orderBy('date')
            ->get();
        $attendanceData = $this->prepareAttendanceData($attendances, $members);
        $progressData = $this->prepareProgressData($course, $members);

        // Statistics
        $stats = $this->gradingService->calculateGradeStatistics($course);

        $filename = "full-report-{$course->code}-".now()->format('Ymd');

        if ($format === 'pdf') {
            return $this->generateFullReportPdf($course, [
                'grades' => $gradesData,
                'attendance' => $attendanceData,
                'progress' => $progressData,
                'stats' => $stats,
            ], $filename);
        }

        return Excel::download(
            new CourseFullReportExport($course, $gradesData, $attendanceData, $progressData, $stats),
            "{$filename}.xlsx"
        );
    }

    /**
     * Export certificates report
     */
    public function exportCertificates(Request $request, Course $course)
    {
        $this->authorize('manage', $course);

        $certificates = CourseCertificate::where('course_id', $course->id)
            ->with(['member.user:id,name,email', 'member.group:id,name'])
            ->orderBy('issued_at')
            ->get();

        $data = $certificates->map(function ($cert) {
            return [
                'certificate_number' => $cert->certificate_number,
                'verification_code' => $cert->verification_code,
                'student_name' => $cert->member->user->name ?? '-',
                'student_email' => $cert->member->user->email ?? '-',
                'group' => $cert->member->group->name ?? '-',
                'grade' => $cert->grade,
                'issued_at' => $cert->issued_at?->format('d/m/Y'),
                'downloaded_at' => $cert->downloaded_at?->format('d/m/Y'),
                'status' => $cert->status,
            ];
        })->toArray();

        $filename = "certificates-{$course->code}-".now()->format('Ymd');

        return Excel::download(
            new CourseCertificatesExport($data, $course),
            "{$filename}.xlsx"
        );
    }

    // ===========================================
    // Helper Methods
    // ===========================================

    protected function prepareGradesData(Course $course, $members): array
    {
        $data = [];

        // Compute actual max total from all score sources
        $lessons = $course->courseLessons()->with(['assignments', 'questions'])->get();
        $computedMaxTotal = $course->courseAssignments->sum('points')
            + $lessons->flatMap->assignments->sum('points')
            + $course->courseQuizzes->sum('total_score')
            + $lessons->flatMap->questions->sum('points');

        foreach ($members as $member) {
            $percentage = $computedMaxTotal > 0
                ? (($member->achieved_score ?? 0) / $computedMaxTotal) * 100
                : 0;
            $percentage = min(100, max(0, $percentage));

            $calculatedGrade = CourseMember::calculateGradeFromPercentage($percentage);
            $finalGrade = $member->edited_grade ?? $calculatedGrade;
            $gradeName = CourseMember::getGradeNameFromGrade($finalGrade);

            $data[] = [
                'member_code' => $member->member_code ?? '-',
                'name' => $member->user->name ?? '-',
                'email' => $member->user->email ?? '-',
                'group' => $member->group->name ?? '-',
                'achieved_score' => $member->achieved_score ?? 0,
                'total_score' => $computedMaxTotal,
                'percentage' => round($percentage),
                'calculated_grade' => $calculatedGrade,
                'final_grade' => $finalGrade,
                'grade_name' => $gradeName,
                'bonus_points' => $member->bonus_points ?? 0,
                'completion_status' => $member->completion_status ?? 'pending',
            ];
        }

        return $data;
    }

    protected function prepareAttendanceData($attendances, $members): array
    {
        $attendanceIds = $attendances->pluck('id');

        $details = AttendanceDetail::whereIn('course_attendance_id', $attendanceIds)
            ->get()
            ->groupBy('course_member_id');

        $data = [];

        foreach ($members as $member) {
            $memberDetails = $details[$member->id] ?? collect();

            $present = $memberDetails->where('status', 1)->count();
            $late = $memberDetails->where('status', 2)->count();
            $absent = $memberDetails->where('status', 0)->count();
            $leave = $memberDetails->where('status', 3)->count();
            $total = $attendances->count();

            $rate = $total > 0
                ? round((($present + ($late * 0.5)) / $total) * 100, 1)
                : 0;

            $data[] = [
                'member_code' => $member->member_code ?? '-',
                'name' => $member->user->name ?? '-',
                'group' => $member->group->name ?? '-',
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'leave' => $leave,
                'total_sessions' => $total,
                'attendance_rate' => $rate,
            ];
        }

        return $data;
    }

    protected function prepareProgressData(Course $course, $members): array
    {
        $course->load(['courseLessons', 'courseAssignments', 'courseQuizzes']);

        $totalLessons = $course->courseLessons->count();
        $totalAssignments = $course->courseAssignments->count();
        $totalQuizzes = $course->courseQuizzes->count();

        // Get progress data
        $lessonProgress = DB::table('lesson_member_completes')
            ->whereIn('course_lesson_id', $course->courseLessons->pluck('id'))
            ->get()
            ->groupBy('user_id');

        $assignmentSubmissions = DB::table('course_assignment_files')
            ->whereIn('course_assignment_id', $course->courseAssignments->pluck('id'))
            ->get()
            ->groupBy('user_id');

        $quizAttempts = DB::table('course_quiz_answers')
            ->whereIn('course_quiz_id', $course->courseQuizzes->pluck('id'))
            ->select('course_quiz_id', 'user_id')
            ->groupBy('course_quiz_id', 'user_id')
            ->get()
            ->groupBy('user_id');

        $data = [];

        foreach ($members as $member) {
            $userId = $member->user_id;

            $lessonsCompleted = isset($lessonProgress[$userId]) ? $lessonProgress[$userId]->count() : 0;
            $assignmentsCompleted = isset($assignmentSubmissions[$userId])
                ? $assignmentSubmissions[$userId]->unique('course_assignment_id')->count()
                : 0;
            $quizzesCompleted = isset($quizAttempts[$userId]) ? $quizAttempts[$userId]->count() : 0;

            $data[] = [
                'member_code' => $member->member_code ?? '-',
                'name' => $member->user->name ?? '-',
                'group' => $member->group->name ?? '-',
                'lessons_completed' => $lessonsCompleted,
                'total_lessons' => $totalLessons,
                'lessons_progress' => $totalLessons > 0 ? round(($lessonsCompleted / $totalLessons) * 100, 1) : 0,
                'assignments_completed' => $assignmentsCompleted,
                'total_assignments' => $totalAssignments,
                'assignments_progress' => $totalAssignments > 0 ? round(($assignmentsCompleted / $totalAssignments) * 100, 1) : 0,
                'quizzes_completed' => $quizzesCompleted,
                'total_quizzes' => $totalQuizzes,
                'quizzes_progress' => $totalQuizzes > 0 ? round(($quizzesCompleted / $totalQuizzes) * 100, 1) : 0,
            ];
        }

        return $data;
    }

    protected function generateGradesPdf(Course $course, array $data, string $filename)
    {
        $stats = $this->gradingService->calculateGradeStatistics($course);

        $pdf = Pdf::loadView('reports.grades', [
            'course' => $course,
            'data' => $data,
            'stats' => $stats,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download("{$filename}.pdf");
    }

    protected function generateAttendancePdf(Course $course, array $data, $attendances, string $filename)
    {
        $pdf = Pdf::loadView('reports.attendance', [
            'course' => $course,
            'data' => $data,
            'attendances' => $attendances,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download("{$filename}.pdf");
    }

    protected function generateFullReportPdf(Course $course, array $allData, string $filename)
    {
        $pdf = Pdf::loadView('reports.full', [
            'course' => $course,
            'grades' => $allData['grades'],
            'attendance' => $allData['attendance'],
            'progress' => $allData['progress'],
            'stats' => $allData['stats'],
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("{$filename}.pdf");
    }
}
