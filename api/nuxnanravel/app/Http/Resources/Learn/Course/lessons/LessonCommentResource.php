<?php

namespace App\Http\Resources\Learn\Course\lessons;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonCommentResource extends JsonResource
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
            'lesson_id' => $this->lesson_id,
            'user' => new UserResource($this->user),
            'content' => $this->content,
            'images' => $this->lessonCommentImages,
            'timestamp' => $this->created_at->toDateTimeString(),
            'likes' => $this->likes ?? 0,
            'dislikes' => $this->dislikes ?? 0,
            'replies' => LessonCommentResource::collection($this->replies()->with('user')->get()),
            'replies_count' => $this->replies()->count(),
            'isLikedByAuth' => $this->likeComment()->where('user_id', auth()->id())->exists(),
            'isDislikedByAuth' => $this->dislikeComment()->where('user_id', auth()->id())->exists(),
            'parent_id' => $this->parent_id,
            'sentiment' => $this->sentiment,
            'privacy_settings' => $this->privacy_settings,
            'create_at' => $this->created_at->diffForHumans(),
        ];
    }
}
