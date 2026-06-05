<?php

namespace App\Http\Resources\Learn\Course\members;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseMemberResource extends JsonResource
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
            'user_id' => $this->user_id,
            'course_id' => $this->course_id,
            'user' => new UserResource($this->user),
            'member_name' => $this->member_name,
            'member_email' => $this->member_email,
            'group' => $this->group,
            'access_expiry_date' => $this->access_expiry_date,
            'completion_date' => $this->completion_date,
            'enrollment_date' => $this->enrollment_date,
            'grade_progress' => $this->getCalculatedGrade() ?? $this->grade_progress,
            'group_id' => $this->group_id,
            'achieved_score' => (float) ($this->achieved_score ?? 0),
            'bonus_points' => (float) ($this->bonus_points ?? 0),
            'external_score_points' => (float) ($this->external_score_points ?? 0),
            'total_earned_score' => (float) $this->getTotalAchievedScore(),
            'total_max_score' => (float) ($this->course->total_score ?? 0),
            'score_percentage' => (float) $this->getPercentageScore(),
            'notes_comments' => $this->notes_comments,
            'order_number' => $this->order_number,
            'member_code' => $this->member_code,

            // Grading Fields
            'draft_total_score' => (float) $this->draft_total_score,
            'draft_grade' => $this->draft_grade,
            'draft_grade_point' => (float) $this->draft_grade_point,
            'final_total_score' => (float) $this->final_total_score,
            'final_grade' => $this->final_grade,
            'final_grade_point' => (float) $this->final_grade_point,
            'grade_accepted_at' => $this->grade_accepted_at,
            'completion_status' => $this->completion_status,
            'completed_at' => $this->completed_at,

            // Effective identity fields (fallback to resolved data if not set on member)
            'effective_member_name' => $this->member_name ?? ($this->identity_data['member_name'] ?? $this->user->name),
            'effective_member_code' => $this->member_code ?? ($this->identity_data['member_code'] ?? null),
            'effective_order_number' => $this->order_number ?? ($this->identity_data['order_number'] ?? null),
            'identity_source' => $this->identity_data['source'] ?? 'unknown',

            'role' => $this->role,
            'status' => $this->status,
            'course_member_status' => $this->course_member_status,
            'group_member_status' => $this->group_member_status,
            'last_accessed_tab' => $this->last_accessed_tab ?? 0,
            'last_accessed_group_tab' => $this->last_accessed_group_tab ?? 0,
            'last_accessed_at' => $this->last_accessed_at,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
            'grade_name' => $this->getGradeName(),

            // Eligibility fields — populated by controller batch-compute (no N+1)
            'can_take_exam' => $this->eligibility_data['can_take_exam'] ?? null,
            'eligibility_status' => $this->eligibility_data['eligibility_status'] ?? null,
            'absence_percent' => $this->eligibility_data['attendance_stats']['absence_rate'] ?? null,
            'eligibility_unlocked_at' => $this->eligibility_unlocked_at ?? null,
            'unlock_options' => ($this->eligibility_data['can_take_exam'] ?? null) === false
                                            ? ($this->eligibility_data['unlock_options'] ?? null)
                                            : null,
        ];
    }
}
