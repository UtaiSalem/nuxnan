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
        // assignmentable เป็น morphTo — บทเรียน/รายวิชาปลายทางอาจถูกลบไปแล้ว
        // และ user() คืน null ได้เพราะ App\Models\User ใช้ SoftDeletes
        // ทั้งสองทางเคยทำ endpoint นี้พัง 500 ทั้งเส้น จึงต้อง null-safe ทุกจุด
        $assignment = $this->assignment;
        $answerUser = $this->user;

        // Resolve Course ID based on assignmentable type
        $courseId = null;
        if ($assignment?->assignmentable_type === 'App\Models\Lesson') {
            $courseId = $assignment->assignmentable?->course_id;
        } elseif ($assignment?->assignmentable_type === 'App\Models\Course') {
            $courseId = $assignment->assignmentable?->id;
        }

        // ใช้คอลัมน์ user_id ตรง ๆ ไม่ต้องผ่าน relation — กัน null และไม่ lazy load เพิ่ม
        $course_member = ($courseId && $this->user_id)
            ? CourseMember::where('user_id', $this->user_id)->where('course_id', $courseId)->first()
            : null;

        return [
            'id' => $this->id,
            'assignment_id' => $this->assignment_id,
            'student' => $answerUser ? new UserResource($answerUser) : null,
            'user_id' => $this->user_id,
            'user' => $answerUser?->id,
            // เดิม fallback เป็น $this->user->firstname.' '.$this->user->lastname
            // ซึ่งเป็นโค้ดตาย: ตาราง users ไม่มีคอลัมน์ firstname/lastname ได้ " " เสมอ
            'member_name' => $course_member ? $course_member->member_name : ($answerUser?->name ?? 'ผู้ใช้ที่ถูกลบ'),
            'course_group' => $course_member ? $course_member->group_id : null,
            'submission_date' => $this->submission_date,
            'content' => $this->content,
            'status' => $this->status,
            'points' => $this->points,
            'feedback' => $this->feedback,
            'late_submission' => $this->late_submission,
            'images' => $this->images,
            'attachments' => AssignmentAnswerAttachmentResource::collection($this->attachments),
            'created_at' => Carbon::parse($this->created_at)->setTimezone('Asia/Bangkok')->toIso8601String(),
            'updated_at' => $this->updated_at,
        ];
    }
}
