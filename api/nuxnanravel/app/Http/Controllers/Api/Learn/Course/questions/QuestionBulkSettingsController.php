<?php

namespace App\Http\Controllers\Api\Learn\Course\questions;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseQuiz;
use App\Models\CourseQuizResult;
use App\Models\Lesson;
use App\Models\LessonAnswerQuestion;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\UserAnswerQuestion;
use App\Services\CourseScoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * One click for the teacher: set the score and/or the re-answer / reveal fee
 * (pp_fine) of EVERY question in a lesson's question set or in a quiz.
 *
 * Changing points also re-scores answers already given, so totals, quiz
 * results and course member scores stay consistent.
 */
class QuestionBulkSettingsController extends Controller
{
    /** PATCH /lessons/{lesson}/questions/bulk-settings */
    public function lesson(Request $request, Lesson $lesson): JsonResponse
    {
        $course = $lesson->course;
        abort_unless($course, 404);
        Gate::authorize('manage', $course);

        $data = $this->validated($request);

        $count = DB::transaction(function () use ($lesson, $data) {
            $count = $lesson->questions()->update($data);

            // Keep the per-answer snapshot in line with the new score.
            if (array_key_exists('points', $data)) {
                LessonAnswerQuestion::where('lesson_id', $lesson->id)
                    ->whereIn('question_id', $lesson->questions()->select('id'))
                    ->update(['points' => DB::raw('CASE WHEN is_correct = 1 THEN '.(float) $data['points'].' ELSE 0 END')]);
            }

            return $count;
        });

        $this->resyncCourse($course, array_key_exists('points', $data));

        return response()->json([
            'success' => true,
            'updated' => $count,
            'message' => "อัปเดตคำถามทั้งหมด {$count} ข้อแล้ว",
        ]);
    }

    /** PATCH /courses/{course}/quizzes/{quiz}/questions/bulk-settings */
    public function quiz(Request $request, Course $course, CourseQuiz $quiz): JsonResponse
    {
        abort_if((int) $quiz->course_id !== (int) $course->id, 404);
        Gate::authorize('manage', $course);

        $data = $this->validated($request);

        $count = DB::transaction(function () use ($quiz, $data) {
            // One bulk UPDATE bypasses the per-question observer; the quiz
            // counters are recomputed once below instead.
            $count = $quiz->questions()->update($data);
            $quiz->syncQuestionCounters();

            if (array_key_exists('points', $data)) {
                $this->rescoreQuizResults($quiz->fresh());
            }

            return $count;
        });

        $this->resyncCourse($course, array_key_exists('points', $data));

        return response()->json([
            'success' => true,
            'updated' => $count,
            'quiz_total_score' => (float) $quiz->fresh()->total_score,
            'message' => "อัปเดตคำถามทั้งหมด {$count} ข้อแล้ว",
        ]);
    }

    /**
     * @return array{points?: float, pp_fine?: int}
     */
    private function validated(Request $request): array
    {
        $request->validate([
            'points' => ['nullable', 'numeric', 'min:0.5', 'max:1000', 'multiple_of:0.5', 'required_without:pp_fine'],
            'pp_fine' => ['nullable', 'integer', 'min:0', 'max:100000', 'required_without:points'],
        ], [
            'points.required_without' => 'กรุณาระบุคะแนนหรือแต้มที่หักอย่างน้อยหนึ่งอย่าง',
            'pp_fine.required_without' => 'กรุณาระบุคะแนนหรือแต้มที่หักอย่างน้อยหนึ่งอย่าง',
        ]);

        $data = [];
        if ($request->filled('points')) {
            $data['points'] = (float) $request->input('points');
        }
        if ($request->filled('pp_fine')) {
            $data['pp_fine'] = (int) $request->input('pp_fine');
        }

        return $data;
    }

    /**
     * Re-score answers already given to this quiz with the new per-question
     * points. Pass/fail is only re-evaluated for finished attempts, so an
     * attempt still in progress is never closed by a settings change.
     */
    private function rescoreQuizResults(CourseQuiz $quiz): void
    {
        $questions = $quiz->questions()->get(['id', 'points'])->keyBy('id');
        $correctOptionIds = QuestionOption::whereIn('optionable_id', $questions->keys())
            ->where('optionable_type', (new Question)->getMorphClass())
            ->where('is_correct', true)
            ->pluck('id')
            ->flip();

        UserAnswerQuestion::where('quiz_id', $quiz->id)->chunkById(200, function ($answers) use ($questions, $correctOptionIds) {
            foreach ($answers as $answer) {
                $question = $questions->get($answer->question_id);
                $points = $question && $correctOptionIds->has($answer->answer_id) ? (float) $question->points : 0;

                if ((float) $answer->points !== $points) {
                    $answer->update(['points' => $points]);
                }
            }
        });

        $total = (float) $quiz->total_score;

        CourseQuizResult::where('quiz_id', $quiz->id)->chunkById(200, function ($results) use ($quiz, $total) {
            foreach ($results as $result) {
                $score = (float) UserAnswerQuestion::where('quiz_id', $quiz->id)
                    ->where('user_id', $result->user_id)
                    ->sum('points');
                $percentage = $total > 0 ? $score / $total * 100 : 0;

                $update = ['score' => $score, 'percentage' => $percentage];
                if ($result->completed_at) {
                    $update['status'] = round($percentage, 2) >= $quiz->passing_score ? 3 : 4; // PASSED : FAILED
                }

                $result->update($update);
            }
        });
    }

    private function resyncCourse(Course $course, bool $pointsChanged): void
    {
        $scores = app(CourseScoreService::class);
        $scores->syncCourseTotalScore($course);

        if ($pointsChanged) {
            $scores->syncAllCourseMembers($course);
        }
    }
}
