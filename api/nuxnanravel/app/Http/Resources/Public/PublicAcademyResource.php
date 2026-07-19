<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicAcademyResource extends JsonResource
{
    public function toArray(Request $r): array
    {
        return ['id' => $this->id, 'slug' => $this->slug, 'name' => $this->name, 'description' => mb_strlen((string) $this->description) > 240 ? mb_substr((string) $this->description, 0, 240).'…' : $this->description, 'logo' => $this->logo_url, 'cover' => $this->cover_url, 'owner_display_name' => $this->user?->name ?? $this->user?->display_name, 'donation_enabled' => $this->donationEnabled(), 'total_donated_points' => (int) ($this->total_donated_points ?? 0), 'total_donors' => (int) ($this->total_donors ?? 0), 'courses_count' => (int) ($this->courses_count ?? 0)];
    }
}
