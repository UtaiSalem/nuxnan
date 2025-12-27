<?php

namespace App\Http\Resources\Learn\Academy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademyPostCommentResource extends JsonResource
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
            'academy_post_id'           => $this->post_id,
            'user'              => new UserResource($this->user),
            'content'           => $this->content,
            'images'            => $this->images,
            'timestamp'         => $this->created_at->toDateTimeString(),
            'likes'             => $this->likes,
            'dislikes'          => $this->dislikes,
            'replies'           => $this->replies,
            // 'isLikedByAuth'     => $this->when(auth()->check(), function () {
            //                             return $this->likedPostComment()->where('user_id', auth()->id())->exists() ;
            //                         }),
            // 'isDislikedByAuth'  => $this->when(auth()->check(), function () {
            //                             return $this->dislikedComment()->where('user_id', auth()->id())->exists() ;
            //                         }),
            'isLikedByAuth'     => $this->likedPostComment()->where('user_id', auth()->id())->exists(),
            'isDislikedByAuth'  => $this->dislikedPostComment()->where('user_id', auth()->id())->exists(),
            
            'parent_comment_id' => $this->parent_comment_id,
            'sentiment'         => $this->sentiment,
            'privacy_settings' => $this->privacy_settings,
            // 'create_at' => $this->created_at->toDateTimeString(),
            'create_at'         => $this->created_at->diffForHumans(),
        ];
    }
}
