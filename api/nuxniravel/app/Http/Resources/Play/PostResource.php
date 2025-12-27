<?php

namespace App\Http\Resources\Play;

use App\Models\LikedPost;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use App\Http\Resources\Play\PostCommentResource;
use App\Http\Resources\Play\PostImageResource;
use App\Http\Resources\Play\PostLocationResource;
use App\Http\Resources\Play\PostMentionResource;
use App\Http\Resources\Play\PostTaggedUserResource;
use App\Http\Resources\Play\PostLinkPreviewResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Basic Info
            'id'                => $this->id,
            'author'            => new UserResource($this->user),       
            'content'           => $this->content,
            'post_url'          => $this->post_url,
            'status'            => $this->status,
            'post_type'         => $this->post_type,
            
            // Images/Media
            'images'            => PostImageResource::collection($this->postImages),
            'postImages'        => PostImageResource::collection($this->postImages),
            'imagesResources'   => PostImageResource::collection($this->postImages),
            'has_media'         => $this->postImages && $this->postImages->count() > 0,
            
            // Location
            'location'          => $this->location, // Simple string location
            'location_details'  => $this->when($this->relationLoaded('postLocation') && $this->postLocation, function () {
                return new PostLocationResource($this->postLocation);
            }),
            'has_location'      => !empty($this->location) || ($this->relationLoaded('postLocation') && $this->postLocation),
            
            // Feeling/Activity
            'feeling'           => $this->feeling,
            'feeling_icon'      => $this->feeling_icon,
            'activity_type'     => $this->activity_type,
            'activity_text'     => $this->activity_text,
            'feeling_text'      => $this->feeling_text, // Accessor for formatted feeling text
            'has_feeling'       => !empty($this->feeling) || !empty($this->activity_type),
            
            // Background/Theme (for status posts)
            'background'        => [
                'color'         => $this->background_color,
                'gradient'      => $this->background_gradient,
                'image'         => $this->background_image,
                'text_color'    => $this->text_color,
                'font_size'     => $this->font_size,
            ],
            'has_background'    => !empty($this->background_color) || !empty($this->background_gradient) || !empty($this->background_image),
            
            // Mentions (@users)
            'mentions'          => $this->when($this->relationLoaded('postMentions'), function () {
                return PostMentionResource::collection($this->postMentions);
            }),
            'mentioned_users'   => $this->when($this->relationLoaded('postMentions'), function () {
                return $this->postMentions->map(function ($mention) {
                    return [
                        'id' => $mention->user_id,
                        'username' => $mention->username,
                        'user' => $mention->user ? new UserResource($mention->user) : null,
                    ];
                });
            }),
            
            // Tagged Users
            'tagged_users'      => $this->when($this->relationLoaded('postTaggedUsers'), function () {
                return PostTaggedUserResource::collection($this->postTaggedUsers);
            }),
            'tagged_count'      => $this->when($this->relationLoaded('postTaggedUsers'), function () {
                return $this->postTaggedUsers->count();
            }),
            
            // Link Preview
            'link_preview'      => $this->when($this->relationLoaded('postLinkPreview') && $this->postLinkPreview, function () {
                return new PostLinkPreviewResource($this->postLinkPreview);
            }),
            'has_link_preview'  => $this->relationLoaded('postLinkPreview') && $this->postLinkPreview !== null,
            
            // Engagement
            'likes'             => $this->likes ?? 0,
            'dislikes'          => $this->dislikes ?? 0,
            'isLikedByAuth'     => $this->when(auth()->check(), function () {
                return $this->likedPost()->where('user_id', auth()->id())->exists();
            }),
            'isDislikedByAuth'  => $this->when(auth()->check(), function () {
                return $this->dislikedPost()->where('user_id', auth()->id())->exists();
            }),
            'comments'          => $this->comments ?? 0,
            'comments_count'    => $this->postComments ? $this->postComments->count() : ($this->comments_count ?? 0),
            'post_comments'     => $this->postComments ? PostCommentResource::collection($this->getComments()) : [],
            'shares'            => $this->shares ?? 0,
            'views'             => $this->views ?? 0,
            'engagement_rate'   => $this->engagement_rate ?? 0,
            
            // Options & Settings
            'privacy_settings'  => $this->privacy_settings,
            'privacy_label'     => $this->getPrivacyLabel(),
            'comments_disabled' => $this->comments_disabled ?? false,
            'is_pinned'         => $this->is_pinned ?? false,
            'is_edited'         => $this->is_edited ?? false,
            'edited_at'         => $this->edited_at,
            
            // Scheduling
            'is_scheduled'      => $this->is_scheduled ?? false,
            'is_published'      => $this->is_published ?? true,
            'scheduled_at'      => $this->scheduled_at,
            
            // Hashtags & Tags
            'hashtags'          => $this->hashtags,
            'tags'              => $this->tags,
            
            // Poll
            'poll_id'           => $this->poll_id,
            'has_poll'          => !empty($this->poll_id),
            
            // Meta
            'meta'              => $this->meta,
            'sentiment'         => $this->sentiment,
            'source_platform'   => $this->source_platform,
            
            // Timestamps
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
            'diff_humans_created_at' => $this->created_at ? $this->created_at->diffForHumans() : null,
            
            // Auth-specific
            'is_owner'          => $this->when(auth()->check(), function () {
                return $this->user_id === auth()->id();
            }),
            'can_edit'          => $this->when(auth()->check(), function () {
                return $this->user_id === auth()->id();
            }),
            'can_delete'        => $this->when(auth()->check(), function () {
                return $this->user_id === auth()->id();
            }),
        ];
    }

    /**
     * Get privacy label based on privacy_settings value.
     */
    protected function getPrivacyLabel(): array
    {
        $labels = [
            1 => ['en' => 'Only Me', 'th' => 'เฉพาะฉัน', 'icon' => 'lock'],
            2 => ['en' => 'Friends', 'th' => 'เพื่อน', 'icon' => 'users'],
            3 => ['en' => 'Public', 'th' => 'สาธารณะ', 'icon' => 'globe'],
        ];
        
        return $labels[$this->privacy_settings] ?? $labels[3];
    }
}
