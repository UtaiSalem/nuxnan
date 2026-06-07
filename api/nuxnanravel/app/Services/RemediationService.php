<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseQuizResult;
use App\Models\CourseRemediationEnrollment;
use App\Models\CourseRemediationSession;
use App\Models\GradeEditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RemediationService
{
    protected CourseGradingService $gradingService;

    protected AttendanceEligibilityService $eligibilityService;

    public function __construct(CourseGradingService $gradingService, AttendanceEligibilityService $eligibilityService)
    {
        $this->gradingService = $gradingService;
        $this->eligibilityService = $eligibilityService;
    }

    /**
     * Create a remediation session
     */
    public function createSession(Course $course, array $data, User $creator): CourseRemediationSession
    {
        return CourseRemediationSession::create([
            'course_id' => $course->id,
            'created_by' => $creator->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'start_at' => $data['start_at'],
            'end_at' => $data['end_at'],
            'registration_deadline' => $data['registration_deadline'] ?? null,
            'type' => $data['type'] ?? CourseRemediationSession::TYPE_EXAM_RETAKE,
            'eligible_grades' => $data['eligible_grades'] ?? ['F', 'D', 'D+'],
            'min_score_to_remediate' => $data['min_score_to_remediate'] ?? null,
            'max_grade_achievable' => $data['max_grade_achievable'] ?? $course->remediation_max_grade ?? 'C',
            'max_participants' => $data['max_participants'] ?? null,
            'passing_score' => $data['passing_score'] ?? 60,
            'status' => CourseRemediationSession::STATUS_DRAFT,
            'location' => $data['location'] ?? null,
            'is_online' => $data['is_online'] ?? false,
            'online_link' => $data['online_link'] ?? null,
            'instructions' => $data['instructions'] ?? null,
            'attachments' => $data['attachments'] ?? null,
        ]);
    }

    /**
     * Open session for registration
     */
    public function openSession(CourseRemediationSession $session): CourseRemediationSession
    {
        $session->update([
            'status' => CourseRemediationSession::STATUS_OPEN,
        ]);

        return $session->fresh();
    }

    /**
     * Get eligible students for a remediation session
     */
    public function getEligibleStudents(CourseRemediationSession $session): array
    {
        $course = $session->course;
        $eligibleGrades = $session->eligible_grades ?? [];

        $query = $course->members()
            ->with('user:id,name,email,profile_photo_path')
            ->whereIn('completion_status', ['failed', 'completed']);

        if (! empty($eligibleGrades)) {
            $query->whereIn(DB::raw('COALESCE(final_grade, draft_grade)'), $eligibleGrades);
        }

        if ($session->min_score_to_remediate) {
            $query->where(DB::raw('COALESCE(final_percentage, draft_percentage)'), '>=', $session->min_score_to_remediate);
        }

        // Check max attempts
        $maxAttempts = $course->max_remediation_attempts ?? 2;
        $query->where('remediation_attempts', '<', $maxAttempts);

        // Exclude already enrolled
        $enrolledIds = $session->enrollments()->pluck('course_member_id');
        if ($enrolledIds->isNotEmpty()) {
            $query->whereNotIn('id', $enrolledIds);
        }

        return $query->get()->map(function ($member) {
            return [
                'member_id' => $member->id,
                'user' => $member->user,
                'grade' => $member->final_grade ?? $member->draft_grade,
                'score' => $member->final_percentage ?? $member->draft_percentage,
                'remediation_attempts' => $member->remediation_attempts,
            ];
        })->toArray();
    }

    /**
     * Enroll student in remediation
     */
    public function enrollStudent(CourseRemediationSession $session, CourseMember $member, bool $force = false): CourseRemediationEnrollment
    {
        // Validate eligibility
        $course = $session->course;
        $grade = $member->final_grade ?? $member->draft_grade;

        if (! $force && ! $session->isGradeEligible($grade)) {
            throw new \Exception("เกรด {$grade} ไม่มีสิทธิ์แก้ตัวในรอบนี้");
        }

        if (! $force && ! $session->isRegistrationOpen()) {
            throw new \Exception('การลงทะเบียนแก้ตัวปิดแล้ว');
        }

        $maxAttempts = $course->max_remediation_attempts ?? 2;
        if (! $force && $member->remediation_attempts >= $maxAttempts) {
            throw new \Exception("ใช้สิทธิ์แก้ตัวครบ {$maxAttempts} ครั้งแล้ว");
        }

        // Check if already enrolled
        $existing = CourseRemediationEnrollment::where('remediation_session_id', $session->id)
            ->where('course_member_id', $member->id)
            ->first();

        if ($existing) {
            throw new \Exception('ลงทะเบียนแก้ตัวในรอบนี้แล้ว');
        }

        $enrollment = CourseRemediationEnrollment::create([
            'remediation_session_id' => $session->id,
            'course_member_id' => $member->id,
            'student_id' => $member->user_id,
            'original_grade' => $grade,
            'original_score' => $member->final_percentage ?? $member->draft_percentage,
            'status' => CourseRemediationEnrollment::STATUS_ENROLLED,
            'enrolled_at' => now(),
            'attempt_number' => $member->remediation_attempts + 1,
        ]);

        // Update member status
        $member->update([
            'completion_status' => 'remediation',
            'original_grade' => $member->original_grade ?? $grade,
        ]);

        return $enrollment;
    }

    /**
     * Start the remediation session
     */
    public function startSession(CourseRemediationSession $session): CourseRemediationSession
    {
        $session->update([
            'status' => CourseRemediationSession::STATUS_IN_PROGRESS,
        ]);

        return $session->fresh();
    }

    /**
     * Mark student as attended
     */
    public function markAttendance(CourseRemediationEnrollment $enrollment): CourseRemediationEnrollment
    {
        return $enrollment->markAttended();
    }

    /**
     * Submit work for assignment/project type
     */
    public function submitWork(
        CourseRemediationEnrollment $enrollment,
        array $submission,
        ?string $notes = null
    ): CourseRemediationEnrollment {
        return $enrollment->submit($submission, $notes);
    }

    /**
     * Grade a remediation enrollment
     */
    public function gradeEnrollment(
        CourseRemediationEnrollment $enrollment,
        float $score,
        User $grader,
        ?string $notes = null
    ): CourseRemediationEnrollment {
        $session = $enrollment->remediationSession;
        $member = $enrollment->courseMember;

        // Calculate grade (capped at max achievable)
        $gradeResult = $this->gradingService->calculateGrade($score);
        $maxGrade = $session->max_grade_achievable;

        // Cap the grade
        $gradeOrder = ['F' => 0, 'D' => 1, 'D+' => 2, 'C' => 3, 'C+' => 4, 'B' => 5, 'B+' => 6, 'A' => 7];
        if (isset($gradeOrder[$gradeResult['grade']]) && isset($gradeOrder[$maxGrade])) {
            if ($gradeOrder[$gradeResult['grade']] > $gradeOrder[$maxGrade]) {
                $gradeResult['grade'] = $maxGrade;
                // Adjust grade point accordingly
                $gradeResult['grade_point'] = $this->getGradePoint($maxGrade);
            }
        }

        // Grade the enrollment
        $enrollment->grade($score, $gradeResult['grade'], $grader, $notes);

        // Update the course member if passed
        if ($enrollment->status === CourseRemediationEnrollment::STATUS_PASSED) {
            return DB::transaction(function () use ($member, $score, $gradeResult, $grader, $enrollment, $session) {
                GradeEditLog::log(
                    $member,
                    $grader,
                    GradeEditLog::TYPE_REMEDIATION_RESULT,
                    $member->final_percentage,
                    $member->final_grade,
                    $member->final_grade_point,
                    $score,
                    $gradeResult['grade'],
                    $gradeResult['grade_point'],
                    'ผลการแก้ตัว',
                    null,
                    null,
                    $enrollment->id
                );

                $member->update([
                    'final_percentage' => $score,
                    'final_earned_score' => $score,
                    'final_grade' => $gradeResult['grade'],
                    'final_grade_point' => $gradeResult['grade_point'],
                    'completion_status' => 'completed',
                    'completed_at' => now(),
                    'remediation_attempts' => $member->remediation_attempts + 1,
                ]);

                if ($member->eligibility_status === 'ineligible') {
                    $this->eligibilityService->unlockByRemediation($member, $session->id, $grader);
                }

                // Grant retake if session is linked to a quiz
                if ($session->quiz_id !== null) {
                    $quizResult = CourseQuizResult::firstOrCreate(
                        [
                            'user_id' => $member->user_id,
                            'course_id' => $member->course_id,
                            'quiz_id' => $session->quiz_id,
                        ],
                        ['status' => 0]
                    );

                    $quizResult->update([
                        'retake_unlocked_at' => now(),
                        'retake_used_at' => null,
                        'retake_granted_by_enrollment_id' => $enrollment->id,
                    ]);
                }

                return $enrollment->fresh();
            });
        } else {
            // Failed remediation
            $member->update([
                'completion_status' => 'failed',
                'remediation_attempts' => $member->remediation_attempts + 1,
            ]);
        }

        return $enrollment->fresh();
    }

    /**
     * Get grade point for a grade letter
     */
    protected function getGradePoint(string $grade): float
    {
        $points = [
            'A' => 4.00, 'B+' => 3.50, 'B' => 3.00, 'C+' => 2.50,
            'C' => 2.00, 'D+' => 1.50, 'D' => 1.00, 'F' => 0.00,
        ];

        return $points[$grade] ?? 0;
    }

    /**
     * Complete the remediation session
     */
    public function completeSession(CourseRemediationSession $session): CourseRemediationSession
    {
        // Mark all no-shows
        $session->enrollments()
            ->where('status', CourseRemediationEnrollment::STATUS_ENROLLED)
            ->orWhere('status', CourseRemediationEnrollment::STATUS_CONFIRMED)
            ->update(['status' => CourseRemediationEnrollment::STATUS_NO_SHOW]);

        $session->update([
            'status' => CourseRemediationSession::STATUS_COMPLETED,
        ]);

        return $session->fresh();
    }

    /**
     * Get remediation statistics for a session
     */
    public function getSessionStatistics(CourseRemediationSession $session): array
    {
        $enrollments = $session->enrollments;

        $stats = [
            'total_enrolled' => $enrollments->count(),
            'attended' => $enrollments->whereIn('status', [
                CourseRemediationEnrollment::STATUS_ATTENDED,
                CourseRemediationEnrollment::STATUS_SUBMITTED,
                CourseRemediationEnrollment::STATUS_GRADED,
                CourseRemediationEnrollment::STATUS_PASSED,
                CourseRemediationEnrollment::STATUS_FAILED,
            ])->count(),
            'submitted' => $enrollments->whereIn('status', [
                CourseRemediationEnrollment::STATUS_SUBMITTED,
                CourseRemediationEnrollment::STATUS_GRADED,
                CourseRemediationEnrollment::STATUS_PASSED,
                CourseRemediationEnrollment::STATUS_FAILED,
            ])->count(),
            'graded' => $enrollments->whereIn('status', [
                CourseRemediationEnrollment::STATUS_PASSED,
                CourseRemediationEnrollment::STATUS_FAILED,
            ])->count(),
            'passed' => $enrollments->where('status', CourseRemediationEnrollment::STATUS_PASSED)->count(),
            'failed' => $enrollments->where('status', CourseRemediationEnrollment::STATUS_FAILED)->count(),
            'no_show' => $enrollments->where('status', CourseRemediationEnrollment::STATUS_NO_SHOW)->count(),
            'cancelled' => $enrollments->where('status', CourseRemediationEnrollment::STATUS_CANCELLED)->count(),
        ];

        $gradedEnrollments = $enrollments->whereIn('status', [
            CourseRemediationEnrollment::STATUS_PASSED,
            CourseRemediationEnrollment::STATUS_FAILED,
        ]);

        if ($gradedEnrollments->isNotEmpty()) {
            $scores = $gradedEnrollments->pluck('remediation_score')->filter();
            $stats['average_score'] = $scores->isNotEmpty() ? round($scores->avg(), 2) : 0;
            $stats['min_score'] = $scores->isNotEmpty() ? $scores->min() : 0;
            $stats['max_score'] = $scores->isNotEmpty() ? $scores->max() : 0;
            $stats['pass_rate'] = $stats['graded'] > 0
                ? round(($stats['passed'] / $stats['graded']) * 100, 2)
                : 0;
        } else {
            $stats['average_score'] = 0;
            $stats['min_score'] = 0;
            $stats['max_score'] = 0;
            $stats['pass_rate'] = 0;
        }

        return $stats;
    }

    /**
     * Get student's remediation history
     */
    public function getStudentRemediationHistory(User $student): array
    {
        return CourseRemediationEnrollment::where('student_id', $student->id)
            ->with([
                'remediationSession:id,title,type,start_at,end_at,status',
                'remediationSession.course:id,title',
            ])
            ->orderBy('enrolled_at', 'desc')
            ->get()
            ->map(function ($enrollment) {
                return [
                    'id' => $enrollment->id,
                    'session' => $enrollment->remediationSession,
                    'original_grade' => $enrollment->original_grade,
                    'original_score' => $enrollment->original_score,
                    'status' => $enrollment->status,
                    'status_label' => $enrollment->status_label,
                    'remediation_score' => $enrollment->remediation_score,
                    'remediation_grade' => $enrollment->remediation_grade,
                    'attempt_number' => $enrollment->attempt_number,
                    'enrolled_at' => $enrollment->enrolled_at->format('Y-m-d H:i'),
                    'graded_at' => $enrollment->graded_at?->format('Y-m-d H:i'),
                ];
            })
            ->toArray();
    }
}
