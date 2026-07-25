<?php

namespace App\Http\Resources\AcademyPointWithdrawal;

use Illuminate\Http\Resources\Json\JsonResource;

class AcademyPointWithdrawalResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'academy_id' => $this->academy_id, 'academy' => ['name' => $this->whenLoaded('academy', fn () => $this->academy->name)], 'amount' => $this->amount, 'purpose' => $this->purpose, 'status' => $this->status, 'requested_at' => $this->created_at, 'requester' => ['name' => $this->whenLoaded('requester', fn () => $this->requester->name)], 'reviewer' => ['name' => $this->whenLoaded('reviewer', fn () => $this->reviewer?->name)], 'reviewed_at' => $this->reviewed_at, 'approver' => ['name' => $this->whenLoaded('approver', fn () => $this->approver?->name)], 'approved_at' => $this->approved_at, 'payer' => ['name' => $this->whenLoaded('payer', fn () => $this->payer?->name)], 'paid_at' => $this->paid_at, 'payment_reference' => $this->payment_reference, 'admin_note' => $this->admin_note, 'rejection_reason' => $this->rejection_reason, 'has_proof' => (bool) $this->payout_proof_path, 'version' => $this->version];
    }
}
