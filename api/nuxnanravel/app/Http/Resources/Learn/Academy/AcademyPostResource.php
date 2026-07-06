<?php

namespace App\Http\Resources\Learn\Academy;

use App\Constants\AcademyGroupTypes;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademyPostResource extends JsonResource
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
            'posted_as_group_id' => $this->posted_as_group_id,
            'posted_as_group' => $this->whenLoaded('postedAsGroup', function () {
                return [
                    'id' => $this->postedAsGroup->id,
                    'name' => $this->postedAsGroup->name,
                    'type' => $this->postedAsGroup->type,
                    'type_meta' => AcademyGroupTypes::get($this->postedAsGroup->type),
                ];
            }),
            'academy' => $this->academy ? new AcademyResource($this->academy) : null,
            'author' => $this->user ? new UserResource($this->user) : null,
            'content' => $this->content,
            'images' => $this->images,
            'imagesResources' => AcademyPostImageResource::collection($this->images),
            'post_url' => $this->academy_post_url,
            'status' => $this->status,
            'meta' => $this->meta,
            'likes' => $this->likes,
            'dislikes' => $this->dislikes,
            'isLikedByAuth' => $this->when(auth()->check(), function () {
                return $this->likedPost()->where('user_id', auth()->id())->exists();
            }),
            'isDislikedByAuth' => $this->when(auth()->check(), function () {
                return $this->dislikedPost()->where('user_id', auth()->id())->exists();
            }),
            'comments' => $this->comments,
            'comments_count' => $this->comments()->count(),
            'post_comments' => AcademyPostCommentResource::collection($this->getComments()),
            'shares' => $this->shares,
            'views' => $this->views,
            'hashtags' => $this->hashtags,
            'privacy_settings' => $this->privacy_settings,
            'location' => $this->location,
            'tags' => $this->tags,
            'sentiment' => $this->sentiment,
            'engagement_rate' => $this->engagement_rate,
            'post_type' => $this->post_type ?? 'regular',
            'target_audience' => $this->target_audience,
            'reward_points' => $this->reward_points,
            'embed_data' => $this->embed_data,
            'is_pinned' => $this->is_pinned,
            'source_platform' => $this->source_platform,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
            'diff_humans_created_at' => $this->created_at->diffForHumans(),
        ];
    }
}
