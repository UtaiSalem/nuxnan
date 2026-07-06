<?php

namespace App\Http\Resources\Learn\Academy;

use App\Models\Academy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademyMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'academy_id' => $this->academy_id,
            'user_id' => $this->user_id,
            'student_id' => $this->student_id,
            'member_code' => $this->member_code,
            'role' => $this->role,
            'status' => $this->status,
            'status_text' => $this->getStatusText(),
            'member_name' => $this->member_name,
            'member_avatar' => $this->member_avatar,
            'role_display_name' => $this->role_display_name,
            'enrollment_date' => $this->enrollment_date,
            'graduation_date' => $this->graduation_date,
            'note_comment' => $this->note_comment,
            'invited_by' => $this->invited_by,

            // Academy Role Information
            // Academy Role Information
            'academy_role_id' => $this->academy_role_id,
            'academy_role' => $this->whenLoaded('academyRole', function () {
                return $this->academyRole ? [
                    'id' => $this->academyRole->id,
                    'name' => $this->academyRole->name,
                    'display_name_th' => $this->academyRole->display_name_th,
                    'display_name_en' => $this->academyRole->display_name_en,
                    'color' => $this->academyRole->color,
                    'icon' => $this->academyRole->icon,
                    'permissions' => $this->academyRole->permissions,
                ] : null;
            }),

            // Role checks
            'is_admin' => $this->isAdmin(),
            'is_teacher' => $this->isTeacher(),
            'is_student' => $this->isStudent(),
            'is_parent' => $this->isParent(),

            // Relations
            'user' => $this->whenLoaded('user', function () {
                return $this->user ? [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'profile_photo_url' => $this->user->profile_photo_url,
                    'reference_code' => $this->user->reference_code,
                ] : null;
            }),
            'student' => $this->whenLoaded('student', function () {
                return $this->student ? [
                    'id' => $this->student->id,
                    'student_id' => $this->student->student_id,
                    'full_name_th' => $this->student->full_name_th,
                    'full_name_en' => $this->student->full_name_en,
                    'nickname' => $this->student->nickname,
                    'profile_image' => $this->student->profile_image,
                    'class_level' => $this->student->class_level,
                    'class_section' => $this->student->class_section,
                    'current_classroom' => $this->student->current_classroom,
                    'gender' => $this->student->gender,
                    'gender_text' => $this->student->gender_text,
                    'current_enrollment' => $this->student->relationLoaded('currentEnrollment') && $this->student->currentEnrollment ? [
                        'classroom_id' => $this->student->currentEnrollment->classroom_id,
                        'student_number' => $this->student->currentEnrollment->student_number,
                        'status' => $this->student->currentEnrollment->status,
                        'enrolled_at' => $this->student->currentEnrollment->enrolled_at?->toDateString(),
                    ] : null,
                ] : null;
            }),
            'inviter' => $this->whenLoaded('inviter', function () {
                return $this->inviter ? [
                    'id' => $this->inviter->id,
                    'name' => $this->inviter->name,
                    'profile_photo_url' => $this->inviter->profile_photo_url,
                ] : null;
            }),
            'tags' => $this->whenLoaded('tags', function () {
                return $this->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'color' => $tag->color,
                    ];
                });
            }),
            'academy' => new AcademyResource($this->whenLoaded('academy')),

            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get human-readable status text
     */
    protected function getStatusText(): string
    {
        return match ($this->status) {
            1 => 'รอการอนุมัติ',
            2 => 'สมาชิก',
            3 => 'ถูกปฏิเสธ',
            4 => 'ได้รับเชิญ',
            5 => 'ระงับ',
            default => 'ไม่ทราบ'
        };
    }
}
