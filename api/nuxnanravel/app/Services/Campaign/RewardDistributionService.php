<?php

namespace App\Services\Campaign;

use App\Models\Advert;
use App\Models\CampaignDeliveryEvent;
use App\Models\Course;
use App\Models\CoursePointAccount;
use App\Models\User;
use App\Services\CoursePointAccountService;
use App\Services\PointsService;
use App\Services\RevenueSharePolicyResolver;
use Illuminate\Database\DatabaseManager;

class RewardDistributionService
{
    public function __construct(
        protected RevenueSharePolicyResolver $resolver,
        protected CoursePointAccountService $courseAccounts,
        protected PointsService $points,
        protected DatabaseManager $db,
    ) {}

    public function distribute(CampaignDeliveryEvent $delivery): array
    {
        if ($delivery->status !== CampaignDeliveryEvent::STATUS_COMPLETED || $delivery->fraud_reason !== null) {
            throw new \DomainException('Delivery not eligible for distribution');
        }
        if ($stored = data_get($delivery->metadata, 'reward_distributed_at')) {
            return ['splits' => data_get($delivery->metadata, 'reward_splits'), 'policy_id' => (int) data_get($delivery->metadata, 'policy_id'), 'policy_version' => (int) data_get($delivery->metadata, 'policy_version')];
        }

        return $this->db->transaction(function () use ($delivery) {
            $delivery = CampaignDeliveryEvent::whereKey($delivery->id)->lockForUpdate()->firstOrFail();
            if ($stored = data_get($delivery->metadata, 'reward_distributed_at')) {
                return ['splits' => data_get($delivery->metadata, 'reward_splits'), 'policy_id' => (int) data_get($delivery->metadata, 'policy_id'), 'policy_version' => (int) data_get($delivery->metadata, 'policy_version')];
            }
            $advert = Advert::whereKey($delivery->advert_id)->lockForUpdate()->firstOrFail();
            $gross = (int) $delivery->required_duration * (int) config('campaign.gross_reward_per_view_per_second', 20);
            if ($advert->budget_amount !== null) {
                $gross = min($gross, max(0, (int) $advert->budget_amount));
                $advert->decrement('budget_amount', $gross);
            }
            $courseId = $advert->course_id ? (int) $advert->course_id : null;
            $policy = $this->resolver->resolve((int) $advert->id, $courseId, $advert->academy_id, now());
            $split = $this->resolver->split($gross, $policy);
            $viewer = User::findOrFail($delivery->user_id);
            $this->points->earn($viewer, $split['student'], 'ad_reward', $delivery->id, 'Ad reward', ['delivery_id' => $delivery->id, 'policy_id' => $split['policy_id'], 'policy_version' => $split['policy_version']]);
            $sourceUserId = (int) ($advert->advertiser_id ?? $advert->user_id);
            // Only track course/platform share on a real course. Platform-scoped ads
            // (no course_id) drop the course/platform legs into metadata for reporting
            // rather than trying to firstOrCreate a fake course_id=0 (FK would fail).
            $targetCourseId = $courseId && Course::whereKey($courseId)->exists() ? $courseId : null;
            if ($targetCourseId !== null && $split['course'] > 0) {
                $this->courseAccounts->creditFromAdRevenue($targetCourseId, $sourceUserId, $split['course'], "ad-{$delivery->id}", ['source' => 'ad_revenue', 'delivery_id' => $delivery->id, 'policy_id' => $split['policy_id'], 'policy_version' => $split['policy_version']]);
            }
            if ($targetCourseId !== null && $split['platform'] > 0) {
                $account = CoursePointAccount::whereKey(
                    CoursePointAccount::firstOrCreate(['course_id' => $targetCourseId], ['balance' => 0, 'total_earned' => 0, 'total_withdrawn' => 0, 'total_distributed' => 0, 'reserved_balance' => 0, 'commission_rate' => 0, 'minimum_withdrawal' => CoursePointAccount::MINIMUM_WITHDRAWAL])->id
                )->lockForUpdate()->firstOrFail();
                $account->incrementPlatformEarned($split['platform']);
            }
            $metadata = $delivery->metadata ?? [];
            $metadata += ['reward_distributed_at' => now()->toISOString(), 'reward_splits' => $split, 'policy_id' => $split['policy_id'], 'policy_version' => $split['policy_version']];
            $delivery->update(['metadata' => $metadata]);

            return ['splits' => $split, 'policy_id' => $split['policy_id'], 'policy_version' => $split['policy_version']];
        });
    }
}
