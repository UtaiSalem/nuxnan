<?php

namespace App\Observers;

use App\Models\AcademyPostLike;
use App\Services\Gamification\XpService;

class AcademyPostLikeObserver
{
    public function __construct(protected XpService $xpService) {}

    public function created(AcademyPostLike $like): void
    {
        $post = $like->post;
        if (!$post) {
            return;
        }

        $post->loadMissing('academy');
        if (!$post->academy || !$post->user_id) {
            return;
        }

        // Award XP to the author of the post
        $this->xpService->award(
            $post->academy,
            'post.like_received',
            config('xp_rates.school.post.like_received', 1),
            $post->user_id,
            $post->posted_as_group_id,
            [
                'post_id'  => $post->id,
                'liker_id' => $like->user_id,
            ]
        );
    }
}
