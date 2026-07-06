<?php

namespace App\Observers;

use App\Models\AcademyPost;
use App\Services\Gamification\XpService;

class AcademyPostObserver
{
    public function __construct(protected XpService $xpService) {}

    public function created(AcademyPost $post): void
    {
        $post->loadMissing('academy');
        if (! $post->academy || ! $post->user_id) {
            return;
        }

        $this->xpService->award(
            $post->academy,
            'post.created',
            config('xp_rates.school.post.created', 10),
            $post->user_id,
            $post->posted_as_group_id,
            [
                'post_id' => $post->id,
                'post_type' => $post->post_type ?? 'regular',
            ]
        );
    }
}
