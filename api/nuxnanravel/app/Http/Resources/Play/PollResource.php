<?php

namespace App\Http\Resources\Play;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;
use App\Models\PollVote;

class PollResource extends JsonResource
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
            'author' => new UserResource($this->user),
            'question' => $this->title,
            'description' => $this->description,
            'starts_at' => $this->start_date,
            'ends_at' => $this->end_date,
            'is_ended' => $this->end_date ? now()->isAfter($this->end_date) : false,
            'is_active' => $this->is_active,
            'is_public' => $this->is_public,
            'max_votes' => $this->max_votes,
            'is_multiple' => $this->is_multiple_choice ?? false,
            'total_votes' => $this->total_votes ?? $this->options->sum('votes') ?? 0,
            'image_url' => $this->image_url,
            'points_pool' => $this->points_pool,
            'points_per_vote' => $this->points_per_vote,
            'points_distributed' => $this->points_distributed,
            'options' => $this->options->map(function ($option) {
                $totalVotes = $this->total_votes ?? $this->options->sum('votes') ?? 0;
                $optionVotes = $option->votes ?? 0;
                
                $isUserVote = false;
                if (auth()->check()) {
                    $isUserVote = PollVote::where('poll_option_id', $option->id)
                        ->where('user_id', auth()->id())
                        ->exists();
                }

                return [
                    'id' => $option->id,
                    'text' => $option->text,
                    'votes' => $optionVotes,
                    'percentage' => $totalVotes > 0 ? round(($optionVotes / $totalVotes) * 100) : 0,
                    'position' => $option->position,
                    'is_user_vote' => $isUserVote,
                ];
            }),
            'user_voted' => auth()->check() ? PollVote::where('poll_id', $this->id)->where('user_id', auth()->id())->exists() : false,
            'user_votes' => auth()->check() ? PollVote::where('poll_id', $this->id)->where('user_id', auth()->id())->pluck('poll_option_id') : [],
            'time_remaining' => $this->calculateTimeRemaining(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'diff_humans_created_at' => $this->created_at ? $this->created_at->diffForHumans() : null,
            'reaction_counts' => [
                'likes' => $this->likes()->count(),
                'dislikes' => $this->dislikes()->count(),
                'comments' => $this->comments()->count(),
            ],
            'user_reactions' => [
                'is_liked' => auth()->check() ? $this->likes()->where('user_id', auth()->id())->exists() : false,
                'is_disliked' => auth()->check() ? $this->dislikes()->where('user_id', auth()->id())->exists() : false,
            ],
            'comments' => PollCommentResource::collection($this->whenLoaded('comments')),
        ];
    }
    
    /**
     * Calculate time remaining for the poll.
     */
    protected function calculateTimeRemaining(): ?string
    {
        if (!$this->end_date) {
            return null;
        }
        
        $now = now();
        $endDate = $this->end_date;
        
        if ($now->isAfter($endDate)) {
            return null;
        }
        
        $diff = $now->diff($endDate);
        
        if ($diff->d > 0) {
            return $diff->d . ' วัน';
        } elseif ($diff->h > 0) {
            return $diff->h . ' ชั่วโมง';
        } elseif ($diff->i > 0) {
            return $diff->i . ' นาที';
        } else {
            return 'ไม่กี่วินาที';
        }
    }
}
