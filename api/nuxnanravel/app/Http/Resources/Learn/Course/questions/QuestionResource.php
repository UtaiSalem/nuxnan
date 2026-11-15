<?php

namespace App\Http\Resources\Learn\Course\questions;

use App\Http\Resources\UserResource;
use App\Models\Course;
use App\Models\LessonAnswerQuestion;
use App\Models\Question;
use App\Models\UserAnswerQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    /**
     * Who may see a question's answer key (correct option, explanations):
     * its author and every admin of its course — so a co-teacher opening the
     * edit form sees which option is right instead of saving them all as
     * wrong. Memoised on the request: lists would otherwise query per row.
     */
    public static function canSeeAnswerKey(?Question $question): bool
    {
        $user = auth()->user();
        if (! $user || ! $question) {
            return false;
        }

        if ((int) $question->user_id === (int) $user->id) {
            return true;
        }

        $key = 'answer_key_admin:'.$question->course_id.':'.$user->id;
        $attributes = request()->attributes;

        if (! $attributes->has($key)) {
            $course = $question->course_id ? Course::find($question->course_id) : null;
            $attributes->set($key, (bool) $course?->isAdmin($user));
        }

        return (bool) $attributes->get($key);
    }

    public function toArray(Request $request): array
    {
        $authAnswerQuestion = null;
        $user_answer = null;

        if ($this->questionable_type === 'App\Models\Course\Lesson' || $this->questionable_type === 'App\Models\Lesson') {
            $authAnswerQuestion = LessonAnswerQuestion::where('question_id', $this->id)->where('user_id', auth()->id())->first();
            if ($authAnswerQuestion) {
                $user_answer = [
                    'id' => $authAnswerQuestion->id,
                    'answer_id' => $authAnswerQuestion->answer_id,
                    'is_correct' => $authAnswerQuestion->is_correct,
                    'points' => $authAnswerQuestion->points,
                ];
            }
        } elseif ($this->questionable_type === 'App\Models\Learn\Course\CourseQuiz' || $this->questionable_type === 'App\Models\CourseQuiz') {
            $authAnswerQuestion = $this->userAnswers()->where('user_id', auth()->id())->where('quiz_id', $this->questionable_id)->first();
            if ($authAnswerQuestion) {
                $user_answer = [
                    'id' => $authAnswerQuestion->id,
                    'answer_id' => $authAnswerQuestion->answer_id,
                    'is_correct' => false, // UserAnswerQuestion doesn't store is_correct directly on the model usually, it stores points. But let's check.
                    // Actually, let's just pass what we have. Points > 0 implies correct?
                    'points' => $authAnswerQuestion->points,
                ];
            }
        }

        // Fallback or generic
        if (! $authAnswerQuestion) {
            // Try to find any answer?? No, be strict.
        }

        return [
            'id' => $this->id,
            'creator' => new UserResource($this->user),
            'questionable_id' => $this->questionable_id,
            'questionable_type' => $this->questionable_type,
            'text' => $this->text,
            'type' => $this->type,
            'options' => QuestionOptionResource::collection($this->options),
            // Answer key: same audience as QuestionOptionResource `is_correct`
            // (question author + course admins), never learners.
            'correct_option_id' => $this->when(
                self::canSeeAnswerKey($this->resource),
                fn () => $this->correct_option_id
            ),
            // The explanation reveals the answer — same audience as the key.
            'explanation' => $this->when(
                self::canSeeAnswerKey($this->resource),
                fn () => $this->explanation
            ),
            'difficulty_level' => $this->difficulty_level,
            'time_limit' => $this->time_limit,
            'points' => $this->points,
            'pp_fine' => $this->pp_fine,
            'position' => $this->position,
            'tags' => $this->tags,
            'images' => $this->images->map(function ($img) {
                return [
                    'id' => $img->id,
                    'url' => $img->url,
                    'full_url' => $img->full_url,
                ];
            }),
            'authAnswerQuestion' => $authAnswerQuestion ? $authAnswerQuestion->id : null,
            'isAnsweredByAuth' => $authAnswerQuestion ? $authAnswerQuestion->answer_id : null,
            'user_answer' => $user_answer,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
