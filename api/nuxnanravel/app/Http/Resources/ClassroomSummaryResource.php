<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassroomSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name ?: "{$this->grade_level}/{$this->section}",
            'grade_level' => $this->grade_level,
            'section' => $this->section,
            'academic_year' => $this->whenLoaded('academicYear', fn () => $this->academicYear ? [
                'id' => $this->academicYear->id,
                'name' => $this->academicYear->name,
            ] : null),
            'student_count' => $this->student_count ?? null,
            'homeroom_teacher' => $this->whenLoaded('homeroomTeacher', fn () => $this->homeroomTeacher ? [
                'id' => $this->homeroomTeacher->id,
                'name' => $this->homeroomTeacher->name,
                'profile_image_url' => $this->homeroomTeacher->profile_photo_url,
            ] : null),
        ];
    }
}
