<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseExternalScore;
use App\Models\CourseExternalScoreEntry;
use App\DataTransferObjects\ScoreBreakdown;
use Illuminate\Support\Facades\DB;

class CourseScoreService
{
    /**
     * Compute a detailed breakdown of a member's score across all sources.
     * Note: This does not write to the database.
     */
    public function computeBreakdown(CourseMember $member): ScoreBreakdown
    {
        $breakdown = new ScoreBreakdown();
        $userId = $member->user_id;
        $courseId = $member->course_id;

        // 1. Quizzes (Course level)
        $courseQuizzes = DB::table('course_quizzes')->where('course_id', $courseId)->get(['id', 'total_score']);
        $breakdown->courseQuizMax = $courseQuizzes->sum('total_score');
        $quizIds = $courseQuizzes->pluck('id')->toArray();
        
        $completedQuizIds = [];
        if (!empty($quizIds)) {
            $quizResults = DB::table('course_quiz_results')
                ->where('course_id', $courseId)
                ->where('user_id', $userId)
                ->get(['score', 'quiz_id']);
                
            $breakdown->courseQuizEarned = $quizResults->sum('score');
            $completedQuizIds = $quizResults->pluck('quiz_id')->toArray();
        }
        $breakdown->missingSources['quiz_ids'] = array_values(array_diff($quizIds, $completedQuizIds));

        // 2. Course Assignments
        $courseAssignments = DB::table('assignments')
            ->where('assignmentable_id', $courseId)
            ->where('assignmentable_type', Course::class)
            ->get(['id', 'points']);
        $breakdown->courseAssignmentMax = $courseAssignments->sum('points');
        $courseAssignIds = $courseAssignments->pluck('id')->toArray();
        
        $completedCourseAssignIds = [];
        if (!empty($courseAssignIds)) {
            $assignResults = DB::table('assignment_answers')
                ->whereIn('assignment_id', $courseAssignIds)
                ->where('user_id', $userId)
                ->where(function($q) {
                    $q->where('status', 'graded')->orWhereNotNull('points');
                })
                ->get(['assignment_id', 'points']);
                
            $breakdown->courseAssignmentEarned = $assignResults->sum('points');
            $completedCourseAssignIds = $assignResults->pluck('assignment_id')->toArray();
        }
        $breakdown->missingSources['course_assignment_ids'] = array_values(array_diff($courseAssignIds, $completedCourseAssignIds));

        // 3. Lesson Sources (Assignments & Questions)
        $lessonIds = DB::table('lessons')->where('course_id', $courseId)->pluck('id')->toArray();
        
        // 3.1 Lesson Assignments
        $lessonAssignments = DB::table('assignments')
            ->whereIn('assignmentable_id', $lessonIds)
            ->where('assignmentable_type', 'App\Models\Lesson')
            ->get(['id', 'points']);
        $breakdown->lessonAssignmentMax = $lessonAssignments->sum('points');
        $lessonAssignIds = $lessonAssignments->pluck('id')->toArray();
        
        $completedLessonAssignIds = [];
        if (!empty($lessonAssignIds)) {
            $lessonAssignResults = DB::table('assignment_answers')
                ->whereIn('assignment_id', $lessonAssignIds)
                ->where('user_id', $userId)
                ->where(function($q) {
                    $q->where('status', 'graded')->orWhereNotNull('points');
                })
                ->get(['assignment_id', 'points']);
                
            $breakdown->lessonAssignmentEarned = $lessonAssignResults->sum('points');
            $completedLessonAssignIds = $lessonAssignResults->pluck('assignment_id')->toArray();
        }
        $breakdown->missingSources['lesson_assignment_ids'] = array_values(array_diff($lessonAssignIds, $completedLessonAssignIds));
        
        // 3.2 Lesson Questions
        $lessonQuestions = DB::table('questions')
            ->whereIn('questionable_id', $lessonIds)
            ->where('questionable_type', 'App\Models\Lesson')
            ->get(['id', 'points']);
            
        // Handle COALESCE(points, 1) in PHP since we fetched them
        $lessonQuestionMax = 0;
        foreach ($lessonQuestions as $q) {
            $lessonQuestionMax += ($q->points !== null ? $q->points : 1);
        }
        $breakdown->lessonQuestionMax = $lessonQuestionMax;
        $lessonQuestionIds = $lessonQuestions->pluck('id')->toArray();

        $completedLessonQuestionIds = [];
        if (!empty($lessonQuestionIds)) {
            $lessonQuestionResults = DB::table('lesson_answer_questions')
                ->where('user_id', $userId)
                ->whereIn('question_id', $lessonQuestionIds)
                ->where('is_correct', true)
                ->get(['question_id', 'points']);
                
            $breakdown->lessonQuestionEarned = $lessonQuestionResults->sum('points');
            // A question is answered regardless of correctness, but we don't have that table available easily here.
            // For simplicity, we just mark any missing correct ones. Alternatively, we don't track missing questions yet.
        }

        // 4. External Scores
        $externalScores = DB::table('course_external_scores')
            ->where('course_id', $courseId)
            ->get(['id', 'max_score']);
        $breakdown->externalMax = $externalScores->sum('max_score');
        $externalScoreIds = $externalScores->pluck('id')->toArray();
        
        if (!empty($externalScoreIds)) {
            $breakdown->externalEarned = DB::table('course_external_score_entries')
                ->whereIn('external_score_id', $externalScoreIds)
                ->where('course_member_id', $member->id)
                ->sum('score');
        }

        // 5. Bonus
        $breakdown->bonus = (float)($member->bonus_points ?? 0);
        
        // Bonus penalty clamp -100% is handled in percentage() but we don't cap bonus points directly.
        
        return $breakdown;
    }

    /**
     * Recompute all scores for a member, save them to the database, and update their grade.
     */
    public function recompute(CourseMember $member): ScoreBreakdown
    {
        return DB::transaction(function () use ($member) {
            // Lock the member row
            $lockedMember = CourseMember::where('id', $member->id)->lockForUpdate()->first();
            if (!$lockedMember) {
                return new ScoreBreakdown(); // Should never happen
            }

            $breakdown = $this->computeBreakdown($lockedMember);
            
            $lockedMember->achieved_score = $breakdown->internalEarned();
            $lockedMember->external_score_points = $breakdown->externalEarned;
            
            // Calculate grade
            $lockedMember->grade_progress = CourseMember::calculateGradeFromPercentage($breakdown->percentage());
            $lockedMember->last_score_synced_at = now();
            
            $lockedMember->save();
            
            // Note: Since achieved_score is synced, we might also want to update the course total_score just in case.
            // But usually course total_score is updated when assignments/quizzes are created.
            
            return $breakdown;
        });
    }

    /**
     * Backwards compatibility for P0 Hotfix / Sync All
     */
    public function syncAllCourseMembers(Course $course): void
    {
        $course->courseMembers()->chunk(100, function ($members) {
            foreach ($members as $member) {
                $this->recompute($member);
            }
        });
    }

    /**
     * Backwards compatibility: Calculate internal total score (Quizzes + Assignments + Questions)
     */
    public function calculateInternalTotalScore(Course $course): int
    {
        $quizTotal = $course->courseQuizzes()->sum('total_score');
        
        $assignmentTotal = DB::table('assignments')
            ->where('assignmentable_id', $course->id)
            ->where('assignmentable_type', Course::class)
            ->sum('points');
            
        $lessonIds = $course->courseLessons()->pluck('id');
        $lessonAssignmentTotal = DB::table('assignments')
            ->whereIn('assignmentable_id', $lessonIds)
            ->where('assignmentable_type', 'App\Models\Lesson')
            ->sum('points');

        $lessonQuestionTotal = DB::table('questions')
            ->whereIn('questionable_id', $lessonIds)
            ->where('questionable_type', 'App\Models\Lesson')
            ->sum(DB::raw('COALESCE(points, 1)'));

        return (int)($quizTotal + $assignmentTotal + $lessonAssignmentTotal + $lessonQuestionTotal);
    }
    
    /**
     * Backwards compatibility: syncCourseTotalScore
     */
    public function syncCourseTotalScore(Course $course): int
    {
        $internalTotal = $this->calculateInternalTotalScore($course);
        $externalTotal = CourseExternalScore::where('course_id', $course->id)->sum('max_score');
        
        $newTotal = $internalTotal + $externalTotal;
        $course->update(['total_score' => $newTotal]);
        
        return $newTotal;
    }

    // --- Backwards Compatibility Wrappers for Controllers (P1.6 pending) ---

    public function syncMemberAchievedScore(CourseMember $member): int
    {
        $breakdown = $this->recompute($member);
        return (int)$breakdown->internalEarned();
    }

    public function syncMemberExternalScore(CourseMember $member): float
    {
        $breakdown = $this->recompute($member);
        return $breakdown->externalEarned;
    }

    public function updateMemberGradeProgress(CourseMember $member): void
    {
        $this->recompute($member);
    }

    public function calculateGradeData(CourseMember $member): array
    {
        $breakdown = $this->computeBreakdown($member);
        $percentage = $breakdown->percentage();
        $gradeValue = CourseMember::calculateGradeFromPercentage($percentage);
        $gradeName = CourseMember::getGradeNameFromGrade($gradeValue);

        return [
            'achieved_score' => $breakdown->internalEarned(),
            'external_score_points' => $breakdown->externalEarned,
            'bonus_points' => $breakdown->bonus,
            'total_earned_score' => $breakdown->totalEarned(),
            'total_score' => $breakdown->totalMax(),
            'score_percentage' => round($percentage, 2),
            'grade_progress' => $gradeValue,
            'grade_name' => $gradeName,
        ];
    }
}
