<?php

namespace App\Http\Resources\Learn\Course;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseGroupMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // 'id'            => $this->id,
            // 'course_id'     => $this->course_id,
            // 'group_id'      => $this->group_id,
            // 'user_id'       => $this->user_id,
            // 'user'          => $this->user,
            // 'group'         => $this->group,
            // 'created_at'    => $this->created_at,
            // 'updated_at'    => $this->updated_at,
            'member'           => $this->user,
        ];
    }
}
