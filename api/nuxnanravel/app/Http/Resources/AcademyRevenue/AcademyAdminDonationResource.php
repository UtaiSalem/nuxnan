<?php

namespace App\Http\Resources\AcademyRevenue;

use Illuminate\Http\Resources\Json\JsonResource;

class AcademyAdminDonationResource extends JsonResource
{
    public function toArray($request): array
    {
        $email = $this->donor?->email;

        return [
            'id' => $this->id,
            'academy_id' => $this->academy_id,
            'academy' => [
                'id' => $this->academy?->id,
                'name' => $this->academy?->name,
            ],
            'donation_type' => $this->donation_type,
            'points_amount' => $this->points_amount,
            'cash_amount' => $this->cash_amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'purpose' => $this->purpose,
            'anonymous' => $this->anonymous,
            'donor_display_name' => $this->donor_display_name ?: ($this->donor?->name ?: 'ไม่ระบุชื่อ'),
            'donor' => [
                'id' => $this->donor_id,
                'name' => $this->donor?->name,
                'email' => $email,
                'avatar' => $this->donor?->avatar,
            ],
            'slip_path' => $this->slip_path,
            'payment_method' => $this->payment_method,
            'payment_reference' => $this->payment_reference,
            'created_at' => $this->created_at,
            'reviewed_at' => $this->reviewed_at,
            'reviewer' => $this->reviewer ? [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
            ] : null,
            'rejection_reason' => $this->rejection_reason,
            'academy_point_transaction_id' => $this->academy_point_transaction_id,
            'metadata' => $this->metadata,
        ];
    }
}
