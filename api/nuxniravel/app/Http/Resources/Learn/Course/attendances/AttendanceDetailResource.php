<?php

namespace App\Http\Resources\Learn\Course\attendances;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceDetailResource extends JsonResource
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
            'course_attendance_id'  => $this->course_attendance_id,
            'status'                => $this->status,
            'comments'              => $this->comments,
            'course_member_id'      => $this->course_member_id,
        ];
    }
}
