<?php

namespace App\Http\Resources\Learn\Course\lessons;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TopicResource extends JsonResource
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
            'author' => new UserResource($this->user),
            'title' => $this->title,
            'content' => $this->content,
            'youtube_url' => $this->youtube_url,
            'min_read' => $this->min_read,
            'images' => TopicImageResource::collection($this->images),
            'view_count' => $this->view_count,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at,
            'diff_humans_created_at' => $this->created_at ? $this->created_at->diffForHumans() : null,
        ];
    }
}
