<?php

namespace App\Http\Resources\Learn\Course\groups;

use Illuminate\Http\Request;
use App\Http\Resources\Learn\Course\groups\CourseGroupMemberResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CourseGroupResource extends JsonResource
{
    /**
     * Generate avatar URL for a user
     */
    private function getAvatarUrl($user): string
    {
        if (!$user) {
            return 'https://ui-avatars.com/api/?name=User&color=7F9CF5&background=EBF4FF';
        }
        
        if ($user->profile_photo_path) {
            // Check if it's already a full URL (e.g., Google profile photo)
            if (filter_var($user->profile_photo_path, FILTER_VALIDATE_URL)) {
                return $user->profile_photo_path;
            }
            // Local storage path - prepend backend URL
            return url(Storage::url($user->profile_photo_path));
        }
        
        // Fallback to UI Avatars
        return 'https://ui-avatars.com/api/?name=' . urlencode($user->name ?? 'User') . '&color=7F9CF5&background=EBF4FF';
    }

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
                $avatarUrl = $this->getAvatarUrl($user);
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
                        'avatar'    => $avatarUrl,
                        'email'     => $user->email,
                    ] : null,
                    'avatar'        => $avatarUrl,
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
