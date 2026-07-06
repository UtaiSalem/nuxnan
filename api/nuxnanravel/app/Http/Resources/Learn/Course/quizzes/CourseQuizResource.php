<?php

namespace App\Http\Resources\Learn\Course\quizzes;

use App\Http\Resources\Learn\Course\questions\QuestionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseQuizResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $current_result = $this->relationLoaded('userResults')
            ? $this->userResults->where('user_id', auth()->id())->first()
            : null;

        // Use whenLoaded for questions if it exists in the model
        $questions_count = $this->questions_count ?? ($this->relationLoaded('questions') ? $this->questions->count() : $this->questions()->count());

        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'image' => $this->image ? url($this->image) : null,
            'user' => $this->user,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'time_limit' => $this->time_limit,
            'total_score' => $this->total_score,
            'passing_score' => $this->passing_score,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'questions_count' => $questions_count,
            'member_achieved_score' => $current_result ? $current_result->score : 0,
            'course_members_score' => $current_result ? $current_result->score : 0,
            'current_result' => $current_result,
        ];

        // student_results and questions -> send only for show() not index()
        $isCourseAdmin = $this->resource->course ? $this->resource->course->isAdmin(auth()->user()) : false;

        return array_merge($data, [
            'student_results' => $this->when(
                $request->routeIs('*.show') && $this->relationLoaded('userResults') && $isCourseAdmin,
                fn () => $this->userResults
            ),
            'questions' => $this->when(
                $request->routeIs('*.show') && $this->relationLoaded('questions'),
                function () {
                    if ($this->shuffle_questions === 1) {
                        // Seed shuffle to be deterministic per user + quiz
                        // This ensures the student sees the same order if they refresh
                        $seed = auth()->id() + $this->id;
                        $questions = $this->questions->shuffle($seed);
                    } else {
                        $questions = $this->questions;
                    }

                    return QuestionResource::collection($questions)->resolve();
                }
            ),
        ]);
    }
}
