<?php

namespace App\Http\Resources\AcademyDonate;

use Illuminate\Http\Resources\Json\JsonResource;

class AcademyDonateResource extends JsonResource
{
    public function toArray($request): array
    {
        $email = $this->donor?->email;
        $admin = (bool) optional($request->user())->is_plearnd_admin;

        return ['id' => $this->id, 'academy_id' => $this->academy_id, 'academy' => ['id' => $this->academy?->id, 'name' => $this->academy?->name], 'donation_type' => $this->donation_type, 'points_amount' => $this->points_amount, 'cash_amount' => $this->cash_amount, 'currency' => $this->currency, 'status' => $this->status, 'purpose' => $this->purpose, 'anonymous' => $this->anonymous, 'donor_display_name' => $this->anonymous ? 'ผู้ไม่ประสงค์ออกนาม' : ($this->donor_display_name ?: $this->donor?->name), 'donor' => ['id' => $this->donor_id, 'email' => $admin ? $email : ($email ? substr($email, 0, 1).'***'.substr($email, -10) : null)], 'created_at' => $this->created_at, 'reviewed_at' => $this->reviewed_at, 'academy_point_transaction_id' => $this->academy_point_transaction_id];
    }
}
