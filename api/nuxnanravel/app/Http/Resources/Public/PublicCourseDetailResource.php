<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;

class PublicCourseDetailResource extends PublicCourseResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), ['description' => $this->description, 'support_summary' => $this->support_summary ?? null]);
    }
}
