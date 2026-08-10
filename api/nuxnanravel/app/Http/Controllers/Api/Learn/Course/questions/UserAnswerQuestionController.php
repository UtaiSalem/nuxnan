<?php

namespace App\Http\Controllers\Api\Learn\Course\questions;

use App\Constants\QuizConstants;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseQuiz;
use App\Models\CourseQuizResult;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\UserAnswerQuestion;
use App\Services\ContentVisibilityService;
use App\Services\CourseScoreService;
use App\Services\QuizEfficiencyService;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserAnswerQuestionController extends Controller
{
    protected $quizEfficiencyService;

    protected ContentVisibilityService $visibility;

    public function __construct(QuizEfficiencyService $quizEfficiencyService, ContentVisibilityService $visibility)
    {
        $this->quizEfficiencyService = $quizEfficiencyService;
        $this->visibility = $visibility;
    }

    /**
     * Store a new user answer for a question.
     */
    public function store(CourseQuiz $quiz, Question $question, Request $request)
    {
        $user = auth()->user();
        $isCourseAdmin = false;

        $course = Course::find($quiz->course_id ?? $request->course_id);
        if ($course) {
            $isCourseAdmin = $course->isAdmin($user);
        }

        // Visibility guard for students
        if (! $isCourseAdmin) {
            $this->visibility->assertVisibleOrFail($quiz, $user, 403);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'answer_id' => 'required|exists:question_options,id',
            'started_at' => 'nullable|date',
            'course_id' => 'required|exists:courses,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Lifecycle guard: block regular quiz submissions after the course ends.
        $course = Course::find($quiz->course_id ?? $request->course_id);
        if ($course) {
            $gate = Gate::inspect('submitQuiz', $course);
            if ($gate->denied()) {
                return response()->json([
                    'success' => false,
                    'code' => $gate->code() ?: 'WORK_TYPE_LOCKED_AFTER_END',
                    'message' => $gate->message() ?: 'รายวิชาสิ้นสุดแล้ว ไม่สามารถส่งคำตอบได้',
                ], 422);
            }
        }

        return DB::transaction(function () use ($quiz, $question, $request) {
            // First check if an answer already exists
            $existingAnswer = UserAnswerQuestion::where([
                'user_id' => auth()->id(),
                'question_id' => $question->id,
                'quiz_id' => $quiz->id,
            ])->first();

            // If answer already exists, return error to use update method instead
            if ($existingAnswer) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณได้ตอบคำถามนี้ไปแล้ว',
                    'existing_answer_id' => $existingAnswer->id,
                    'answer_id' => $existingAnswer->answer_id,
                    'points' => $existingAnswer->points,
                ], 422);
            }

            // Additional validation: check if answer belongs to the question
            if (! $question->options()->where('id', $request->answer_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ตัวเลือกที่เลือกไม่ถูกต้องสำหรับคำถามนี้',
                ], 422);
            }

            // Create new answer since none exists
            $selectedOption = QuestionOption::find($request->answer_id);
            $isCorrect = $selectedOption && $selectedOption->is_correct;

            $userAnswerQuestion = UserAnswerQuestion::create([
                'user_id' => auth()->id(),
                'question_id' => $question->id,
                'quiz_id' => $quiz->id,
                'course_id' => $request->course_id,
                'correct_option_id' => $question->correct_option_id, // Keep this for legacy or reference, or set to null if cleaner
                'answer_id' => $request->answer_id,
                'points' => $isCorrect ? $question->points : 0,
            ]);

            // Update quiz results and member scores
            $this->updateCourseQuizResult($quiz, $request->started_at);
            $this->updateCourseMemberScore($quiz->course_id);

            return $this->generateResponse($userAnswerQuestion, $userAnswerQuestion->points);
        });
    }

    /**
     * Update an existing user answer for a question.
     */
    public function update(CourseQuiz $quiz, Question $question, UserAnswerQuestion $answer, Request $request)
    {
        $user = auth()->user();

        $course = Course::find($quiz->course_id);
        $isCourseAdmin = $course ? $course->isAdmin($user) : false;

        // Visibility guard for students
        if (! $isCourseAdmin) {
            $this->visibility->assertVisibleOrFail($quiz, $user, 403);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'answer_id' => 'required|exists:question_options,id',
            'started_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return DB::transaction(function () use ($quiz, $question, $answer, $request) {
            // Validate that the answer belongs to the user and question
            if ($answer->user_id !== auth()->id() || $answer->question_id !== $question->id || $answer->quiz_id !== $quiz->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถแก้ไขคำตอบนี้ได้',
                ], 403);
            }

            // Additional validation: check if answer belongs to the question
            if (! $question->options()->where('id', $request->answer_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ตัวเลือกที่เลือกไม่ถูกต้องสำหรับคำถามนี้',
                ], 422);
            }

            // Validate and deduct pp_fine (Plearnd Points) for editing an answer
            $ppFine = $question->pp_fine ?? 0;
            $user = auth()->user();

            if ($ppFine > 0) {
                if ($user->pp < $ppFine) {
                    return response()->json([
                        'success' => false,
                        'message' => "แต้มสะสมไม่เพียงพอ ต้องใช้ {$ppFine} แต้มในการแก้ไขคำตอบ",
                        'required_pp' => $ppFine,
                        'current_pp' => $user->pp,
                    ], 422);
                }

                // Deduct pp_fine from user
                $user->decrement('pp', $ppFine);
            }

            $oldAnswer = $answer->answer_id;
            $newAnswer = $request->answer_id;

            // Logic change: Check if the options themselves are marked as correct
            $oldOption = QuestionOption::find($oldAnswer);
            $newOption = QuestionOption::find($newAnswer);

            $isOldAnswerCorrect = $oldOption && $oldOption->is_correct;
            $isNewAnswerCorrect = $newOption && $newOption->is_correct;

            // Update the user's answer record
            $answer->update([
                'answer_id' => $newAnswer,
                'correct_option_id' => $question->correct_option_id,
                'points' => $isNewAnswerCorrect ? $question->points : 0,
                'edit_count' => $answer->edit_count + 1,
            ]);

            // Update quiz result and metrics
            $this->updateQuizResultAndMemberScore($quiz, $answer, $isOldAnswerCorrect, $isNewAnswerCorrect, $question->points, $request->started_at);

            return response()->json([
                'success' => true,
                'message' => 'แก้ไขคำตอบเรียบร้อยแล้ว',
                'authAnswerQuestion' => $answer->id,
                'points' => $answer->points,
                'is_correct' => $isNewAnswerCorrect,
                'pp_deducted' => $ppFine,
                'user_pp' => $user->fresh()->pp,
            ], 200);
        });
    }

    /**
     * Update or create a course quiz result record.
     */
    private function updateCourseQuizResult($quiz, $started_at = null)
    {
        $courseQuizResult = CourseQuizResult::firstOrCreate(
            [
                'course_id' => $quiz->course_id,
                'user_id' => auth()->id(),
                'quiz_id' => $quiz->id,
            ],
            [
                'started_at' => $started_at ?? now(),
            ]
        );

        // Update quiz metrics (e.g., score, percentage, status)
        $this->updateQuizMetrics($courseQuizResult, $quiz, $started_at);

        return $courseQuizResult;
    }

    /**
     * Update quiz result metrics and course member score after editing an answer.
     */
    private function updateQuizResultAndMemberScore($quiz, $answer, $isOldAnswerCorrect, $isNewAnswerCorrect, $points, $started_at)
    {
        $courseQuizResult = CourseQuizResult::where([
            'course_id' => $answer->course_id,
            'user_id' => $answer->user_id,
            'quiz_id' => $answer->quiz_id,
        ])->first();

        if (! $courseQuizResult) {
            Log::error('Course quiz result not found for answer ID: '.$answer->id);
            throw new \Exception('ไม่พบข้อมูลผลลัพธ์การทำแบบทดสอบ');
        }

        // Adjust score based on old and new answers
        if ($isOldAnswerCorrect && ! $isNewAnswerCorrect) {
            // Changed from correct to incorrect - deduct points
            $courseQuizResult->score -= $points;
        } elseif (! $isOldAnswerCorrect && $isNewAnswerCorrect) {
            // Changed from incorrect to correct - add points
            $courseQuizResult->score += $points;
        }

        $courseQuizResult->save();

        // Update quiz metrics (e.g., percentage, status) but not score since we already updated it
        $this->updateQuizMetricsWithoutScore($courseQuizResult, $quiz, $started_at);

        // Always recalculate the total course member score to ensure consistency.
        $this->updateCourseMemberScore($quiz->course_id);

        return $courseQuizResult;
    }

    /**
     * Update course member's achieved score.
     */
    private function updateCourseMemberScore($courseId)
    {
        $courseMember = CourseMember::where('course_id', $courseId)
            ->where('user_id', auth()->id())
            ->first();

        if (! $courseMember) {
            Log::error('Course member not found for course ID: '.$courseId);
            throw new \Exception('ไม่พบข้อมูลสมาชิกในคอร์สนี้');
        }

        app(CourseScoreService::class)->recompute($courseMember);

        return $courseMember;
    }

    /**
     * Update quiz metrics (e.g., attempted questions, correct answers, percentage, status).
     */
    private function updateQuizMetrics($courseQuizResult, $quiz, $started_at = null)
    {
        $quiz_user_answers = UserAnswerQuestion::where('user_id', auth()->id())
            ->where('quiz_id', $quiz->id)
            ->get();

        $courseQuizResult->attempted_questions = $quiz_user_answers->count();
        $courseQuizResult->correct_answers = $quiz_user_answers->filter(function ($answer) {
            return $answer->points > 0;
        })->count();

        $courseQuizResult->incorrect_answers = $quiz_user_answers->filter(function ($answer) {
            return $answer->points == 0;
        })->count();

        $courseQuizResult->score = $quiz_user_answers->sum('points');
        $courseQuizResult->percentage = $this->calculatePercentage($quiz->effectiveTotalScore(), $courseQuizResult->score);
        $courseQuizResult->status = round($courseQuizResult->percentage, 2) >= $quiz->passing_score
            ? QuizConstants::STATUS_PASSED
            : QuizConstants::STATUS_FAILED;

        if ($started_at) {
            $courseQuizResult->duration += max(0, now()->timestamp - $started_at->timestamp);
        }

        $courseQuizResult->efficiency = $this->quizEfficiencyService->calculateEfficiency($courseQuizResult, $quiz);
        $courseQuizResult->save();
    }

    /**
     * Update quiz metrics (e.g., attempted questions, correct answers, percentage, status) without recalculating score.
     */
    private function updateQuizMetricsWithoutScore($courseQuizResult, $quiz, $started_at = null)
    {
        $quiz_user_answers = UserAnswerQuestion::where('user_id', auth()->id())
            ->where('quiz_id', $quiz->id)
            ->get();

        $courseQuizResult->attempted_questions = $quiz_user_answers->count();
        $courseQuizResult->correct_answers = $quiz_user_answers->filter(function ($answer) {
            return $answer->points > 0;
        })->count();

        $courseQuizResult->incorrect_answers = $quiz_user_answers->filter(function ($answer) {
            return $answer->points == 0;
        })->count();

        // Don't recalculate score as it's already updated in the calling method
        $courseQuizResult->percentage = $this->calculatePercentage($quiz->effectiveTotalScore(), $courseQuizResult->score);
        $courseQuizResult->status = round($courseQuizResult->percentage, 2) >= $quiz->passing_score
            ? QuizConstants::STATUS_PASSED
            : QuizConstants::STATUS_FAILED;

        if ($started_at) {
            $courseQuizResult->duration += max(0, now()->timestamp - $started_at->timestamp);
        }

        $courseQuizResult->efficiency = $this->quizEfficiencyService->calculateEfficiency($courseQuizResult, $quiz);
        $courseQuizResult->save();
    }

    /**
     * Calculate percentage score.
     */
    private function calculatePercentage($totalScore, $score)
    {
        return $totalScore > 0 ? ($score / $totalScore) * 100 : 0;
    }

    /**
     * Create a new user answer record.
     */
    private function createUserAnswerQuestion($request, $quiz, $question, $points)
    {
        try {
            return auth()->user()->answerQuestions()->create([
                'course_id' => $request->course_id,
                'quiz_id' => $quiz->id,
                'question_id' => $question->id,
                'correct_option_id' => $question->correct_option_id,
                'answer_id' => $request->answer_id,
                'points' => $points,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create user answer question: '.$e->getMessage());
            throw $e; // Re-throw the exception for transaction rollback
        }
    }

    /**
     * Generate a JSON response.
     */
    private function generateResponse($userAnswerQuestion, $points)
    {
        if ($userAnswerQuestion) {
            return response()->json([
                'success' => true,
                'message' => 'บันทึกคำตอบเรียบร้อยแล้ว',
                'authAnswerQuestion' => $userAnswerQuestion->id,
                'points' => $points,
                'is_correct' => $points > 0,
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'ไม่สามารถบันทึกคำตอบได้',
            'points' => 0,
            'is_correct' => false,
        ], 500);
    }
}
