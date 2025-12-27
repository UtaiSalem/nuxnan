<?php

namespace App\Http\Resources\Shared;

use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'advertiser'        => new UserResource($this->advertiser),
            'amounts'           => $this->amounts,
            'duration'          => $this->duration,
            'total_views'       => $this->total_views,
            'remaining_views'   => $this->remaining_views,
            'slip'              => $this->slip,
            'status'            => $this->status,
            'media_image'       => $this->media_image,
            'transfer_date'     => $this->transfer_date,
            'transfer_time'     => $this->transfer_time,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
