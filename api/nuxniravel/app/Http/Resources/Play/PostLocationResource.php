<?php

namespace App\Http\Resources\Play;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostLocationResource extends JsonResource
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
            'name'              => $this->name,
            'address'           => $this->address,
            'city'              => $this->city,
            'state'             => $this->state,
            'country'           => $this->country,
            'postal_code'       => $this->postal_code,
            'formatted_address' => $this->formatted_address,
            'coordinates'       => $this->coordinates,
            'latitude'          => $this->latitude,
            'longitude'         => $this->longitude,
            'place_id'          => $this->place_id,
            'place_type'        => $this->place_type,
            'icon_url'          => $this->icon_url,
            'metadata'          => $this->metadata,
            'created_at'        => $this->created_at,
        ];
    }
}
