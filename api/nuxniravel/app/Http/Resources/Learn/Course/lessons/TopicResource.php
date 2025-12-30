<?php

namespace App\Http\Resources\Learn\Course\lessons;

use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
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
            'id'                => $this->id,
            'author'            => new UserResource($this->user),    
            'title'             => $this->title,
            'content'           => $this->content,
            'images'            => $this->images,
            'status'            => $this->status,
            'public'            => $this->public,
            'meta'              => $this->meta,
            'likes'             => $this->likes,
            'dislikes'          => $this->dislikes,
            'comments'          => $this->comments,
            // 'comments_count'    => $this->postComments->count(),
            // 'post_comments'     => PostCommentResource::collection($this->getComments()),
            'shares'            => $this->shares,
            'views'             => $this->views,
            'hashtags'          => $this->hashtags,
            'privacy_settings'  => $this->privacy_settings,
            'location'          => $this->location,
            'url'               => $this->url,
            'tags'              => $this->tags,
            'sentiment'         => $this->sentiment,
            'engagement_rate'   => $this->engagement_rate,
            'post_type'         => $this->post_type,
            'source_platform'   => $this->source_platform,
            'parent_post_id'    => $this->parent_post_id,
            'created_at'        => $this->created_at,
            'diff_humans_created_at'    => $this->created_at->diffForHumans(),
        ];
    }
}
