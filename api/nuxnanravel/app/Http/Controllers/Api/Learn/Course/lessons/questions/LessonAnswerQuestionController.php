<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons\questions;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\Lesson;
use App\Models\LessonAnswerQuestion;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Services\ContentVisibilityService;
use App\Services\CourseScoreService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class LessonAnswerQuestionController extends Controller
{
    protected ContentVisibilityService $visibility;

    public function __construct(ContentVisibilityService $visibility)
    {
        $this->visibility = $visibility;
    }

    public function store(Request $request, Lesson $lesson, Question $question)
    {
        $user = auth()->user();

        // Route-model binding resolves {lesson} and {question} independently, so
        // a mismatched pair would otherwise persist an answer row that is counted
        // by CourseScoreService but belongs to no real lesson question.
        if ($question->questionable_type !== $lesson->getMorphClass()
            || (int) $question->questionable_id !== (int) $lesson->id) {
            return response()->json([
                'success' => false,
                'code' => 'QUESTION_NOT_IN_LESSON',
                'message' => 'ไม่พบคำถามนี้ในบทเรียน',
            ], 404);
        }

        // Guard for students
        if (! $lesson->course->isAdmin($user)) {
            $this->visibility->assertVisibleOrFail($lesson, $user, 403);
        }

        // The option must belong to THIS question, otherwise a learner could send
        // a correct option id borrowed from another question and score full marks.
        $validated = $request->validate([
            'answer_id' => [
                'required',
                Rule::exists('question_options', 'id')
                    ->where('optionable_id', $question->id)
                    ->where('optionable_type', $question->getMorphClass()),
            ],
        ], [
            'answer_id.exists' => 'ตัวเลือกคำตอบนี้ไม่ใช่ตัวเลือกของคำถามข้อนี้',
        ]);

        // Lifecycle guard: block lesson question answers after the course ends.
        $course = Course::find($lesson->course_id);
        if ($course) {
            $gate = Gate::inspect('submitLessonQuestion', $course);
            if ($gate->denied()) {
                return response()->json([
                    'success' => false,
                    'code' => $gate->code() ?: 'WORK_TYPE_LOCKED_AFTER_END',
                    'message' => $gate->message() ?: 'รายวิชาสิ้นสุดแล้ว ไม่สามารถตอบคำถามได้',
                ], 422);
            }
        }

        // Completion requirement guard
        if ($lesson->require_completion_before_exercises) {
            $isCourseAdmin = $course ? $course->isAdmin(auth()->user()) : false;
            if (! $lesson->canUserDoExercises(auth()->user(), $isCourseAdmin)) {
                return response()->json([
                    'success' => false,
                    'code' => 'LESSON_COMPLETION_REQUIRED',
                    'message' => 'กรุณาอ่านบทเรียนให้จบก่อนตอบคำถาม',
                ], 422);
            }
        }

        // Re-resolve the option through the same ownership constraint as the
        // validator (defence in depth, and guarantees a non-null model).
        $option = QuestionOption::where('id', $validated['answer_id'])
            ->where('optionable_id', $question->id)
            ->where('optionable_type', $question->getMorphClass())
            ->firstOrFail();

        // Flexible Scoring: If the chosen option is correct, give full points.
        $isCorrect = $option->is_correct;
        $points = $isCorrect ? $question->points : 0;

        // Snapshot completion state BEFORE persisting so `just_completed`
        // only fires on the transition false -> true.
        $totalQuestions = (int) $lesson->questions()->count();
        $previousCorrectCount = (int) $this->lessonAnswerScope($lesson, (int) auth()->id())->where('lesson_answer_questions.is_correct', true)->count();
        $wasAllCorrect = $totalQuestions > 0 && $previousCorrectCount >= $totalQuestions;

        $answerKeys = [
            'user_id' => auth()->id(),
            'lesson_id' => $lesson->id,
            'question_id' => $question->id,
        ];

        $answerValues = [
            'answer_id' => $validated['answer_id'],
            'points' => $points,
            'is_correct' => $isCorrect,
        ];

        // `updateOrCreate` is a check-then-insert race: two concurrent submissions
        // can both miss the SELECT and insert duplicate rows, double counting the
        // score. Once the unique index on (user_id, lesson_id, question_id) exists
        // the loser hits a constraint violation, which we recover from by updating
        // the row the winner just wrote. Without the index no violation is raised
        // and this behaves exactly as before.
        try {
            LessonAnswerQuestion::updateOrCreate($answerKeys, $answerValues);
        } catch (QueryException $e) {
            if (! $this->isUniqueViolation($e)) {
                throw $e;
            }

            LessonAnswerQuestion::where($answerKeys)->firstOrFail()->update($answerValues);
        }

        // Update CourseMember achieved score
        $courseId = $lesson->course_id;
        $courseMember = CourseMember::where('course_id', $courseId)
            ->where('user_id', auth()->id())
            ->first();

        if ($courseMember) {
            app(CourseScoreService::class)->recompute($courseMember);
        }

        return response()->json([
            'success' => true,
            'is_correct' => $isCorrect,
            'points' => $points,
            'message' => $isCorrect ? 'ถูกต้อง!' : 'ยังไม่ถูกต้อง',
            'quiz_summary' => $this->buildQuizSummary($lesson, (int) auth()->id(), $totalQuestions, $wasAllCorrect),
        ]);
    }

    /**
     * Detect a duplicate-key violation across drivers (MySQL 1062, SQLite 19/2067).
     */
    private function isUniqueViolation(QueryException $e): bool
    {
        $driverCode = (int) ($e->errorInfo[1] ?? 0);

        return in_array($driverCode, [1062, 19, 2067], true);
    }

    /**
     * Base query for the current user's answers restricted to questions that
     * still belong to this lesson (guards against orphaned answer rows).
     */
    private function lessonAnswerScope(Lesson $lesson, int $userId): Builder
    {
        return LessonAnswerQuestion::query()
            ->join('questions', 'questions.id', '=', 'lesson_answer_questions.question_id')
            ->where('lesson_answer_questions.user_id', $userId)
            ->where('lesson_answer_questions.lesson_id', $lesson->id)
            ->where('questions.questionable_type', $lesson->getMorphClass())
            ->where('questions.questionable_id', $lesson->id);
    }

    private function buildQuizSummary(Lesson $lesson, int $userId, int $totalQuestions, bool $wasAllCorrect): array
    {
        $totalPoints = (int) $lesson->questions()->sum('points');

        $stats = $this->lessonAnswerScope($lesson, $userId)
            ->selectRaw('COUNT(*) as answered_count')
            ->selectRaw('COALESCE(SUM(lesson_answer_questions.is_correct), 0) as correct_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN lesson_answer_questions.is_correct = 1 THEN lesson_answer_questions.points ELSE 0 END), 0) as earned_points')
            ->first();

        $answeredCount = (int) ($stats->answered_count ?? 0);
        $correctCount = (int) ($stats->correct_count ?? 0);
        $earnedPoints = (int) ($stats->earned_points ?? 0);

        $allAnswered = $totalQuestions > 0 && $answeredCount >= $totalQuestions;
        $allCorrect = $totalQuestions > 0 && $correctCount >= $totalQuestions;

        return [
            'total_questions' => $totalQuestions,
            'answered_count' => $answeredCount,
            'correct_count' => $correctCount,
            'earned_points' => $earnedPoints,
            'total_points' => $totalPoints,
            'all_answered' => $allAnswered,
            'all_correct' => $allCorrect,
            'percentage' => $totalPoints > 0 ? (int) round($earnedPoints / $totalPoints * 100) : 0,
            'just_completed' => $allCorrect && ! $wasAllCorrect,
        ];
    }
}
