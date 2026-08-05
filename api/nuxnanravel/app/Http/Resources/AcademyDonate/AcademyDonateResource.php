<?php

namespace App\Http\Resources\AcademyDonate;

use Illuminate\Http\Resources\Json\JsonResource;

class AcademyDonateResource extends JsonResource
{
    public function toArray($request): array
    {
        $email = $this->donor?->email;
        $admin = (bool) optional($request->user())->is_plearnd_admin;
        // Listing endpoints flag whether the viewer may see donor contact details;
        // anything that does not set it (own donations, admin lists) keeps the old behaviour.
        $contactVisible = $request->attributes->get('academy_donate_contact_visible', true);
        $maskedEmail = $email ? substr($email, 0, 1).'***'.substr($email, -10) : null;

        return ['id' => $this->id, 'academy_id' => $this->academy_id, 'academy' => ['id' => $this->academy?->id, 'name' => $this->academy?->name], 'donation_type' => $this->donation_type, 'points_amount' => $this->points_amount, 'cash_amount' => $this->cash_amount, 'currency' => $this->currency, 'status' => $this->status, 'purpose' => $this->purpose, 'anonymous' => $this->anonymous, 'donor_display_name' => $this->anonymous ? 'ผู้ไม่ประสงค์ออกนาม' : ($this->donor_display_name ?: $this->donor?->name), 'donor' => ['id' => $this->anonymous ? null : $this->donor_id, 'email' => $contactVisible ? ($admin ? $email : $maskedEmail) : null], 'created_at' => $this->created_at, 'reviewed_at' => $this->reviewed_at, 'academy_point_transaction_id' => $this->academy_point_transaction_id];
    }
}
