<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseFinalizationLog;
use App\Models\CourseGrade;
use App\Models\CourseMember;
use App\Models\CourseMemberGradeSnapshot;
use App\Models\GradeEditLog;
use App\Models\GradeScale;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseGradingService
{
    protected AttendanceEligibilityService $eligibilityService;

    protected CourseScoreService $scoreService;

    public function __construct(
        AttendanceEligibilityService $eligibilityService,
        CourseScoreService $scoreService
    ) {
        $this->eligibilityService = $eligibilityService;
        $this->scoreService = $scoreService;
    }

    /**
     * Calculate grade from score using grade scale
     */
    public function calculateGrade(float $score, ?GradeScale $gradeScale = null): array
    {
        // Get default grade scale if not specified
        if (! $gradeScale) {
            $gradeScale = GradeScale::where('is_default', true)->first();
        }

        if (! $gradeScale) {
            // Fallback to Thai standard grading
            return $this->calculateDefaultGrade($score);
        }

        $item = $gradeScale->items()
            ->where('min_score', '<=', $score)
            ->orderBy('min_score', 'desc')
            ->first();

        if (! $item) {
            return [
                'grade' => 'F',
                'grade_point' => 0,
                'description' => 'ไม่ผ่าน',
            ];
        }

        return [
            'grade' => $item->grade,
            'grade_point' => $item->grade_point,
            'description' => $item->description,
        ];
    }

    /**
     * Default Thai grading scale
     */
    protected function calculateDefaultGrade(float $score): array
    {
        $grades = [
            ['min' => 80, 'grade' => 'A', 'point' => 4.00, 'desc' => 'ดีเยี่ยม'],
            ['min' => 75, 'grade' => 'B+', 'point' => 3.50, 'desc' => 'ดีมาก'],
            ['min' => 70, 'grade' => 'B', 'point' => 3.00, 'desc' => 'ดี'],
            ['min' => 65, 'grade' => 'C+', 'point' => 2.50, 'desc' => 'ค่อนข้างดี'],
            ['min' => 60, 'grade' => 'C', 'point' => 2.00, 'desc' => 'ปานกลาง'],
            ['min' => 55, 'grade' => 'D+', 'point' => 1.50, 'desc' => 'พอใช้'],
            ['min' => 50, 'grade' => 'D', 'point' => 1.00, 'desc' => 'ผ่าน'],
            ['min' => 0, 'grade' => 'F', 'point' => 0.00, 'desc' => 'ไม่ผ่าน'],
        ];

        foreach ($grades as $g) {
            if ($score >= $g['min']) {
                return [
                    'grade' => $g['grade'],
                    'grade_point' => $g['point'],
                    'description' => $g['desc'],
                ];
            }
        }

        return ['grade' => 'F', 'grade_point' => 0, 'description' => 'ไม่ผ่าน'];
    }

    /**
     * Calculate draft grades for all students in a course
     */
    public function calculateDraftGrades(Course $course, ?GradeScale $gradeScale = null): array
    {
        // Learner roles only: 1=student, 2=student_leader. Exclude teachers (3) and admins (4).
        $members = $course->courseMembers()
            ->with(['user', 'group'])
            ->whereIn('role', [1, 2])
            ->get();
        $results = [];

        foreach ($members as $member) {
            $breakdown = $this->scoreService->recompute($member);
            $percentage = $breakdown->percentage();

            $gradeResult = $this->calculateGrade($percentage, $gradeScale);

            $member->update([
                'draft_earned_score' => $breakdown->totalEarned(),
                'draft_max_score' => $breakdown->totalMax(),
                'draft_percentage' => $percentage,
                'draft_grade' => $gradeResult['grade'],
                'draft_grade_point' => $gradeResult['grade_point'],
            ]);

            $results[] = [
                'member_id' => $member->id,
                'user_id' => $member->user_id,
                'user' => $member->user,
                'member_code' => $member->member_code,
                'group_id' => $member->group_id,
                'group' => $member->group ? ['id' => $member->group->id, 'name' => $member->group->name] : null,
                'total_score' => $percentage,
                'grade' => $gradeResult['grade'],
                'grade_point' => $gradeResult['grade_point'],
                'is_accepted' => $member->completion_status === 'accepted',
            ];
        }

        return $results;
    }

    /**
     * Calculate total score for a member from gradebook
     * Note: Returns 0-100 percentage.
     *
     * @deprecated Use CourseScoreService::recompute instead.
     */
    protected function calculateMemberTotalScore(CourseMember $member): float
    {
        // For P0 hotfix: directly calculate percentage from member score fields.
        // The legacy gradebook path is removed until P2/P3.
        $course = $member->course;
        if (! $course || $course->total_score <= 0) {
            return 0;
        }

        $earned = ($member->achieved_score ?? 0) +
                  ($member->external_score_points ?? 0) +
                  ($member->bonus_points ?? 0);

        $percentage = ($earned / $course->total_score) * 100;

        return (float) max(0, min(100, $percentage));
    }

    /**
     * Start grading period for a course
     */
    public function startGradingPeriod(Course $course, User $performer): Course
    {
        return DB::transaction(function () use ($course, $performer) {
            $course->update([
                'finalization_status' => 'grading',
            ]);

            // Update eligibility for all members
            $eligibilityResults = $this->eligibilityService->updateCourseEligibility($course);

            // Log the action
            CourseFinalizationLog::create([
                'course_id' => $course->id,
                'performed_by' => $performer->id,
                'action' => CourseFinalizationLog::ACTION_START_GRADING,
                'snapshot' => [
                    'eligibility_results' => $eligibilityResults,
                ],
                'total_students' => $eligibilityResults['total'],
                'notes' => 'เริ่มช่วงให้เกรด',
            ]);

            return $course->fresh();
        });
    }

    /**
     * Publish draft grades to students
     */
    public function publishDraftGrades(Course $course, User $performer, ?GradeScale $gradeScale = null): array
    {
        return DB::transaction(function () use ($course, $performer, $gradeScale) {
            $lockedCourse = Course::lockForUpdate()->findOrFail($course->id);
            $runId = (string) Str::uuid();

            // Learner roles only: 1=student, 2=student_leader.
            $members = $lockedCourse->courseMembers()->with('user')->whereIn('role', [1, 2])->get();
            $results = [];

            foreach ($members as $member) {
                // recompute, calculate grade, update draft_* fields
                $breakdown = $this->scoreService->recompute($member);
                $percentage = $breakdown->percentage();
                $gradeResult = $this->calculateGrade($percentage, $gradeScale);

                $member->update([
                    'draft_earned_score' => $breakdown->totalEarned(),
                    'draft_max_score' => $breakdown->totalMax(),
                    'draft_percentage' => $percentage,
                    'draft_grade' => $gradeResult['grade'],
                    'draft_grade_point' => $gradeResult['grade_point'],
                ]);

                // Update old snapshots
                CourseMemberGradeSnapshot::where('course_member_id', $member->id)
                    ->update(['is_current' => false]);

                // Insert new row into CourseMemberGradeSnapshot
                CourseMemberGradeSnapshot::create([
                    'course_id' => $member->course_id,
                    'user_id' => $member->user_id,
                    'course_member_id' => $member->id,
                    'published_run_id' => $runId,
                    'is_current' => true,
                    'published_by' => $performer->id,
                    'earned_score' => $breakdown->totalEarned(),
                    'max_score' => $breakdown->totalMax(),
                    'percentage' => $percentage,
                    'letter_grade' => $gradeResult['grade'],
                    'grade_point' => $gradeResult['grade_point'],
                ]);

                $results[] = [
                    'member_id' => $member->id,
                    'user_id' => $member->user_id,
                    'user' => $member->user,
                    'total_score' => $percentage,
                    'grade' => $gradeResult['grade'],
                    'grade_point' => $gradeResult['grade_point'],
                ];
            }

            // CC-GAP-1 Fix: Update status to published
            $lockedCourse->update([
                'finalization_status' => 'published',
            ]);

            // Update completion status (learners only)
            $lockedCourse->courseMembers()->whereIn('role', [1, 2])->update([
                'completion_status' => 'pending_acceptance',
            ]);

            // Calculate statistics
            $stats = $this->calculateGradeStatistics($lockedCourse);

            // Log the action
            CourseFinalizationLog::create([
                'course_id' => $lockedCourse->id,
                'performed_by' => $performer->id,
                'action' => CourseFinalizationLog::ACTION_PUBLISH_DRAFT,
                'snapshot' => [
                    'grade_distribution' => $stats['distribution'],
                ],
                'total_students' => $stats['total'],
                'passed_count' => $stats['passed'],
                'failed_count' => $stats['failed'],
                'average_score' => $stats['average_score'],
                'notes' => 'ประกาศเกรดเบื้องต้น',
            ]);

            return [
                'results' => $results,
                'statistics' => $stats,
            ];
        });
    }

    /**
     * Student accepts their grade
     */
    public function acceptGrade(CourseMember $member): CourseMember
    {
        $snapshot = CourseMemberGradeSnapshot::where('course_member_id', $member->id)
            ->where('is_current', true)
            ->first();

        if ($snapshot) {
            $member->update([
                'final_earned_score' => $snapshot->earned_score,
                'final_max_score' => $snapshot->max_score,
                'final_percentage' => $snapshot->percentage,
                'final_grade' => $snapshot->letter_grade,
                'final_grade_point' => $snapshot->grade_point,
                'grade_accepted_at' => now(),
                'completion_status' => $snapshot->letter_grade === 'F' ? 'failed' : 'completed',
                'completed_at' => $snapshot->letter_grade !== 'F' ? now() : null,
            ]);
        } else {
            // Legacy fallback
            $member->update([
                'final_earned_score' => $member->draft_earned_score,
                'final_max_score' => $member->draft_max_score ?? 100,
                'final_percentage' => $member->draft_percentage,
                'final_grade' => $member->draft_grade,
                'final_grade_point' => $member->draft_grade_point,
                'grade_accepted_at' => now(),
                'completion_status' => $member->draft_grade === 'F' ? 'failed' : 'completed',
                'completed_at' => $member->draft_grade !== 'F' ? now() : null,
            ]);
        }

        return $member->fresh();
    }

    /**
     * Finalize all grades for a course
     */
    public function finalizeGrades(Course $course, User $performer): Course
    {
        return DB::transaction(function () use ($course, $performer) {
            if (! in_array($course->finalization_status, ['grading', 'published'])) {
                return $course;
            }

            if ($course->finalization_status === 'grading') {
                $this->publishDraftGrades($course, $performer);
                $course->refresh();
            }

            // Finalize all pending grades (auto-accept) — learners only
            $course->courseMembers()
                ->whereIn('role', [1, 2])
                ->where('completion_status', 'pending_acceptance')
                ->each(function ($member) {
                    $this->acceptGrade($member);
                });

            $course->update([
                'finalization_status' => 'finalized',
                'finalized_at' => now(),
                'finalized_by' => $performer->id,
            ]);

            // CC-GAP-4 Fix: Bridge to Transcript system (CourseGrade)
            $this->syncToTranscripts($course, $performer);

            $stats = $this->calculateGradeStatistics($course);

            CourseFinalizationLog::create([
                'course_id' => $course->id,
                'performed_by' => $performer->id,
                'action' => CourseFinalizationLog::ACTION_FINALIZE,
                'snapshot' => [
                    'grade_distribution' => $stats['distribution'],
                ],
                'total_students' => $stats['total'],
                'passed_count' => $stats['passed'],
                'failed_count' => $stats['failed'],
                'average_score' => $stats['average_score'],
                'notes' => 'ยืนยันเกรดสุดท้าย',
            ]);

            return $course->fresh();
        });
    }

    /**
     * Sync course grades to transcript system (CourseGrade)
     */
    protected function syncToTranscripts(Course $course, User $performer): void
    {
        // Learner roles only: 1=student, 2=student_leader.
        $members = $course->courseMembers()->with('user')->whereIn('role', [1, 2])->get();

        foreach ($members as $member) {
            if ($member->final_grade === null) {
                continue;
            }

            // Find student record linked to user — scoped to course's academy when present
            $studentQuery = Student::where('user_id', $member->user_id);
            if ($course->academy_id) {
                $studentQuery->where('academy_id', $course->academy_id);
            }
            $student = $studentQuery->first();

            // Transcript only applies to members enrolled as a Student in the course's academy.
            // Skip non-academy members (e.g. public learners) — they still have grades on CourseMember.
            if (! $student) {
                continue;
            }

            CourseGrade::updateOrCreate(
                [
                    'course_id' => $course->id,
                    'user_id' => $member->user_id,
                ],
                [
                    'academy_id' => $course->academy_id,
                    'student_id' => $student->id,
                    'semester_id' => null, // Course model has no semester_id; set via transcript generation
                    'total_score' => $member->final_earned_score ?? $member->final_percentage,
                    'percentage' => $member->final_percentage ?? $member->getPercentageScore(),
                    'letter_grade' => $member->final_grade,
                    'grade_points' => $member->final_grade_point,
                    'status' => CourseGrade::STATUS_COMPLETED,
                    'is_published' => true,
                    'approved_by' => $performer->id,
                    'approved_at' => now(),
                ]
            );
        }
    }

    /**
     * Override a student's grade (with audit trail)
     */
    public function overrideGrade(
        CourseMember $member,
        User $editor,
        string $newGrade,
        ?float $newScore = null,
        string $reason = '',
        string $changeType = GradeEditLog::TYPE_ADMIN_ADJUSTMENT
    ): CourseMember {
        return DB::transaction(function () use ($member, $editor, $newGrade, $newScore, $reason, $changeType) {
            // Get grade point for new grade
            $gradeResult = $this->calculateGrade($newScore ?? 0);

            // Find the correct grade point
            $gradeScale = GradeScale::where('is_default', true)->first();
            if ($gradeScale) {
                $item = $gradeScale->items()->where('grade', $newGrade)->first();
                if ($item) {
                    $gradeResult['grade_point'] = $item->grade_point;
                }
            }

            // Log the change
            GradeEditLog::log(
                $member,
                $editor,
                $changeType,
                $member->final_percentage ?? $member->draft_percentage,
                $member->final_grade ?? $member->draft_grade,
                $member->final_grade_point ?? $member->draft_grade_point,
                $newScore,
                $newGrade,
                $gradeResult['grade_point'],
                $reason
            );

            // Update the grade
            $member->update([
                'final_earned_score' => $newScore ?? $member->draft_earned_score,
                'final_percentage' => $newScore ?? $member->draft_percentage,
                'final_grade' => $newGrade,
                'final_grade_point' => $gradeResult['grade_point'],
                'completion_status' => $newGrade === 'F' ? 'failed' : 'completed',
            ]);

            // If the course is finalized, insert a new snapshot to track this override before syncing to the transcript
            if ($member->course && $member->course->finalization_status === 'finalized') {
                $runId = (string) Str::uuid();

                CourseMemberGradeSnapshot::where('course_member_id', $member->id)
                    ->update(['is_current' => false]);

                CourseMemberGradeSnapshot::create([
                    'course_id' => $member->course_id,
                    'user_id' => $member->user_id,
                    'course_member_id' => $member->id,
                    'published_run_id' => $runId,
                    'is_current' => true,
                    'published_by' => $editor->id,
                    'earned_score' => $newScore ?? $member->final_earned_score ?? 0,
                    'max_score' => $member->final_max_score ?? 100,
                    'percentage' => $newScore ?? $member->final_percentage ?? 0,
                    'letter_grade' => $newGrade,
                    'grade_point' => $gradeResult['grade_point'],
                ]);
            }

            return $member->fresh();
        });
    }

    /**
     * Calculate grade statistics for a course
     */
    public function calculateGradeStatistics(Course $course): array
    {
        // Learner roles only: 1=student, 2=student_leader. Exclude teachers (3) and admins (4).
        $members = $course->courseMembers()->whereIn('role', [1, 2])->get();

        $grades = ['A' => 0, 'B+' => 0, 'B' => 0, 'C+' => 0, 'C' => 0, 'D+' => 0, 'D' => 0, 'F' => 0];
        $scores = [];
        $passed = 0;
        $failed = 0;

        foreach ($members as $member) {
            $grade = $member->final_grade ?? $member->draft_grade;
            $score = $member->final_percentage ?? $member->draft_percentage;

            if ($grade) {
                if (isset($grades[$grade])) {
                    $grades[$grade]++;
                }

                if ($grade === 'F') {
                    $failed++;
                } else {
                    $passed++;
                }
            }

            if ($score !== null) {
                $scores[] = $score;
            }
        }

        $averageScore = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
        $minScore = count($scores) > 0 ? min($scores) : 0;
        $maxScore = count($scores) > 0 ? max($scores) : 0;

        // Calculate GPA
        $totalGradePoints = 0;
        $gradePointCounts = 0;
        foreach ($members as $member) {
            $gp = $member->final_grade_point ?? $member->draft_grade_point;
            if ($gp !== null) {
                $totalGradePoints += $gp;
                $gradePointCounts++;
            }
        }
        $averageGPA = $gradePointCounts > 0 ? $totalGradePoints / $gradePointCounts : 0;

        return [
            'total' => $members->count(),
            'passed' => $passed,
            'failed' => $failed,
            'pass_rate' => $members->count() > 0 ? round(($passed / $members->count()) * 100, 2) : 0,
            'average_score' => round($averageScore, 2),
            'min_score' => round($minScore, 2),
            'max_score' => round($maxScore, 2),
            'average_gpa' => round($averageGPA, 2),
            'distribution' => $grades,
        ];
    }

    /**
     * Get course grading summary
     */
    public function getGradingSummary(Course $course): array
    {
        $stats = $this->calculateGradeStatistics($course);
        $eligibility = $this->eligibilityService->getCourseEligibilitySummary($course);

        $pendingAppeals = $course->gradeAppeals()
            ->whereIn('status', ['pending', 'reviewing'])
            ->count();

        $activeRemediation = $course->remediationSessions()
            ->whereIn('status', ['open', 'in_progress'])
            ->count();

        return [
            'course_id' => $course->id,
            'finalization_status' => $course->finalization_status,
            'statistics' => $stats,
            'eligibility' => [
                'total' => $eligibility['total'],
                'eligible' => $eligibility['eligible'],
                'at_risk' => $eligibility['at_risk'],
                'ineligible' => $eligibility['ineligible'],
                'unlocked' => $eligibility['unlocked'],
            ],
            'pending_appeals' => $pendingAppeals,
            'active_remediation_sessions' => $activeRemediation,
            'can_finalize' => in_array($course->finalization_status, ['grading', 'published']) && $pendingAppeals === 0,
        ];
    }

    /**
     * Reopen grading for corrections
     */
    public function reopenGrading(Course $course, User $performer, string $reason): Course
    {
        return DB::transaction(function () use ($course, $performer, $reason) {
            // Mark all CourseMemberGradeSnapshot for the course as is_current=false
            $memberIds = $course->courseMembers()->pluck('id');
            CourseMemberGradeSnapshot::whereIn('course_member_id', $memberIds)
                ->update(['is_current' => false]);

            // Update transcripts (CourseGrade): set is_published=false for this course
            CourseGrade::where('course_id', $course->id)
                ->update(['is_published' => false]);

            $course->update([
                'finalization_status' => 'grading',
            ]);

            CourseFinalizationLog::create([
                'course_id' => $course->id,
                'performed_by' => $performer->id,
                'action' => CourseFinalizationLog::ACTION_REOPEN,
                'notes' => $reason,
                'total_students' => $course->courseMembers()->count(),
            ]);

            return $course->fresh();
        });
    }

    /**
     * Archive the course
     */
    public function archiveCourse(Course $course, User $performer): Course
    {
        return DB::transaction(function () use ($course, $performer) {
            $stats = $this->calculateGradeStatistics($course);

            $course->update([
                'finalization_status' => 'archived',
            ]);

            CourseFinalizationLog::create([
                'course_id' => $course->id,
                'performed_by' => $performer->id,
                'action' => CourseFinalizationLog::ACTION_ARCHIVE,
                'snapshot' => [
                    'final_statistics' => $stats,
                ],
                'total_students' => $stats['total'],
                'passed_count' => $stats['passed'],
                'failed_count' => $stats['failed'],
                'average_score' => $stats['average_score'],
                'notes' => 'เก็บวิชาเข้า Archive',
            ]);

            return $course->fresh();
        });
    }
}
