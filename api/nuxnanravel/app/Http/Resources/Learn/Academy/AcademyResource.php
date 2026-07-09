<?php

namespace App\Http\Resources\Learn\Academy;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
// use App\Http\Resources\Learnd\Academy\AcademyResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademyResource extends JsonResource
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
            // 'author'                => new UserResource($this->user),
            // 'user'                  => new UserResource($this->user),
            'creater' => new UserResource($this->user),
            'name' => $this->name,
            'slogan' => $this->slogan,
            'address' => $this->address,
            'email' => $this->email,
            'phone' => $this->phone,
            'director' => new UserResource(User::find($this->director)),
            'established_year' => $this->established_year,
            'type' => $this->type,
            'accreditation' => $this->accreditation,
            'accreditation_body' => $this->accreditation_body,
            'total_students' => $this->total_students,
            'total_teachers' => $this->total_teachers,
            'courses_offered' => $this->courses_offered,
            'facilities' => $this->facilities,
            'academy_timings' => $this->academy_timings,
            'holidays' => $this->holidays,
            'social_media_links' => $this->social_media_links,
            'logo' => $this->logo,
            'logo_url' => $this->logo_url,
            'cover' => $this->cover,
            'cover_url' => $this->cover_url,
            'name_en' => $this->name_en,
            'description' => $this->description,
            'description_en' => $this->description_en,
            'website' => $this->website,
            'province' => $this->province,
            'country' => $this->country,
            'name_slug' => $this->name_slug,
            'privacy' => $this->getSettings()?->privacy ?? 'public',
            'join_mode' => $this->getSettings()?->join_mode ?? ($this->getSettings()?->auto_accept_members ? 'open' : 'approval'),
            'allow_student_registration' => $this->getSettings() ? (bool) $this->getSettings()->allow_student_registration : true,
            'allow_parent_registration' => $this->getSettings() ? (bool) $this->getSettings()->allow_parent_registration : true,
            'show_member_list' => $this->getSettings() ? (bool) $this->getSettings()->show_member_list : true,
            'show_course_list' => $this->getSettings() ? (bool) $this->getSettings()->show_course_list : true,
            // 'isMember'           => $this->isMember(auth()->user()),
            'setting' => $this->getSettings(),
            'memberStatus' => $this->memberStatus ?? $this->member_status($this->id),
            'authIsAcademyAdmin' => auth()->id() === $this->user_id,
        ];
    }
}
