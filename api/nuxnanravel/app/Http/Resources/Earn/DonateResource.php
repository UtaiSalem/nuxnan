<?php

namespace App\Http\Resources\Earn;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Number;

class DonateResource extends JsonResource
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
            'donor' => $this->relationLoaded('donor') && $this->donor
                                        ? new UserResource($this->donor)
                                        : null,
            'donor_name' => $this->donor_name ?? 'ไม่ประสงค์ออกนาม',
            'amounts' => $this->amounts !== null ? Number::currency($this->amounts, 'THB', 'th_TH') : '฿0.00',
            'total_points' => $this->total_points ?? 0,
            'transfer_date' => $this->transfer_date,
            'transfer_time' => $this->transfer_time,
            'donation_purpose' => $this->donation_purpose,
            'payment_method' => $this->payment_method,
            'transaction_id' => $this->transaction_id,
            'donor_address' => $this->donor_address,
            'status' => $this->status ?? 0,
            'privacy_settings' => $this->privacy_settings,
            'remaining_points' => $this->remaining_points ?? 0,
            'approved_by' => $this->approved_by,
            'notes' => $this->notes,
            'reviewed_at' => $this->reviewed_at,
            'review_note' => $this->review_note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'diff_humans_created_at' => $this->created_at ? $this->created_at->diffForHumans() : null,
        ];
    }
}
