<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseExternalScore;
use App\Models\CourseExternalScoreEntry;
use Illuminate\Support\Facades\DB;

class CourseScoreService
{
    /**
     * Recompute the total score of a course based on lessons, assignments, quizzes, and external scores.
     */
    public function syncCourseTotalScore(Course $course): int
    {
        // 1. Calculate internal scores (assignments, quizzes, etc.)
        // For now, we assume internal scores are already managed. 
        // We'll focus on adding external max_scores to the existing total.
        
        // However, a better way is to sum everything from scratch if possible.
        // Let's see what currently contributes to total_score.
        
        $internalTotal = $this->calculateInternalTotalScore($course);
        $externalTotal = CourseExternalScore::where('course_id', $course->id)->sum('max_score');
        
        $newTotal = $internalTotal + $externalTotal;
        
        $course->update(['total_score' => $newTotal]);
        
        return $newTotal;
    }

    /**
     * Calculate internal total score (Quizzes + Assignments)
     */
    protected function calculateInternalTotalScore(Course $course): int
    {
        $quizTotal = $course->courseQuizzes()->sum('total_score');
        
        // Assignments might be polymorphic
        $assignmentTotal = DB::table('assignments')
            ->where('assignmentable_id', $course->id)
            ->where('assignmentable_type', Course::class)
            ->sum('points');
            
        // Also check assignments in lessons
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
     * Sync a member's achieved score (internal points from assignments, quizzes, etc.)
     */
    public function syncMemberAchievedScore(CourseMember $member): int
    {
        $userId = $member->user_id;
        $courseId = $member->course_id;

        // 1. Quizzes
        $quizScore = DB::table('course_quiz_results')
            ->where('course_id', $courseId)
            ->where('user_id', $userId)
            ->sum('score');

        // 2. Assignments (Course level)
        $courseAssignmentIds = DB::table('assignments')
            ->where('assignmentable_id', $courseId)
            ->where('assignmentable_type', Course::class)
            ->pluck('id');
            
        $courseAssignScore = DB::table('assignment_answers')
            ->whereIn('assignment_id', $courseAssignmentIds)
            ->where('user_id', $userId)
            ->where(function($q) {
                $q->where('status', 'graded')->orWhereNotNull('points');
            })
            ->sum('points');

        // 3. Lesson Assignments & Quizzes
        $lessonIds = DB::table('lessons')->where('course_id', $courseId)->pluck('id');
        
        $lessonAssignmentIds = DB::table('assignments')
            ->whereIn('assignmentable_id', $lessonIds)
            ->where('assignmentable_type', 'App\Models\Lesson')
            ->pluck('id');
            
        $lessonAssignScore = DB::table('assignment_answers')
            ->whereIn('assignment_id', $lessonAssignmentIds)
            ->where('user_id', $userId)
            ->where(function($q) {
                $q->where('status', 'graded')->orWhereNotNull('points');
            })
            ->sum('points');
            
        // 4. Lesson Test Scores (Questions)
        $lessonQuestionIds = DB::table('questions')
            ->whereIn('questionable_id', $lessonIds)
            ->where('questionable_type', 'App\Models\Lesson')
            ->pluck('id');
            
        $lessonTestScore = DB::table('lesson_answer_questions')
            ->where('user_id', $userId)
            ->whereIn('question_id', $lessonQuestionIds)
            ->where('is_correct', true)
            ->sum('points');

        $totalAchieved = (int)($quizScore + $courseAssignScore + $lessonAssignScore + $lessonTestScore);
        
        $member->update(['achieved_score' => $totalAchieved]);
        
        $this->updateMemberGradeProgress($member);
        
        return $totalAchieved;
    }

    /**
     * Sync a member's external score points by summing all their entries.
     */
    public function syncMemberExternalScore(CourseMember $member): float
    {
        $externalScorePoints = DB::table('course_external_score_entries')
            ->join('course_external_scores', 'course_external_score_entries.external_score_id', '=', 'course_external_scores.id')
            ->where('course_external_scores.course_id', $member->course_id)
            ->where('course_external_score_entries.course_member_id', $member->id)
            ->sum('course_external_score_entries.score');

        $member->update(['external_score_points' => $externalScorePoints]);
        
        // After updating external scores, we must also update the grade progress
        $this->updateMemberGradeProgress($member);

        return (float)$externalScorePoints;
    }

    /**
     * Update grade progress for a member based on all score components.
     */
    public function updateMemberGradeProgress(CourseMember $member): void
    {
        $course = $member->course;
        if (!$course || $course->total_score <= 0) return;

        $earned = ($member->achieved_score ?? 0) + 
                  ($member->external_score_points ?? 0) + 
                  ($member->bonus_points ?? 0);
        
        $percentage = ($earned / $course->total_score) * 100;
        $percentage = min(100, max(0, $percentage));
        
        $grade = CourseMember::calculateGradeFromPercentage($percentage);
        
        $member->update([
            'grade_progress' => $grade
        ]);
    }

    /**
     * Sync all members in a course.
     */
    public function syncAllCourseMembers(Course $course): void
    {
        $course->courseMembers()->chunk(100, function ($members) {
            foreach ($members as $member) {
                $this->syncMemberAchievedScore($member);
                $this->syncMemberExternalScore($member);
            }
        });
    }

    /**
     * Centralized grade calculation logic.
     */
    public function calculateGradeData(CourseMember $member): array
    {
        $course = $member->course;
        $achieved = (float)($member->achieved_score ?? 0);
        $external = (float)($member->external_score_points ?? 0);
        $bonus = (float)($member->bonus_points ?? 0);
        $totalEarned = $achieved + $external + $bonus;
        $totalMax = (float)($course->total_score ?? 0);
        
        $percentage = $totalMax > 0 ? ($totalEarned / $totalMax) * 100 : 0;
        $percentage = min(100, max(0, $percentage));
        
        $gradeValue = CourseMember::calculateGradeFromPercentage($percentage);
        $gradeName = CourseMember::getGradeNameFromGrade($gradeValue);

        return [
            'achieved_score' => $achieved,
            'external_score_points' => $external,
            'bonus_points' => $bonus,
            'total_earned_score' => $totalEarned,
            'total_score' => $totalMax,
            'score_percentage' => round($percentage, 2),
            'grade_progress' => $gradeValue,
            'grade_name' => $gradeName,
        ];
    }
}
