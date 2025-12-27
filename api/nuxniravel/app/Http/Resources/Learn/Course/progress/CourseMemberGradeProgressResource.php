<?php

namespace App\Http\Resources\Learn\Course;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseMemberGradeProgressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'course_id'         => $this->course_id,
            'user_id'           => $this->user_id,
            'course'            => new CourseResource($this->course),
            'user'              => new UserResource($this->user),
            'grade'             => $this->grade,
            'progress'          => $this->progress,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
