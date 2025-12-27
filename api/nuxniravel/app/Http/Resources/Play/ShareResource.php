<?php

namespace App\Http\Resources\Play;

use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Learn\Academy\AcademyPostResource;
use App\Http\Resources\Learn\Course\posts\CoursePostResource;

class ShareResource extends JsonResource
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
            'user' => new UserResource($this->whenLoaded('user')),
            'shareable_type' => class_basename($this->shareable_type),
            'shareable_id' => $this->shareable_id,
            'shareable' => $this->getShareableResource(),
            'share_comment' => $this->share_comment, // ข้อความที่เขียนตอนแชร์
            'privacy' => $this->privacy,
            'likes' => $this->likes,
            'dislikes' => $this->dislikes,
            'comments' => $this->comments,
            'comments_count' => $this->comments,
            'share_comments' => $this->getShareComments(), // 3 comments ล่าสุด
            'views' => $this->views ?? 0,
            'diff_humans_created_at' => $this->created_at->diffForHumans(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get the appropriate resource for the shareable item
     */
    private function getShareableResource()
    {
        // Debug logging
        \Log::info('ShareResource getShareableResource', [
            'share_id' => $this->id,
            'shareable_type' => $this->shareable_type,
            'shareable_id' => $this->shareable_id,
            'relation_loaded' => $this->relationLoaded('shareable'),
            'shareable_exists' => !is_null($this->shareable),
        ]);

        // Check if shareable relationship is loaded and has value
        if (!$this->relationLoaded('shareable') || !$this->shareable) {
            \Log::warning('ShareResource: shareable not loaded or null', [
                'share_id' => $this->id,
                'relation_loaded' => $this->relationLoaded('shareable'),
            ]);
            return null;
        }

        $shareable = $this->shareable;
        $type = class_basename($this->shareable_type);

        \Log::info('ShareResource: creating resource', [
            'type' => $type,
            'shareable_class' => get_class($shareable),
        ]);

        switch ($type) {
            case 'Post':
                return new PostResource($shareable);
            case 'CoursePost':
                return new CoursePostResource($shareable);
            case 'AcademyPost':
                return new AcademyPostResource($shareable);
            default:
                return $shareable;
        }
    }

    /**
     * Get latest 3 share comments for pre-loading
     */
    private function getShareComments()
    {
        // Use loaded comments if available, otherwise query fresh
        $comments = $this->relationLoaded('shareComments') 
            ? $this->shareComments 
            : $this->getComments();

        return $comments->map(function($comment) {
            return [
                'id' => $comment->id,
                'user' => new UserResource($comment->user),
                'content' => $comment->content,
                'likes' => $comment->likes,
                'dislikes' => $comment->dislikes,
                'is_liked_by_auth' => $comment->is_liked_by_auth,
                'is_disliked_by_auth' => $comment->is_disliked_by_auth,
                'diff_humans_created_at' => $comment->diff_humans_created_at,
                'created_at' => $comment->created_at,
                'updated_at' => $comment->updated_at,
            ];
        });
    }
}
