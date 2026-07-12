<?php

namespace App\Services\Campaign;

use App\Exceptions\DailyViewLimitException;
use App\Models\Advert;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CampaignViewService
{
    public function __construct(private DatabaseManager $db) {}

    public function impression(Advert $campaign, ?User $viewer, ?string $placement = null, ?string $ipHash = null, ?string $userAgent = null, ?string $idempotencyKey = null): void
    {
        $event = [
            'user_id' => $viewer?->id,
            'event_type' => 'impression',
            'ip_hash' => $ipHash,
            'user_agent' => $userAgent,
            'placement' => $placement,
            'idempotency_key' => $idempotencyKey,
        ];

        if ($idempotencyKey && $campaign->deliveryEvents()->where('user_id', $viewer?->id)->where('idempotency_key', $idempotencyKey)->exists()) {
            return;
        }

        $campaign->deliveryEvents()->create($event);
        $campaign->increment('impressions_count');
    }

    public function rewardedView(Advert $campaign, User $viewer, string $idempotencyKey): Advert
    {
        return $this->db->transaction(function () use ($campaign, $viewer, $idempotencyKey) {
            $locked = Advert::whereKey($campaign->id)->lockForUpdate()->first();
            if (! $locked || $locked->review_status?->value !== 'approved' || $locked->remaining_views < 1) {
                throw (new ModelNotFoundException)->setModel(Advert::class, [$campaign->id]);
            }

            if ($locked->advertViewers()->where('user_id', $viewer->id)->where('idempotency_key', $idempotencyKey)->exists()) {
                return $locked;
            }

            $dailyViews = $locked->advertViewers()->where('user_id', $viewer->id)->whereDate('created_at', today())->count();
            if ($dailyViews >= (int) config('campaign.daily_views_per_user', 5)) {
                throw new DailyViewLimitException;
            }

            $locked->decrement('remaining_views');
            $locked->advertViewers()->create(['user_id' => $viewer->id, 'idempotency_key' => $idempotencyKey]);
            $locked->deliveryEvents()->create([
                'user_id' => $viewer->id,
                'event_type' => 'rewarded_view',
                'idempotency_key' => $idempotencyKey,
            ]);

            $pointsRequired = $locked->duration * 20;
            $viewer = User::whereKey($viewer->id)->lockForUpdate()->firstOrFail();
            if ($viewer->pp < $pointsRequired) {
                throw new \RuntimeException('แต้มสะสมไม่เพียงพอ');
            }
            $viewer->decrement('pp', $pointsRequired);

            // Viewer reward = base per-second rate + a points-derived bonus (config-driven).
            $pointsPerBaht = (float) config('campaign.points_per_reward_baht', 1200);
            $viewerReward = $locked->duration * (float) config('campaign.viewer_reward_per_second')
                + ($pointsPerBaht > 0 ? $pointsRequired / $pointsPerBaht : 0);
            $viewer->increment('wallet', $viewerReward);

            // Reward the referrer who introduced the viewer (falls back to the platform account).
            $this->rewardReferrer($viewer, $locked->duration);

            return $locked->refresh();
        });
    }

    private function rewardReferrer(User $viewer, int $duration): void
    {
        $referrerReward = $duration * (float) config('campaign.referrer_reward_per_second');
        if ($referrerReward <= 0) {
            return;
        }

        $platformCode = config('campaign.platform_account_code', 99999999);
        $referrerCode = $viewer->suggester_code ?: $platformCode;
        $referrer = User::where('personal_code', $referrerCode)->first()
            ?? User::where('personal_code', $platformCode)->first();

        if ($referrer && $referrer->id !== $viewer->id) {
            $referrer->increment('wallet', $referrerReward);
        }
    }
}
