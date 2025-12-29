<?php

namespace App\Http\Resources\Learn\Course\groups;

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
            'id'            => $this->id,  // course_member_id
            'course_id'     => $this->course_id,
            'group_id'      => $this->group_id,
            'user_id'       => $this->user_id,
            'member_name'   => $this->member_name,
            'order_number'  => $this->order_number,
            'user'          => $this->user,
            'avatar'        => $this->user->avatar ?? null,
            'name'          => $this->member_name ?? $this->user->name,
        ];
    }
}
