<?php

namespace App\Http\Resources\Learn\Academy\Enrollment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassroomStudentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'classroom_id' => $this->classroom_id,
            'academy_id' => $this->academy_id,
            'academic_year_id' => $this->academic_year_id,
            'academic_year' => $this->whenLoaded('academicYear', fn () => [
                'id' => $this->academicYear?->id,
                'name' => $this->academicYear?->name,
            ]),
            'student_number' => $this->student_number,
            'status' => $this->status,
            'status_text' => $this->status_text,
            'enrolled_at' => $this->enrolled_at?->toDateString(),
            'left_at' => $this->left_at?->toDateString(),
            'leave_reason' => $this->leave_reason,
            'rollover_batch_id' => $this->rollover_batch_id,
            'created_by' => $this->whenLoaded('createdBy', fn () => [
                'id' => $this->createdBy?->id,
                'name' => $this->createdBy?->name,
            ]),
            'classroom' => $this->whenLoaded('classroom', fn () => [
                'id' => $this->classroom->id,
                'display_name' => $this->classroom->display_name,
                'grade_level' => $this->classroom->grade_level,
                'section' => $this->classroom->section,
            ]),
            'student' => $this->whenLoaded(
                'student',
                fn () => (new StudentSummaryResource($this->student))->toArray($request)
            ),
        ];
    }
}
