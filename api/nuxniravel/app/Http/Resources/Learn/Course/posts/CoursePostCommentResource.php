<?php

namespace App\Http\Resources\Learn\Course\posts;

use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class CoursePostCommentResource extends JsonResource
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
            'course_post_id'    => $this->course_post_id,
            'user'              => new UserResource($this->user),
            'content'           => $this->content,
            'images'            => $this->postCommentImages,
            'timestamp'         => $this->created_at->toDateTimeString(),
            'likes'             => $this->likes,
            'dislikes'          => $this->dislikes,
            'replies'           => $this->replies,

            'isLikedByAuth'     => $this->comment_likes()->where('user_id', auth()->id())->exists(),
            'isDislikedByAuth'  => $this->comment_dislikes()->where('user_id', auth()->id())->exists(),
            
            'parent_comment_id' => $this->parent_comment_id,
            'sentiment'         => $this->sentiment,
            'privacy_settings' => $this->privacy_settings,

            'create_at'         => $this->created_at->diffForHumans(),
        ];
    }
}
