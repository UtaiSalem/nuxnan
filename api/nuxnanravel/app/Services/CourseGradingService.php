<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseFinalizationLog;
use App\Models\GradeEditLog;
use App\Models\GradeScale;
use App\Models\GradeScaleItem;
use App\Models\User;
use App\Models\CourseGrade;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class CourseGradingService
{
    protected AttendanceEligibilityService $eligibilityService;

    public function __construct(AttendanceEligibilityService $eligibilityService)
    {
        $this->eligibilityService = $eligibilityService;
    }

    /**
     * Calculate grade from score using grade scale
     */
    public function calculateGrade(float $score, ?GradeScale $gradeScale = null): array
    {
        // Get default grade scale if not specified
        if (!$gradeScale) {
            $gradeScale = GradeScale::where('is_default', true)->first();
        }

        if (!$gradeScale) {
            // Fallback to Thai standard grading
            return $this->calculateDefaultGrade($score);
        }

        $item = $gradeScale->items()
            ->where('min_score', '<=', $score)
            ->orderBy('min_score', 'desc')
            ->first();

        if (!$item) {
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
        $members = $course->members()->with('user')->get();
        $results = [];

        foreach ($members as $member) {
            // Get total score from gradebook if available
            $totalScore = $this->calculateMemberTotalScore($member);
            
            $gradeResult = $this->calculateGrade($totalScore, $gradeScale);
            
            $member->update([
                'draft_total_score' => $totalScore,
                'draft_grade' => $gradeResult['grade'],
                'draft_grade_point' => $gradeResult['grade_point'],
            ]);

            $results[] = [
                'member_id' => $member->id,
                'user_id' => $member->user_id,
                'user' => $member->user,
                'total_score' => $totalScore,
                'grade' => $gradeResult['grade'],
                'grade_point' => $gradeResult['grade_point'],
            ];
        }

        return $results;
    }

    /**
     * Calculate total score for a member from gradebook
     * Note: Returns 0-100 percentage.
     */
    protected function calculateMemberTotalScore(CourseMember $member): float
    {
        // For P0 hotfix: directly calculate percentage from member score fields.
        // The legacy gradebook path is removed until P2/P3.
        $course = $member->course;
        if (!$course || $course->total_score <= 0) return 0;
        
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
            // Calculate draft grades
            $results = $this->calculateDraftGrades($course, $gradeScale);

            // CC-GAP-1 Fix: Update status to published
            $course->update([
                'finalization_status' => 'published',
            ]);

            // Update completion status
            $course->members()->update([
                'completion_status' => 'pending_acceptance',
            ]);

            // Calculate statistics
            $stats = $this->calculateGradeStatistics($course);

            // Log the action
            CourseFinalizationLog::create([
                'course_id' => $course->id,
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
        $member->update([
            'final_total_score' => $member->draft_total_score,
            'final_grade' => $member->draft_grade,
            'final_grade_point' => $member->draft_grade_point,
            'grade_accepted_at' => now(),
            'completion_status' => $member->draft_grade === 'F' ? 'failed' : 'completed',
            'completed_at' => $member->draft_grade !== 'F' ? now() : null,
        ]);

        return $member->fresh();
    }

    /**
     * Finalize all grades for a course
     */
    public function finalizeGrades(Course $course, User $performer): Course
    {
        return DB::transaction(function () use ($course, $performer) {
            // Finalize all pending grades
            $course->members()
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
        $members = $course->members()->with('user')->get();
        
        foreach ($members as $member) {
            if ($member->final_grade === null) continue;

            // Find student record linked to user
            $student = Student::where('user_id', $member->user_id)->first();

            CourseGrade::updateOrCreate(
                [
                    'course_id'  => $course->id,
                    'user_id'    => $member->user_id,
                ],
                [
                    'academy_id'   => $course->academy_id,
                    'student_id'   => $student?->id,
                    'semester_id'  => null, // Course model has no semester_id; set via transcript generation
                    'total_score'  => $member->final_total_score,
                    'percentage'   => $member->getPercentageScore() ?? $member->final_total_score,
                    'letter_grade' => $member->final_grade,
                    'grade_points' => $member->final_grade_point,
                    'status'       => CourseGrade::STATUS_COMPLETED,
                    'is_published' => true,
                    'approved_by'  => $performer->id,
                    'approved_at'  => now(),
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
                $member->final_total_score ?? $member->draft_total_score,
                $member->final_grade ?? $member->draft_grade,
                $member->final_grade_point ?? $member->draft_grade_point,
                $newScore,
                $newGrade,
                $gradeResult['grade_point'],
                $reason
            );

            // Update the grade
            $member->update([
                'final_total_score' => $newScore ?? $member->draft_total_score,
                'final_grade' => $newGrade,
                'final_grade_point' => $gradeResult['grade_point'],
                'completion_status' => $newGrade === 'F' ? 'failed' : 'completed',
            ]);

            return $member->fresh();
        });
    }

    /**
     * Calculate grade statistics for a course
     */
    public function calculateGradeStatistics(Course $course): array
    {
        $members = $course->members()->get();
        
        $grades = ['A' => 0, 'B+' => 0, 'B' => 0, 'C+' => 0, 'C' => 0, 'D+' => 0, 'D' => 0, 'F' => 0];
        $scores = [];
        $passed = 0;
        $failed = 0;

        foreach ($members as $member) {
            $grade = $member->final_grade ?? $member->draft_grade;
            $score = $member->final_total_score ?? $member->draft_total_score;

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
            $course->update([
                'finalization_status' => 'grading',
            ]);

            CourseFinalizationLog::create([
                'course_id' => $course->id,
                'performed_by' => $performer->id,
                'action' => CourseFinalizationLog::ACTION_REOPEN,
                'notes' => $reason,
                'total_students' => $course->members()->count(),
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
