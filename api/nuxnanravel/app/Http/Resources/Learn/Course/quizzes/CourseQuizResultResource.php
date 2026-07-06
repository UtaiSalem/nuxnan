<?php

namespace App\Http\Resources\Learn\Course\quizzes;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseQuizResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'course_id' => $this->course_id,
            'quiz_id' => $this->quiz_id,
            'score' => (float) $this->score,
            'percentage' => (float) $this->percentage,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'duration' => $this->duration,
            'attempted_questions' => $this->attempted_questions,
            'correct_answers' => $this->correct_answers,
            'incorrect_answers' => $this->incorrect_answers,
            'skipped_questions' => $this->skipped_questions,
            'passed' => (bool) $this->passed,
            'status' => $this->status,
            'efficiency' => (float) $this->efficiency,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
