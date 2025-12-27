<?php

namespace App\Http\Resources\Learn\Course\posts;

use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use App\Http\Resources\CoursePostImageResource;
use Illuminate\Http\Resources\Json\JsonResource;

class CoursePostImageCommentResource extends JsonResource
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
            'image_id'      => $this->course_post_image_id,
            // 'image'         => new CoursePostImageResource($this->post_image),
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
