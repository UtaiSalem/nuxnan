<?php

namespace App\Services\Campaign;

use App\Enums\CampaignReviewStatus;
use App\Models\Advert;
use App\Models\CampaignDeliveryEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AdDeliveryService
{
    public function __construct(protected RewardDistributionService $rewards) {}

    public function startSession(Advert $advert, User $user, string $sessionId, ?string $deviceFingerprint, ?string $ipHash, ?string $userAgent): array
    {
        $key = 'ad-delivery-start:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            abort(429);
        }
        RateLimiter::hit($key, 60);

        return DB::transaction(function () use ($advert, $user, $sessionId, $deviceFingerprint, $ipHash, $userAgent) {
            $advert = Advert::whereKey($advert->id)->lockForUpdate()->first();
            if (! $advert || $advert->review_status?->value !== CampaignReviewStatus::APPROVED->value || $advert->remaining_views <= 0) {
                throw (new ModelNotFoundException)->setModel(Advert::class, [$advert?->id]);
            }
            $today = CampaignDeliveryEvent::where('user_id', $user->id)->whereIn('status', [CampaignDeliveryEvent::STATUS_STARTED, CampaignDeliveryEvent::STATUS_COMPLETED])->whereDate('started_at', today())->count();
            if ($today >= (int) config('campaign.daily_views_per_user', 5)) {
                abort(429, 'daily view limit reached');
            }
            $delivery = CampaignDeliveryEvent::create([
                'advert_id' => $advert->id, 'user_id' => $user->id, 'event_type' => 'rewarded_view',
                'session_id' => $sessionId, 'started_at' => now(), 'required_duration' => $advert->duration,
                'device_fingerprint_hash' => $deviceFingerprint ? hash('sha256', $deviceFingerprint) : null,
                'ip_hash' => $ipHash, 'user_agent' => $userAgent, 'status' => CampaignDeliveryEvent::STATUS_STARTED,
            ]);
            $token = hash_hmac('sha256', $sessionId.'|'.$delivery->id, config('app.key'));
            $delivery->update(['delivery_token_hash' => hash('sha256', $token)]);

            return ['token' => $token, 'deliveryId' => $delivery->id, 'required_duration' => $delivery->required_duration];
        });
    }

    private function verify(CampaignDeliveryEvent $delivery, string $token): void
    {
        if ($delivery->status !== CampaignDeliveryEvent::STATUS_STARTED || ! hash_equals((string) $delivery->delivery_token_hash, hash('sha256', $token))) {
            abort(403);
        }
    }

    public function heartbeat(CampaignDeliveryEvent $delivery, string $token, float $visibilityRatio): void
    {
        $key = 'ad-delivery-hb:'.$delivery->id;
        if (RateLimiter::tooManyAttempts($key, 20)) {
            abort(429);
        }
        RateLimiter::hit($key, 60);
        $this->verify($delivery, $token);
        $old = $delivery->page_visibility_ratio;
        $delivery->update(['last_heartbeat_at' => now(), 'page_visibility_ratio' => $old === null ? $visibilityRatio : ($old + $visibilityRatio) / 2]);
    }

    public function complete(CampaignDeliveryEvent $delivery, string $token): array
    {
        if (in_array($delivery->status, [CampaignDeliveryEvent::STATUS_COMPLETED, CampaignDeliveryEvent::STATUS_REPLAYED], true)) {
            $delivery->update(['status' => CampaignDeliveryEvent::STATUS_REPLAYED]);
            throw new HttpException(409, 'replayed');
        }

        return DB::transaction(function () use ($delivery, $token) {
            $delivery = CampaignDeliveryEvent::whereKey($delivery->id)->lockForUpdate()->firstOrFail();
            if ($delivery->status !== CampaignDeliveryEvent::STATUS_STARTED) {
                $this->verify($delivery, $token);
            }
            $this->verify($delivery, $token);
            $watched = $delivery->last_heartbeat_at && $delivery->started_at
                ? abs((int) $delivery->started_at->diffInSeconds($delivery->last_heartbeat_at, false))
                : 0;
            if ($watched < ($delivery->required_duration ?? 0) - 2) {
                $delivery->update(['status' => CampaignDeliveryEvent::STATUS_INSUFFICIENT_WATCH, 'fraud_reason' => 'below_required_duration', 'completed_at' => now()]);

                return ['delivery' => $delivery->fresh(), 'valid' => false, 'reason' => 'below_required_duration'];
            }
            if ((float) ($delivery->page_visibility_ratio ?? 0) < 0.7) {
                $delivery->update(['status' => CampaignDeliveryEvent::STATUS_INSUFFICIENT_VISIBILITY, 'fraud_reason' => 'low_visibility', 'completed_at' => now()]);

                return ['delivery' => $delivery->fresh(), 'valid' => false, 'reason' => 'low_visibility'];
            }
            $advert = Advert::whereKey($delivery->advert_id)->lockForUpdate()->firstOrFail();
            if ($advert->remaining_views > 0) {
                $advert->increment('impressions_count');
            }
            if ($advert->remaining_views > 0) {
                $advert->decrement('remaining_views');
            }
            $delivery->update(['status' => CampaignDeliveryEvent::STATUS_COMPLETED, 'completed_at' => now()]);

            $result = ['delivery' => $delivery->fresh(), 'valid' => true, 'reason' => null];
            $result['reward'] = $this->rewards->distribute($result['delivery']);

            return $result;
        });
    }
}
