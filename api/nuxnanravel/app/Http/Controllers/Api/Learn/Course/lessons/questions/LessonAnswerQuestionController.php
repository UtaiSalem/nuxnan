<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons\questions;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonAnswerQuestion;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Http\Request;

use App\Services\ContentVisibilityService;

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

        // Guard for students
        if (!$lesson->course->isAdmin($user)) {
            $this->visibility->assertVisibleOrFail($lesson, $user, 403);
        }

        $request->validate([
            'answer_id' => 'required|exists:question_options,id',
        ]);

        // Lifecycle guard: block lesson question answers after the course ends.
        $course = \App\Models\Course::find($lesson->course_id);
        if ($course) {
            $gate = \Illuminate\Support\Facades\Gate::inspect('submitLessonQuestion', $course);
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
            if (!$lesson->canUserDoExercises(auth()->user(), $isCourseAdmin)) {
                return response()->json([
                    'success' => false,
                    'code' => 'LESSON_COMPLETION_REQUIRED',
                    'message' => 'กรุณาอ่านบทเรียนให้จบก่อนตอบคำถาม',
                ], 422);
            }
        }

        // Verify answer belongs to question
        $option = QuestionOption::find($request->answer_id);

        // Flexible Scoring: If the chosen option is correct, give full points.
        $isCorrect = $option->is_correct;
        $points = $isCorrect ? $question->points : 0;

        $answer = LessonAnswerQuestion::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'lesson_id' => $lesson->id,
                'question_id' => $question->id,
            ],
            [
                'answer_id' => $request->answer_id,
                'points' => $points,
                'is_correct' => $isCorrect,
            ]
        );

        // Update CourseMember achieved score
        $courseId = $lesson->course_id;
        $courseMember = \App\Models\CourseMember::where('course_id', $courseId)
            ->where('user_id', auth()->id())
            ->first();

        if ($courseMember) {
            app(\App\Services\CourseScoreService::class)->recompute($courseMember);
        }

        return response()->json([
            'success' => true,
            'is_correct' => $isCorrect,
            'points' => $points,
            'message' => $isCorrect ? 'ถูกต้อง!' : 'ยังไม่ถูกต้อง',
        ]);
    }
}
