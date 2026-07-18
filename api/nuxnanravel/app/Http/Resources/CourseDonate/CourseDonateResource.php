<?php

namespace App\Http\Resources\CourseDonate;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseDonateResource extends JsonResource
{
    public function toArray($request): array
    {
        $admin = (bool) optional($request->user())->is_plearnd_admin;
        $email = $this->donor?->email;

        return ['id' => $this->id, 'course_id' => $this->course_id, 'donation_type' => $this->donation_type, 'points_amount' => $this->points_amount, 'cash_amount' => $this->cash_amount, 'currency' => $this->currency, 'status' => $this->status, 'purpose' => $this->purpose, 'anonymous' => $this->anonymous, 'donor' => ['id' => $this->donor_id, 'display_name' => $this->anonymous ? 'ผู้บริจาคไม่ระบุนามสกุล' : ($this->donor_display_name ?: $this->donor?->name), 'email' => $admin ? $email : ($email ? substr($email, -4) : null)], 'created_at' => $this->created_at, 'reviewed_at' => $this->reviewed_at, 'course_point_transaction_id' => $this->course_point_transaction_id];
    }
}
