<?php

namespace App\Http\Resources\Campaign;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'campaign_type' => $this->campaign_type?->value ?? $this->campaign_type,
            'scope_type' => $this->scope_type?->value ?? $this->scope_type,
            'advertiser' => $this->whenLoaded('advertiser', fn () => $this->advertiser ? [
                'id' => $this->advertiser->id,
                'name' => $this->advertiser->name,
                'display_name' => $this->advertiser->name,
                'avatar' => $this->advertiser->profile_photo_url,
                'profile_photo_url' => $this->advertiser->profile_photo_url,
            ] : null),
            'academy' => $this->whenLoaded('academy', fn () => $this->academy ? ['id' => $this->academy->id, 'name' => $this->academy->name, 'logo' => $this->academy->logo] : null),
            'course' => $this->whenLoaded('course', fn () => $this->course ? ['id' => $this->course->id, 'title' => $this->course->name] : null),
            'beneficiary' => $this->whenLoaded('beneficiary', fn () => $this->beneficiary ? ['id' => $this->beneficiary->id, 'name' => $this->beneficiary->name] : null),
            'inherit_to_academy' => (bool) $this->inherit_to_academy,
            'title' => $this->title,
            'description' => $this->description,
            'media_link' => $this->media_link,
            'media_image' => $this->media_image,
            'budget_amount' => $this->budget_amount,
            'duration' => $this->duration,
            'total_views' => $this->total_views,
            'remaining_views' => $this->remaining_views,
            'impressions_count' => $this->impressions_count,
            'active_from' => $this->active_from,
            'active_until' => $this->active_until,
            'payment_status' => $this->payment_status?->value ?? $this->payment_status,
            'review_status' => $this->review_status?->value ?? $this->review_status,
            'amounts' => $this->amounts,
            'status' => $this->status,
            'supporter' => $this->whenLoaded('advertiser', fn () => $this->advertiser?->name),
            'reviewer' => $this->whenLoaded('reviewer', fn () => $this->reviewer ? ['id' => $this->reviewer->id, 'name' => $this->reviewer->name] : null),
            'reviewed_at' => $this->reviewed_at,
            'reward_per_view' => ($this->campaign_type?->value ?? $this->campaign_type) === 'advertisement'
                ? $this->rewardPerView() : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Total baht a viewer earns per completed view — must mirror CampaignViewService::rewardedView().
     */
    private function rewardPerView(): float
    {
        $duration = (int) $this->duration;
        $pointsPerBaht = (float) config('campaign.points_per_reward_baht', 1200);
        $base = $duration * (float) config('campaign.viewer_reward_per_second');
        $bonus = $pointsPerBaht > 0 ? ($duration * 20) / $pointsPerBaht : 0;

        return round($base + $bonus, 2);
    }
}
