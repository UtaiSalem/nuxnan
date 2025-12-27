<?php

namespace App\Http\Resources\Learn\Course;

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
        $member_achieved_score  = $this->userResults()->where('user_id', auth()->id())->where('course_id', $this->course_id)->first();
        $course_members_score   = $this->userResults()->where('course_id', $this->course_id)->get();
        $questions              = $this->shuffle_questions === 1 ? $this->questions->shuffle() : $this->questions;
        return [
            'id'                => $this->id,
            'course_id'         => $this->course_id,
            'title'             => $this->title,
            'description'       => $this->description,
            'time_limit'        => $this->time_limit,
            'total_score'       => $this->total_score,
            'total_questions'   => $this->total_questions ?? 0,
            'is_active'         => $this->is_active,
            'start_date'        => $this->start_date,
            'end_date'          => $this->end_date,
            'shuffle_questions' => $this->shuffle_questions,
            'passing_score'     => $this->passing_score,
            'questions'         => QuestionResource::collection($questions),
            'member_achieved_score' => $member_achieved_score ? $member_achieved_score->score : 0,
            'course_members_score' => $course_members_score,
            // 'start_date'        => $this->start_date->format('Y-m-d H:i:s'), // Format start_date
            // 'end_date'          => $this->end_date->format('Y-m-d H:i:s'), // Format end_date
        ];
    }
}
