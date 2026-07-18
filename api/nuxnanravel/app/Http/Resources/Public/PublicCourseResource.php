<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicCourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'slug' => $this->slug, 'name' => $this->name, 'title' => $this->title ?? $this->name, 'description' => mb_strlen((string) $this->description) > 240 ? mb_substr((string) $this->description, 0, 240).'…' : $this->description, 'cover' => $this->cover_url, 'academy' => $this->academy ? ['id' => $this->academy->id, 'name' => $this->academy->name, 'logo' => $this->academy->logo] : null, 'teacher_display_name' => $this->user?->name ?? $this->user?->display_name, 'donation_enabled' => $this->donationEnabled(), 'total_donated_points' => (int) ($this->total_donated_points ?? 0), 'total_donors' => (int) ($this->total_donors ?? 0), 'active_campaign_count' => (int) ($this->active_campaign_count ?? 0)];
    }
}
