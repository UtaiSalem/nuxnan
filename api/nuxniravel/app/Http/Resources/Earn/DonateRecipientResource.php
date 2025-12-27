<?php

namespace App\Http\Resources\Earn;

use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use App\Http\Resources\Earn\DonateResource;
use Illuminate\Http\Resources\Json\JsonResource;

class DonateRecipientResource extends JsonResource
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
            'donate_id' => $this->donate_id,
            'user_id' => $this->user_id,

            'donation' => new DonateResource($this->donation),
            'reciever' => new UserResource($this->reciever),
        ];
    }
}
