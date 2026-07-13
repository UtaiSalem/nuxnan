<?php

namespace App\Http\Resources;

use App\Enums\StudentCardRequestReason;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentCardRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'academy_id' => $this->academy_id,
            'academic_year_id' => $this->academic_year_id,
            'classroom_id' => $this->classroom_id,
            'student_id' => $this->student_id,
            'existing_card_id' => $this->existing_card_id,
            'result_card_id' => $this->result_card_id,
            'request_type' => $this->request_type?->value,
            'status' => $this->status?->value,
            'priority' => $this->priority,
            'origin' => $this->origin?->value,
            'full_name' => $this->full_name_snapshot,
            'student_number' => $this->student_number_snapshot,
            'grade_level' => $this->grade_level_snapshot,
            'section' => $this->section_snapshot,
            'reason' => $this->reason,
            'reason_code' => $this->reason_code,
            'reason_label' => $this->reason_code ? StudentCardRequestReason::tryFrom($this->reason_code)?->label() : null,
            'requester_name' => $this->requester_name,
            'requester_phone' => $this->requester_phone,
            'admin_notes' => $this->admin_notes,
            'rejection_reason' => $this->rejection_reason,
            'requested_at' => $this->requested_at,
            'approved_at' => $this->approved_at,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'cancelled_at' => $this->cancelled_at,
            'rejected_at' => $this->rejected_at,
            'requested_by' => $this->whenLoaded('requestedBy', fn () => ['id' => $this->requestedBy?->id, 'name' => $this->requestedBy?->name]),
            'classroom' => $this->whenLoaded('classroom', fn () => $this->classroom
                ? new ClassroomSummaryResource($this->classroom)
                : null),
            'audit_logs' => $this->whenLoaded('auditLogs'),
        ];
    }
}
