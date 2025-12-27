<?php

namespace App\Http\Resources\Learn\Course;

use Carbon\Carbon;
use App\Models\CourseMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentAnswerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $course_member = CourseMember::where('user_id', $this->user->id)->where('course_id', $this->assignment->assignmentable->id)->first();

        return [
            'id' => $this->id,
            // 'assignment'        => new AssignmentResource($this->assignment),
            // 'assignment'        => $this->assignment->assignmentable,
            'assignment_id'     => $this->assignment_id,
            'student'           => new UserResource($this->user),
            'user'              => $this->user->id,
            'member_name'       => $course_member->member_name,
            // 'course_group'      => CourseMember::where('user_id', $this->user->id)->where('course_id', $this->assignment->assignmentable->id)->pluck('group_id')->first(),
            'course_group'      => $course_member->group_id,
            'submission_date'   => $this->submission_date,
            'content'           => $this->content,
            'status'            => $this->status,
            'points'            => $this->points,
            'late_submission'   => $this->late_submission,
            'images'            => $this->images,
            'created_at'        => Carbon::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('d/m/Y H:i:s'),
            'updated_at'        => $this->updated_at,
        ];
    }
}
