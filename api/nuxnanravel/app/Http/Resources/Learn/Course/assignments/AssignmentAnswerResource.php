<?php

namespace App\Http\Resources\Learn\Course\assignments;

use App\Http\Resources\UserResource;
use App\Models\CourseMember;
use Carbon\Carbon;
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
        // Resolve Course ID based on assignmentable type
        $courseId = null;
        if ($this->assignment->assignmentable_type === 'App\Models\Lesson') {
            $courseId = $this->assignment->assignmentable->course_id;
        } elseif ($this->assignment->assignmentable_type === 'App\Models\Course') {
            $courseId = $this->assignment->assignmentable->id;
        }

        $course_member = $courseId ? CourseMember::where('user_id', $this->user->id)->where('course_id', $courseId)->first() : null;

        return [
            'id' => $this->id,
            // 'assignment'        => new AssignmentResource($this->assignment),
            // 'assignment'        => $this->assignment->assignmentable,
            'assignment_id' => $this->assignment_id,
            'student' => new UserResource($this->user),
            'user_id' => $this->user_id,
            'user' => $this->user->id,
            'member_name' => $course_member ? $course_member->member_name : ($this->user->firstname.' '.$this->user->lastname),
            // 'course_group'      => CourseMember::where('user_id', $this->user->id)->where('course_id', $this->assignment->assignmentable->id)->pluck('group_id')->first(),
            'course_group' => $course_member ? $course_member->group_id : null,
            'submission_date' => $this->submission_date,
            'content' => $this->content,
            'status' => $this->status,
            'points' => $this->points,
            'feedback' => $this->feedback,
            'late_submission' => $this->late_submission,
            'images' => $this->images,
            'created_at' => Carbon::parse($this->created_at)->setTimezone('Asia/Bangkok')->toIso8601String(),
            'updated_at' => $this->updated_at,
        ];
    }
}
