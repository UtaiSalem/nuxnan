<?php

namespace App\Http\Resources\Play;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class FriendshipResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'id' => $this->id,
            'sender' => new UserResource($this->sender),
            'recipient' => new UserResource($this->recipient),
            'sender_type' => $this->sender_type,
            'sender_id' => $this->sender_id,
            'recipient_type' => $this->recipient_type,
            'recipient_id' => $this->recipient_id,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
