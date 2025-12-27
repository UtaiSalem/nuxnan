<?php

namespace App\Http\Resources\Learn\Course;

use Illuminate\Http\Request;
use App\Http\Resources\CourseMemberResource;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseGroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $members = $this->members;
        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'description'           => $this->description,
            'image_url'             => $this->image_url,
            'members'               => CourseMemberResource::collection($members),
            'groupMemberOfAuth'     => $members->where('user_id', auth()->id())->first(),
            // 'authGroupMember'       => $this->members()->where('user_id', auth()->id())->get(),
            // 'group_members'         => $members->where('group_id', $this->id)->all(),
        ];
    }
}
