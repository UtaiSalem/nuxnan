<?php

namespace App\Http\Resources\Learn\Academy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademyPostImageCommentResource extends JsonResource
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
            'post_image_id' => $this->post_image_id,
            'user_id'       => $this->user_id,
            'user'          => new UserResource($this->user),
            'content'       => $this->content,
            'likes'         => $this->likes,
            'dislikes'      => $this->dislikes,
            'replies'       => $this->replies,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
            'diff_humans_created_at'    => $this->created_at->diffForHumans(),
            'isLikedByAuth'     => $this->liked()->where('user_id', auth()->id())->exists(),
            'isDislikedByAuth'  => $this->disliked()->where('user_id', auth()->id())->exists(),
        ];
    }
}
