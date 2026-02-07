<?php

namespace App\Http\Resources\Learn\Course\info;

use Illuminate\Http\Request;

class UserProfileCourseResource extends CourseResource
{
    protected $targetMember;

    public function __construct($resource, $targetMember = null)
    {
        parent::__construct($resource);
        $this->targetMember = $targetMember;
    }

    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        
        // Override auth_role and auth_progress with target user's data
        if ($this->targetMember) {
             $data['auth_role'] = $this->targetMember->role;
             $data['auth_progress'] = $this->targetMember->getPercentageScore() ?? 0;
             $data['completion_date'] = $this->targetMember->completion_date;
             $data['grade'] = $this->targetMember->getGradeName();
             $data['enrollment_date'] = $this->targetMember->enrollment_date;
        }

        return $data;
    }
}
