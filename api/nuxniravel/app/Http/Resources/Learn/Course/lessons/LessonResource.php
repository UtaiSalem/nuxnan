<?php

namespace App\Http\Resources\Learn\Course;

use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use App\Http\Resources\TopicResource;
use App\Http\Resources\CourseResource;
use App\Http\Resources\AssignmentResource;
use App\Http\Resources\LessonCommentResource;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'creater'               => new UserResource($this->user),
            'course_id'             => $this->course_id,
            'course'                => new CourseResource($this->course),
            'title'                 => $this->title,
            'description'           => $this->description,
            'content'               => $this->content,
            'url'                   => $this->lesson_url,
            'youtube_url'           => $this->youtube_url,
            'duration'              => $this->duration,
            'min_read'              => $this->min_read,
            'view_count'            => $this->view_count,
            'like_count'            => $this->like_count,
            'is_liked_by_auth'      => $this->likes()->where('user_id', auth()->id())->exists(),
            'dislike_count'         => $this->dislike_count,
            'is_disliked_by_auth'   => $this->dislikes()->where('user_id', auth()->id())->exists(),
            'comments'              => LessonCommentResource::collection($this->getComments()),
            'comment_count'         => $this->comment_count,
            'share_count'           => $this->share_count,
            'download_count'        => $this->download_count,
            'status'                => $this->status,
            'assigned_groups'       => $this->assigned_groups,
            'point_tuition_fee'     => $this->point_tuition_fee,
            'order'                 => $this->order,
            'images'                => $this->images,
            'topics'                => TopicResource::collection($this->topics),
            'created_at'            => $this->created_at->format('Y-m-d H:i:s'),
            'created_at_for_humans' => $this->created_at->diffForHumans(),
            // 'assignments'   => AssignmentResource::collection($this->assignments),
        ];
    }
}
