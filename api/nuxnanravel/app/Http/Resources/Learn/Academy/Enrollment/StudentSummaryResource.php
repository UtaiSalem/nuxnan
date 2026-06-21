<?php

namespace App\Http\Resources\Learn\Academy\Enrollment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'academy_id' => $this->academy_id,
            'first_name_th' => $this->first_name_th,
            'last_name_th' => $this->last_name_th,
            'nickname' => $this->nickname,
            'status' => $this->status,
            'class_level' => $this->class_level,
            'class_section' => $this->class_section,
        ];
    }
}
