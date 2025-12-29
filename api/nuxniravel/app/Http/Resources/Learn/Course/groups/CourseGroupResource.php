<?php

namespace App\Http\Resources\Learn\Course\groups;

use Illuminate\Http\Request;
use App\Http\Resources\Learn\Course\groups\CourseGroupMemberResource;
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
        // Use 'members' relationship which points to CourseMember model
        $groupMembers = $this->members;
        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'description'           => $this->description,
            'image_url'             => $this->image_url,
            'members_count'         => $groupMembers->count(),
            'members'               => $groupMembers->map(function($member) {
                $user = $member->user;
                return [
                    'id'            => $member->id,  // course_member_id
                    'course_id'     => $member->course_id,
                    'group_id'      => $member->group_id,
                    'user_id'       => $member->user_id,
                    'member_name'   => $member->member_name,
                    'order_number'  => $member->order_number,
                    'user'          => $user ? [
                        'id'        => $user->id,
                        'name'      => $user->name,
                        'avatar'    => $user->avatar,
                        'email'     => $user->email,
                    ] : null,
                    'avatar'        => $user?->avatar,
                    'name'          => $member->member_name ?? $user?->name,
                    'group'         => [
                        'id'        => $this->id,
                        'name'      => $this->name,
                    ],
                ];
            }),
            'groupMemberOfAuth'     => $groupMembers->where('user_id', auth()->id())->first(),
        ];
    }
}
