<?php

namespace App\Observers;

use App\Models\AcademyPostComment;
use App\Services\Gamification\XpService;

class AcademyPostCommentObserver
{
    public function __construct(protected XpService $xpService) {}

    public function created(AcademyPostComment $comment): void
    {
        $post = $comment->post;
        if (! $post) {
            return;
        }

        $post->loadMissing('academy');
        if (! $post->academy || ! $post->user_id) {
            return;
        }

        // Award XP to the author of the post for receiving a comment
        $this->xpService->award(
            $post->academy,
            'post.comment_received',
            config('xp_rates.school.post.comment_received', 2),
            $post->user_id,
            $post->posted_as_group_id,
            [
                'post_id' => $post->id,
                'comment_id' => $comment->id,
                'commenter_id' => $comment->user_id,
            ]
        );
    }
}
