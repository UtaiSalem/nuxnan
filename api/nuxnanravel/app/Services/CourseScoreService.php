<?php

namespace App\Services;

use App\DataTransferObjects\ScoreBreakdown;
use App\Models\Course;
use App\Models\CourseExternalScore;
use App\Models\CourseMember;
use Illuminate\Support\Facades\DB;

class CourseScoreService
{
    /**
     * Cache for course-level structural queries to prevent N+1 during bulk operations.
     */
    protected array $courseStructureCache = [];

    /**
     * Get course structural data (cached).
     */
    public function getCourseStructure(int $courseId): array
    {
        if (! isset($this->courseStructureCache[$courseId])) {
            $cache = [];

            // 1. Quizzes
            $courseQuizzes = DB::table('course_quizzes')->where('course_id', $courseId)->get(['id', 'total_score']);
            $cache['quizMax'] = (float) $courseQuizzes->sum('total_score');
            $cache['quizIds'] = $courseQuizzes->pluck('id')->toArray();
            $cache['quizCount'] = count($cache['quizIds']);

            // 2. Course Assignments
            $courseAssignments = DB::table('assignments')
                ->where('assignmentable_id', $courseId)
                ->where('assignmentable_type', Course::class)
                ->get(['id', 'points']);
            $cache['courseAssignmentMax'] = (float) $courseAssignments->sum('points');
            $cache['courseAssignIds'] = $courseAssignments->pluck('id')->toArray();
            $cache['courseAssignCount'] = count($cache['courseAssignIds']);

            // 3. Lessons
            $lessonIds = DB::table('lessons')->where('course_id', $courseId)->pluck('id')->toArray();
            $cache['lessonIds'] = $lessonIds;
            $cache['lessonCount'] = count($lessonIds);

            // 3.1 Lesson Assignments
            $lessonAssignments = DB::table('assignments')
                ->whereIn('assignmentable_id', $lessonIds)
                ->where('assignmentable_type', 'App\Models\Lesson')
                ->get(['id', 'points']);
            $cache['lessonAssignmentMax'] = (float) $lessonAssignments->sum('points');
            $cache['lessonAssignIds'] = $lessonAssignments->pluck('id')->toArray();
            $cache['lessonAssignCount'] = count($cache['lessonAssignIds']);

            // 3.2 Lesson Questions
            $lessonQuestions = DB::table('questions')
                ->whereIn('questionable_id', $lessonIds)
                ->where('questionable_type', 'App\Models\Lesson')
                ->get(['id', 'points']);
            $lessonQuestionMax = 0;
            foreach ($lessonQuestions as $q) {
                $lessonQuestionMax += ($q->points !== null ? $q->points : 1);
            }
            $cache['lessonQuestionMax'] = (float) $lessonQuestionMax;
            $cache['lessonQuestionIds'] = $lessonQuestions->pluck('id')->toArray();
            $cache['lessonQuestionCount'] = count($cache['lessonQuestionIds']);

            // 4. External Scores
            $externalScores = DB::table('course_external_scores')
                ->where('course_id', $courseId)
                ->get(['id', 'max_score']);
            $cache['externalMax'] = (float) $externalScores->sum('max_score');
            $cache['externalScoreIds'] = $externalScores->pluck('id')->toArray();

            $this->courseStructureCache[$courseId] = $cache;
        }

        return $this->courseStructureCache[$courseId];
    }

    /**
     * Compute a detailed breakdown of a member's score across all sources.
     * Note: This does not write to the database.
     */
    public function computeBreakdown(CourseMember $member): ScoreBreakdown
    {
        $breakdown = new ScoreBreakdown;
        $userId = $member->user_id;
        $courseId = $member->course_id;

        $struct = $this->getCourseStructure($courseId);

        // 1. Quizzes
        $breakdown->courseQuizMax = $struct['quizMax'];
        $breakdown->totalQuizzes = $struct['quizCount'];
        $quizIds = $struct['quizIds'];

        $completedQuizIds = [];
        if (! empty($quizIds)) {
            $quizResults = DB::table('course_quiz_results')
                ->where('course_id', $courseId)
                ->where('user_id', $userId)
                ->get(['score', 'quiz_id']);

            $breakdown->courseQuizEarned = (float) $quizResults->sum('score');
            $completedQuizIds = $quizResults->pluck('quiz_id')->toArray();
            $breakdown->quizzesCompleted = count($completedQuizIds);
        }
        $breakdown->missingSources['quiz_ids'] = array_values(array_diff($quizIds, $completedQuizIds));

        // 2. Course Assignments
        $breakdown->courseAssignmentMax = $struct['courseAssignmentMax'];
        $courseAssignIds = $struct['courseAssignIds'];

        $completedCourseAssignIds = [];
        if (! empty($courseAssignIds)) {
            $assignResults = DB::table('assignment_answers')
                ->whereIn('assignment_id', $courseAssignIds)
                ->where('user_id', $userId)
                ->where(function ($q) {
                    $q->where('status', 'graded')->orWhereNotNull('points');
                })
                ->get(['assignment_id', 'points']);

            $breakdown->courseAssignmentEarned = (float) $assignResults->sum('points');
            $completedCourseAssignIds = $assignResults->pluck('assignment_id')->toArray();
        }
        $breakdown->missingSources['course_assignment_ids'] = array_values(array_diff($courseAssignIds, $completedCourseAssignIds));

        // 3. Lesson Sources (Assignments & Questions)
        $breakdown->totalLessons = $struct['lessonCount'];

        // Lesson Progress (Status only)
        $breakdown->lessonsCompleted = DB::table('lesson_progress')
            ->whereIn('lesson_id', $struct['lessonIds'])
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->count();

        // 3.1 Lesson Assignments
        $breakdown->lessonAssignmentMax = $struct['lessonAssignmentMax'];
        $lessonAssignIds = $struct['lessonAssignIds'];

        $completedLessonAssignIds = [];
        if (! empty($lessonAssignIds)) {
            $lessonAssignResults = DB::table('assignment_answers')
                ->whereIn('assignment_id', $lessonAssignIds)
                ->where('user_id', $userId)
                ->where(function ($q) {
                    $q->where('status', 'graded')->orWhereNotNull('points');
                })
                ->get(['assignment_id', 'points']);

            $breakdown->lessonAssignmentEarned = (float) $lessonAssignResults->sum('points');
            $completedLessonAssignIds = $lessonAssignResults->pluck('assignment_id')->toArray();
        }
        $breakdown->missingSources['lesson_assignment_ids'] = array_values(array_diff($lessonAssignIds, $completedLessonAssignIds));

        // Total Assignments Completed
        $breakdown->totalAssignments = $struct['courseAssignCount'] + $struct['lessonAssignCount'];
        $breakdown->assignmentsCompleted = count($completedCourseAssignIds) + count($completedLessonAssignIds);

        // 3.2 Lesson Questions
        $breakdown->lessonQuestionMax = $struct['lessonQuestionMax'];
        $lessonQuestionIds = $struct['lessonQuestionIds'];

        if (! empty($lessonQuestionIds)) {
            $lessonQuestionResults = DB::table('lesson_answer_questions')
                ->join('questions', 'lesson_answer_questions.question_id', '=', 'questions.id')
                ->where('lesson_answer_questions.user_id', $userId)
                ->whereIn('lesson_answer_questions.question_id', $lessonQuestionIds)
                ->where('lesson_answer_questions.is_correct', true)
                ->get([
                    'lesson_answer_questions.question_id',
                    DB::raw('COALESCE(questions.points, 1) as current_points'),
                ])
                // A moved question can have answers under multiple lessons; count it once using current points.
                ->unique('question_id');

            $breakdown->lessonQuestionEarned = (float) $lessonQuestionResults->sum('current_points');
        }

        // 4. External Scores
        $breakdown->externalMax = $struct['externalMax'];
        $externalScoreIds = $struct['externalScoreIds'];

        if (! empty($externalScoreIds)) {
            $breakdown->externalEarned = (float) DB::table('course_external_score_entries')
                ->whereIn('external_score_id', $externalScoreIds)
                ->where('course_member_id', $member->id)
                ->sum('score');
        }

        // 5. Bonus
        $breakdown->bonus = (float) ($member->bonus_points ?? 0);

        return $breakdown;
    }

    /**
     * Compute breakdown for multiple members efficiently.
     *
     * @return array<int, ScoreBreakdown> Map of member ID to ScoreBreakdown
     */
    public function computeBulkBreakdown(Course $course, $members): array
    {
        $userIds = $members->pluck('user_id')->unique()->toArray();
        $memberIds = $members->pluck('id')->toArray();
        $courseId = $course->id;

        $struct = $this->getCourseStructure($courseId);

        // 1. Bulk fetch all relevant data
        $assignmentScoresByUser = DB::table('assignment_answers')
            ->whereIn('user_id', $userIds)
            ->whereIn('assignment_id', array_merge($struct['courseAssignIds'], $struct['lessonAssignIds']))
            ->where(function ($q) {
                $q->where('status', 'graded')->orWhereNotNull('points');
            })
            ->select('user_id', 'assignment_id', 'points')
            ->get()
            ->groupBy('user_id');

        $quizResultsByUser = DB::table('course_quiz_results')
            ->whereIn('user_id', $userIds)
            ->where('course_id', $courseId)
            ->select('user_id', 'quiz_id', 'score')
            ->get()
            ->groupBy('user_id');

        $questionScoresByUser = DB::table('lesson_answer_questions')
            ->join('questions', 'lesson_answer_questions.question_id', '=', 'questions.id')
            ->whereIn('lesson_answer_questions.user_id', $userIds)
            ->whereIn('lesson_answer_questions.question_id', $struct['lessonQuestionIds'])
            ->where('lesson_answer_questions.is_correct', true)
            ->select('lesson_answer_questions.user_id', 'lesson_answer_questions.question_id', DB::raw('COALESCE(questions.points, 1) as current_points'))
            ->get()
            // A moved question can have answers under multiple lessons; dedupe before grouping users.
            ->unique(fn ($answer) => $answer->user_id.':'.$answer->question_id)
            ->groupBy('user_id');

        $lessonProgressCountsByUser = DB::table('lesson_progress')
            ->whereIn('user_id', $userIds)
            ->whereIn('lesson_id', $struct['lessonIds'])
            ->where('status', 'completed')
            ->select('user_id', 'lesson_id')
            ->get()
            ->groupBy('user_id');

        $externalScoresByMember = DB::table('course_external_score_entries')
            ->whereIn('course_member_id', $memberIds)
            ->whereIn('external_score_id', $struct['externalScoreIds'])
            ->select('course_member_id', 'score')
            ->get()
            ->groupBy('course_member_id');

        // 2. Assemble results
        $results = [];
        foreach ($members as $member) {
            $userId = $member->user_id;
            $mId = $member->id;
            $breakdown = new ScoreBreakdown;

            // Common
            $breakdown->bonus = (float) ($member->bonus_points ?? 0);
            $breakdown->totalQuizzes = $struct['quizCount'];
            $breakdown->totalLessons = $struct['lessonCount'];
            $breakdown->totalAssignments = $struct['courseAssignCount'] + $struct['lessonAssignCount'];

            // Quiz
            $breakdown->courseQuizMax = $struct['quizMax'];
            $userQuizzes = $quizResultsByUser[$userId] ?? collect([]);
            $breakdown->courseQuizEarned = (float) $userQuizzes->sum('score');
            $completedQuizIds = $userQuizzes->pluck('quiz_id')->toArray();
            $breakdown->quizzesCompleted = count($completedQuizIds);
            $breakdown->missingSources['quiz_ids'] = array_values(array_diff($struct['quizIds'], $completedQuizIds));

            // Assignments
            $userAssignments = $assignmentScoresByUser[$userId] ?? collect([]);

            // Course Assignments
            $breakdown->courseAssignmentMax = $struct['courseAssignmentMax'];
            $courseAssignResults = $userAssignments->whereIn('assignment_id', $struct['courseAssignIds']);
            $breakdown->courseAssignmentEarned = (float) $courseAssignResults->sum('points');
            $completedCourseAssignIds = $courseAssignResults->pluck('assignment_id')->toArray();
            $breakdown->missingSources['course_assignment_ids'] = array_values(array_diff($struct['courseAssignIds'], $completedCourseAssignIds));

            // Lesson Assignments
            $breakdown->lessonAssignmentMax = $struct['lessonAssignmentMax'];
            $lessonAssignResults = $userAssignments->whereIn('assignment_id', $struct['lessonAssignIds']);
            $breakdown->lessonAssignmentEarned = (float) $lessonAssignResults->sum('points');
            $completedLessonAssignIds = $lessonAssignResults->pluck('assignment_id')->toArray();
            $breakdown->missingSources['lesson_assignment_ids'] = array_values(array_diff($struct['lessonAssignIds'], $completedLessonAssignIds));

            $breakdown->assignmentsCompleted = count($completedCourseAssignIds) + count($completedLessonAssignIds);

            // Questions
            $breakdown->lessonQuestionMax = $struct['lessonQuestionMax'];
            $userQuestions = $questionScoresByUser[$userId] ?? collect([]);
            // Sum current question points so edits do not leave earned points on stale snapshots.
            $breakdown->lessonQuestionEarned = (float) $userQuestions->sum('current_points');

            // Lesson Progress
            $userLessonProgress = $lessonProgressCountsByUser[$userId] ?? collect([]);
            $breakdown->lessonsCompleted = $userLessonProgress->count();

            // External
            $breakdown->externalMax = $struct['externalMax'];
            $userExternal = $externalScoresByMember[$mId] ?? collect([]);
            $breakdown->externalEarned = (float) $userExternal->sum('score');

            $results[$mId] = $breakdown;
        }

        return $results;
    }

    /**
     * Recompute all scores for a member, save them to the database, and update their grade.
     */
    public function recompute(CourseMember $member): ScoreBreakdown
    {
        return DB::transaction(function () use ($member) {
            // Lock the member row
            $lockedMember = CourseMember::where('id', $member->id)->lockForUpdate()->first();
            if (! $lockedMember) {
                return new ScoreBreakdown; // Should never happen
            }

            $course = $lockedMember->course;

            if ($course && $course->use_legacy_gradebook) {
                $breakdown = $this->computeLegacyBreakdown($lockedMember);
            } else {
                $breakdown = $this->computeBreakdown($lockedMember);
            }

            $lockedMember->achieved_score = $breakdown->internalEarned();
            $lockedMember->external_score_points = $breakdown->externalEarned;

            // Calculate grade
            $lockedMember->grade_progress = CourseMember::calculateGradeFromPercentage($breakdown->percentage());
            $lockedMember->last_score_synced_at = now();

            $lockedMember->save();

            return $breakdown;
        });
    }

    /**
     * Compute breakdown using the old gradebook_scores table (Legacy Mode).
     */
    protected function computeLegacyBreakdown(CourseMember $member): ScoreBreakdown
    {
        $breakdown = new ScoreBreakdown;
        $course = $member->course;

        $scores = DB::table('gradebook_scores')
            ->join('gradebook_assessments', 'gradebook_scores.assessment_id', '=', 'gradebook_assessments.id')
            ->where('gradebook_scores.user_id', $member->user_id)
            ->where('gradebook_assessments.course_id', $member->course_id)
            ->select(
                'gradebook_scores.score',
                'gradebook_assessments.max_score',
                'gradebook_assessments.weight'
            )
            ->get();

        $totalWeightedScore = 0;
        $totalWeight = 0;

        foreach ($scores as $score) {
            if ($score->max_score > 0) {
                $percentage = ($score->score / $score->max_score) * 100;
                $totalWeightedScore += $percentage * ($score->weight / 100);
                $totalWeight += $score->weight;
            }
        }

        if ($totalWeight > 0 && $totalWeight != 100) {
            $totalWeightedScore = ($totalWeightedScore / $totalWeight) * 100;
        }

        // Hacky way to inject percentage directly without altering ScoreBreakdown much:
        // We set externalMax to 100 and externalEarned to $totalWeightedScore,
        // so percentage() returns $totalWeightedScore.
        $breakdown->externalMax = 100;
        $breakdown->externalEarned = round($totalWeightedScore, 2);

        return $breakdown;
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

        return (int) ($quizTotal + $assignmentTotal + $lessonAssignmentTotal + $lessonQuestionTotal);
    }

    /**
     * Backwards compatibility: syncCourseTotalScore
     */
    public function syncCourseTotalScore(Course $course): int
    {
        $internalTotal = $this->calculateInternalTotalScore($course);
        $externalTotal = CourseExternalScore::where('course_id', $course->id)->sum('max_score');

        $newTotal = max(0, $internalTotal + $externalTotal);
        $course->update(['total_score' => $newTotal]);

        return $newTotal;
    }

    // --- Backwards Compatibility Wrappers for Controllers (P1.6 pending) ---

    public function syncMemberAchievedScore(CourseMember $member): int
    {
        $breakdown = $this->recompute($member);

        return (int) $breakdown->internalEarned();
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
