<?php

namespace App\Http\Resources\Play;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'thumbnail' => $this->thumbnail_url ? url($this->thumbnail_url) : null,
            'video_url' => $this->video_url ? url($this->video_url) : null,
            'duration' => $this->duration,
            'views_count' => $this->views_count,
            'likes_count' => $this->likes_count,
            'privacy' => $this->privacy_label,
            'created_at' => $this->created_at?->toISOString(),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->first_name . ' ' . $this->user->last_name,
                    'avatar' => $this->user->avatar ? url($this->user->avatar) : null,
                ];
            }),
        ];
    }
}
