<?php

namespace App\Console\Commands;

use App\Models\CoursePointCampaign;
use App\Services\CoursePointAccountService;
use Illuminate\Console\Command;

class CleanupCoursePointReservations extends Command
{
    protected $signature = 'course-points:cleanup-reservations';

    protected $description = 'Close expired campaigns and release their reserved course points.';

    public function handle(CoursePointAccountService $service): int
    {
        $closed = 0;
        $released = 0;

        CoursePointCampaign::whereIn('status', [CoursePointCampaign::STATUS_ACTIVE, CoursePointCampaign::STATUS_PAUSED])
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->each(function (CoursePointCampaign $campaign) use ($service, &$closed, &$released): void {
                $remaining = $campaign->max_claims
                    ? max(0, ($campaign->max_claims - $campaign->total_claimed) * $campaign->points_per_claim)
                    : 0;
                $service->cancelCampaign($campaign->id);
                $closed++;
                $released += $remaining;
            });

        $this->info("Closed {$closed} expired campaigns, released {$released} points to available balance.");

        return self::SUCCESS;
    }
}
