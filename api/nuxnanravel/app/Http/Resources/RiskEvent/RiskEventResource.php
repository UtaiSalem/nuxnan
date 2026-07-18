<?php

namespace App\Http\Resources\RiskEvent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiskEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subject = $this->subject;
        $label = $subject?->name ?? $subject?->title ?? $subject?->email ?? '';

        return ['id' => $this->id, 'rule_name' => $this->rule_name, 'subject_type' => $this->subject_type, 'subject_id' => $this->subject_id, 'severity' => $this->severity, 'score' => $this->score, 'evidence' => $this->evidence, 'status' => $this->status, 'resolved_by' => $this->resolved_by, 'resolved_at' => $this->resolved_at, 'resolution_note' => $this->resolution_note, 'deduplication_key' => $this->deduplication_key, 'created_at' => $this->created_at, 'updated_at' => $this->updated_at, 'subject_summary' => class_basename((string) $this->subject_type).' #'.$this->subject_id.($label ? ' — '.$label : '')];
    }
}
