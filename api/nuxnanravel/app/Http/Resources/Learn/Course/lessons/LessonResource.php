<?php

namespace App\Http\Resources\Learn\Course\lessons;

use App\Http\Resources\Learn\Course\assignments\AssignmentResource;
use App\Http\Resources\Learn\Course\info\CourseResource;
use App\Http\Resources\Learn\Course\questions\QuestionResource;
use App\Http\Resources\UserResource;
use App\Models\CoursePointCampaign;
use App\Models\CoursePointCampaignClaim;
use App\Services\LessonAccessService;
use Illuminate\Http\Request;
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
        $user = auth()->user();
        $accessStatus = $this->additional['access_status'] ?? null;

        // If not provided (e.g. in collection), calculate it
        if (! $accessStatus) {
            $accessService = $this->additional['access_service'] ?? app(LessonAccessService::class);
            $isCourseAdmin = $this->course->isAdmin($user);
            $accessStatus = $accessService->getAccessStatus($user, $this->resource, $isCourseAdmin);
        }

        $isLocked = $accessStatus['is_locked'] ?? false;

        // Field พื้นฐานที่ทุกคนเห็น (แม้ locked)
        $base = [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'title' => $this->title,
            'description' => $this->description, // คำอธิบายเห็นได้ แม้ locked
            'url' => $this->lesson_url,
            'min_read' => $this->min_read,
            'view_count' => $this->view_count,
            'like_count' => $this->like_count,
            'dislike_count' => $this->dislike_count,
            'comment_count' => $this->comment_count,
            'share_count' => $this->share_count,
            'status' => $this->status,
            'publication_status' => $this->publication_status,
            'access_type' => $this->access_type,
            'require_completion_before_exercises' => (bool) $this->require_completion_before_exercises,
            'point_tuition_fee' => $this->point_tuition_fee,
            'money_tuition_fee' => $this->money_tuition_fee,
            'order' => $this->order,
            'display_order' => $this->display_order ?? null,
            'topics_count' => $this->topics_count ?? ($this->relationLoaded('topics') ? $this->topics->count() : 0),
            'assigned_groups' => $this->assigned_groups,
            'is_liked_by_auth' => $this->likes()->where('user_id', auth()->id())->exists(),
            'is_disliked_by_auth' => $this->dislikes()->where('user_id', auth()->id())->exists(),
            'is_bookmarked_by_auth' => auth()->check() ? $this->isBookmarkedBy(auth()->user()) : false,
            'bookmarks_count' => $this->bookmarks()->count(),
            'images' => LessonImageResource::collection($this->images),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'created_at_for_humans' => $this->created_at->diffForHumans(),
            // Access status จาก service
            'is_locked' => $isLocked,
            'access_status' => $accessStatus['access_status'] ?? 'unknown',
            'price_label' => $accessStatus['price_label'] ?? null,
            'price_points' => $accessStatus['price_points'] ?? null,
            'price_money' => $accessStatus['price_money'] ?? null,
            'reward' => $this->getRewardInfo($request),
        ];

        // ถ้า locked — ไม่ส่ง content/topics/questions/assignments
        if ($isLocked) {
            return array_merge($base, [
                'content' => null,
                'youtube_url' => null,
                'topics' => [],
                'attachments' => [],
                'assignments' => [],
                'questions' => [],
                'comments' => [],
                'auth_progress' => null,
                'creater' => null,
                'course' => null,
            ]);
        }

        // ไม่ locked — ส่งข้อมูลครบ
        return array_merge($base, [
            'creater' => new UserResource($this->user),
            'course' => new CourseResource($this->course),
            'content' => $this->content,
            'youtube_url' => $this->youtube_url,
            'duration' => $this->duration,
            'download_count' => $this->download_count,
            'comments' => LessonCommentResource::collection($this->getComments()),
            'auth_progress' => $this->when(auth()->guard('api')->check(), function () {
                return $this->progress->where('user_id', auth()->guard('api')->id())->first();
            }),
            'topics' => TopicResource::collection($this->topics),
            'attachments' => LessonAttachmentResource::collection($this->attachments),
            'assignments' => AssignmentResource::collection(
                $this->assignments->filter(function ($assignment) {
                    if (auth()->id() === $this->course->user_id) {
                        return true;
                    }
                    if (! $assignment->start_date || $assignment->start_date <= now()) {
                        return true;
                    }

                    return false;
                })
            ),
            'questions' => QuestionResource::collection($this->questions),
        ]);
    }

    protected function getRewardInfo(Request $request): ?array
    {
        $campaign = CoursePointCampaign::where('lesson_id', $this->id)
            ->where('campaign_type', 'lesson_completion')
            ->whereIn('status', ['active', 'paused', 'depleted'])
            ->first();

        if (! $campaign) {
            return null;
        }

        $user = $request->user();
        $claimedByAuth = $user
            ? CoursePointCampaignClaim::where('campaign_id', $campaign->id)
                ->where('user_id', $user->id)->exists()
            : false;

        $remaining = $campaign->max_claims
            ? max(0, $campaign->max_claims - $campaign->total_claimed)
            : null;

        return [
            'enabled' => $campaign->status === 'active',
            'points_per_claim' => $campaign->points_per_claim,
            'max_claims' => $campaign->max_claims,
            'claimed_count' => $campaign->total_claimed,
            'remaining_claims' => $remaining,
            'status' => $campaign->status,
            'claimed_by_auth' => $claimedByAuth,
            'can_claim' => ! $claimedByAuth && $campaign->status === 'active' && ($remaining === null || $remaining > 0),
        ];
    }
}
