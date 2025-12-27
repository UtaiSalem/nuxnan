<?php

namespace App\Http\Resources\Play;

use Illuminate\Http\Request;
use App\Http\Resources\Play\PostImageCommentResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PostImageResource extends JsonResource
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
            'post_id'           => $this->post_id,
            'filename'          => $this->filename,
            'url'               => $this->full_url,
            'full_url'          => $this->full_url,
            'likes'             => $this->likes,
            'dislikes'          => $this->dislikes,
            'comments'          => $this->comments,
            'shares'            => $this->shares,
            'views'             => $this->views,
            'image_comments'    => PostImageCommentResource::collection($this->image_comments()->latest()->limit(3)->get()),
            'isLikedByAuth'     => $this->likedPostImage()->where('user_id', auth()->id())->exists(),
            'isDislikedByAuth'  => $this->dislikedPostImage()->where('user_id', auth()->id())->exists(),
        ];
    }
}
