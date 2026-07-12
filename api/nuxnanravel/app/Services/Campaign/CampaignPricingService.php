<?php

namespace App\Services\Campaign;

use InvalidArgumentException;

class CampaignPricingService
{
    public function advertisement(int $views, int $duration): float
    {
        if ($views < 1 || $duration < 1) {
            throw new InvalidArgumentException('จำนวนวิวและระยะเวลาต้องมากกว่าศูนย์');
        }

        return round($views * $duration * (float) config('campaign.ad_price_per_view_second'), 2);
    }

    public function pointsRate(string $scope): int
    {
        return (int) config('campaign.points_rate.'.($scope === 'public' ? 'platform' : $scope), 1080);
    }
}
