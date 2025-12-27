<?php

namespace App\Http\Resources\Play;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostMentionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'user_id'       => $this->user_id,
            'username'      => $this->username,
            'user'          => $this->when($this->relationLoaded('user'), function () {
                return new UserResource($this->user);
            }),
            'mentioned_by'  => $this->mentioned_by,
            'position'      => $this->position,
            'created_at'    => $this->created_at,
        ];
    }
}
