<?php

namespace App\Http\Resources\Learn\Academy;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
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
            'id'                    => $this->id,
            // 'author'                => new UserResource($this->user),
            // 'user'                  => new UserResource($this->user),
            'creater'               => new UserResource($this->user),
            'name'                  => $this->name,
            'slogan'                => $this->slogan,
            'address'               => $this->address,
            'email'                 => $this->email,
            'phone'                 => $this->phone,
            'director'              => new UserResource(User::find($this->director)),
            'established_year'      => $this->established_year,
            'type'                  => $this->type,
            'accreditation'         => $this->accreditation,
            'accreditation_body'    => $this->accreditation_body,
            'total_students'        => $this->total_students,
            'total_teachers'        => $this->total_teachers,
            'courses_offered'       => $this->courses_offered,
            'facilities'            => $this->facilities,
            'academy_timings'       => $this->academy_timings,
            'holidays'              => $this->holidays,
            'social_media_links'    => $this->social_media_links,
            'logo'                  => $this->logo,
            'cover'                 => $this->cover,
            // 'isMember'           => $this->isMember(auth()->user()),
            'setting'               => $this->academySetting,
            'memberStatus'          => $this->member_status($this->id),
            'authIsAcademyAdmin'    => auth()->id() === $this->user_id
        ];
    }
}
