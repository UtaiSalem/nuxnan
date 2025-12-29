<?php

namespace App\Http\Resources\Learn\Course\attendances;

use Illuminate\Http\Request;
use App\Models\AttendanceDetail;
use App\Http\Resources\Learn\Course\attendances\AttendanceDetailResource;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseAttendanceResource extends JsonResource
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
            'course_id'             => $this->course_id,
            'group_id'              => $this->group_id,
            'instructor_id'         => $this->instructor_id,
            'date'                  => $this->date,
            'start_at'              => $this->start_at,
            'finish_at'             => $this->finish_at,
			'late_time'             => $this->late_time,
            'description'           => $this->description,
            'details'               => AttendanceDetailResource::collection($this->attendanceDetails),
            'authAtttendanceStatus' => AttendanceDetail::where('course_attendance_id', $this->id)->where('group_id', $this->group_id)->where('course_member_id', auth()->id())->first(),
        ];
    }
}
