<?php

namespace App\Http\Resources\CourseDonate;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseDonateResource extends JsonResource
{
    public function toArray($request): array
    {
        $admin = (bool) optional($request->user())->is_plearnd_admin;
        $email = $this->donor?->email;
        // Listing endpoints flag whether the viewer may see donor contact details;
        // anything that does not set it (own donations, admin lists) keeps the old behaviour.
        $contactVisible = $request->attributes->get('course_donate_contact_visible', true);
        $displayName = $this->anonymous ? 'ผู้ไม่ประสงค์ออกนาม' : ($this->donor_display_name ?: $this->donor?->name);

        return ['id' => $this->id, 'course_id' => $this->course_id, 'donation_type' => $this->donation_type, 'points_amount' => $this->points_amount, 'cash_amount' => $this->cash_amount, 'currency' => $this->currency, 'status' => $this->status, 'purpose' => $this->purpose, 'anonymous' => $this->anonymous, 'donor_display_name' => $displayName, 'donor' => ['id' => $this->anonymous ? null : $this->donor_id, 'display_name' => $displayName, 'email' => $contactVisible ? ($admin ? $email : ($email ? substr($email, -4) : null)) : null], 'created_at' => $this->created_at, 'reviewed_at' => $this->reviewed_at, 'course_point_transaction_id' => $this->course_point_transaction_id];
    }
}
