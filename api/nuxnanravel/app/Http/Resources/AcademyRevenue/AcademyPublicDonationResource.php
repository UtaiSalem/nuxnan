<?php

namespace App\Http\Resources\AcademyRevenue;

use Illuminate\Http\Resources\Json\JsonResource;

class AcademyPublicDonationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'academy_id' => $this->academy_id,
            'academy' => [
                'id' => $this->academy?->id,
                'name' => $this->academy?->name,
            ],
            'donation_type' => $this->donation_type,
            'points_amount' => $this->when($this->donation_type === 'point', $this->points_amount),
            'cash_amount' => $this->when($this->donation_type === 'cash', $this->cash_amount),
            'currency' => $this->currency,
            'status' => $this->status,
            'purpose' => $this->purpose,
            'anonymous' => $this->anonymous,
            'donor_display_name' => $this->anonymous ? 'ผู้ไม่ประสงค์ออกนาม' : ($this->donor_display_name ?: ($this->donor?->name ?: 'ไม่ระบุชื่อ')),
            'donor' => null,
            'slip_path' => null,
            'created_at' => $this->created_at,
            'reviewed_at' => $this->reviewed_at,
        ];
    }
}
