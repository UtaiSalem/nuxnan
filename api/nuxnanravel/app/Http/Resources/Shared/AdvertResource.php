<?php

namespace App\Http\Resources\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdvertResource extends JsonResource
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
            'advertiser' => $this->whenLoaded('advertiser', function () {
                if (! $this->advertiser) {
                    return null;
                }

                return [
                    'id' => $this->advertiser->id,
                    'username' => $this->advertiser->username ?? $this->advertiser->name,
                    'display_name' => $this->advertiser->name,
                    'name' => $this->advertiser->name,
                    'avatar' => $this->advertiser->profile_photo_url,
                    'profile_photo_url' => $this->advertiser->profile_photo_url,
                ];
            }),
            'supporter' => $this->whenLoaded('advertiser', fn () => $this->advertiser?->name),
            'amounts' => $this->amounts,
            'title' => $this->title,
            'description' => $this->description,
            'media_link' => $this->media_link,
            'duration' => $this->duration,
            'total_views' => $this->total_views,
            'remaining_views' => $this->remaining_views,
            'status' => $this->status,
            'media_image' => $this->media_image,
            'transfer_date' => $this->transfer_date,
            'transfer_time' => $this->transfer_time,
            'gross_reward_per_view_per_second' => (int) config('campaign.gross_reward_per_view_per_second', 20),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
