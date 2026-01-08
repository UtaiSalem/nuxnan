<?php

namespace App\Http\Resources\Learn\Course\info;

use Illuminate\Http\Request;

class MemberedCourseResource extends CourseResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        
        $member = $this->courseMembers->where('user_id', auth()->id())->first();

        $data['auth_role'] = $member?->role;
        $data['auth_progress'] = $member?->getPercentageScore() ?? 0;

        return $data;
    }
}
