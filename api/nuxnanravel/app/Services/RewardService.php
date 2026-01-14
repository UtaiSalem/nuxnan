<?php

namespace App\Services;

use App\Models\Reward;
use App\Models\UserReward;
use App\Models\User;
use App\Services\PointsService;
use Illuminate\Support\Facades\DB;

class RewardService
{
    protected PointsService $pointsService;

    public function __construct(PointsService $pointsService)
    {
        $this->pointsService = $pointsService;
    }

    /**
     * Get available rewards.
     */
    public function getAvailableRewards(): array
    {
        $rewards = Reward::active()->available()->get();

        $result = [];

        foreach ($rewards as $reward) {
            $result[] = [
                'id' => $reward->id,
                'name' => $reward->name,
                'description' => $reward->description,
                'type' => $reward->type,
                'type_label' => $reward->type_label,
                'value' => $reward->value,
                'formatted_value' => $reward->formatted_value,
                'points_cost' => $reward->points_cost,
                'formatted_points_cost' => $reward->formatted_points_cost,
                'image_url' => $reward->image_url,
                'stock' => $reward->stock,
                'max_redemptions_per_user' => $reward->max_redemptions_per_user,
                'can_redeem' => $reward->isAvailable(),
            ];
        }

        return $result;
    }

    /**
     * Get user rewards.
     */
    public function getUserRewards(int $userId): array
    {
        $userRewards = UserReward::where('user_id', $userId)
            ->with('reward')
            ->orderBy('created_at', 'desc')
            ->get();

        $result = [];

        foreach ($userRewards as $userReward) {
            $reward = $userReward->reward;

            $result[] = [
                'id' => $userReward->id,
                'reward_id' => $reward->id,
                'reward_name' => $reward->name,
                'reward_description' => $reward->description,
                'reward_type' => $reward->type,
                'reward_value' => $reward->value,
                'formatted_value' => $reward->formatted_value,
                'points_spent' => $userReward->points_spent,
                'formatted_points_spent' => $userReward->formatted_points_spent,
                'status' => $userReward->status,
                'status_label' => $userReward->status_label,
                'redeemed_at' => $userReward->redeemed_at ? $userReward->redeemed_at->toIso8601String() : null,
                'expires_at' => $userReward->expires_at ? $userReward->expires_at->toIso8601String() : null,
                'is_expired' => $userReward->isExpired(),
                'is_claimed' => $userReward->isClaimed(),
                'image_url' => $reward->image_url,
            ];
        }

        return $result;
    }

    /**
     * Redeem a reward.
     */
    public function redeemReward(int $userId, int $rewardId): array
    {
        return DB::transaction(function () use ($userId, $rewardId) {
            $user = User::find($userId);
            $reward = Reward::find($rewardId);

            if (!$user || !$reward) {
                throw new \Exception('User or reward not found');
            }

            // Check if reward is available
            if (!$reward->isAvailable()) {
                throw new \Exception('Reward is not available');
            }

            // Check if user can redeem this reward
            if (!$reward->canRedeem($userId)) {
                throw new \Exception('You cannot redeem this reward');
            }

            // Check if user has enough points
            $balance = $this->pointsService->getBalance($userId);
            $availablePoints = $balance['available_points'] ?? 0;

            if ($availablePoints < $reward->points_cost) {
                throw new \Exception('Insufficient points balance');
            }

            // Check stock
            if ($reward->stock <= 0) {
                throw new \Exception('Reward is out of stock');
            }

            // Spend points
            $this->pointsService->spend($userId, [
                'source_type' => 'reward',
                'source_id' => $rewardId,
                'amount' => $reward->points_cost,
                'description' => "แลกราง: {$reward->name}",
                'metadata' => [
                    'reward_id' => $rewardId,
                    'reward_name' => $reward->name,
                ],
            ]);

            // Create user reward record
            $userReward = UserReward::create([
                'user_id' => $userId,
                'reward_id' => $rewardId,
                'points_spent' => $reward->points_cost,
                'status' => 'pending',
                'redeemed_at' => now(),
                'expires_at' => $reward->available_until,
            ]);

            // Update reward stock
            if ($reward->stock > 0) {
                $reward->decrement('stock');
            }

            return [
                'user_reward_id' => $userReward->id,
                'reward_id' => $rewardId,
                'points_spent' => $reward->points_cost,
                'status' => 'pending',
                'redeemed_at' => $userReward->redeemed_at->toIso8601String(),
                'expires_at' => $userReward->expires_at ? $userReward->expires_at->toIso8601String() : null,
                'message' => 'Reward redeemed successfully',
            ];
        });
    }

    /**
     * Claim a reward.
     */
    public function claimReward(int $userId, int $userRewardId): bool
    {
        return DB::transaction(function () use ($userId, $userRewardId) {
            $userReward = UserReward::where('id', $userRewardId)
                ->where('user_id', $userId)
                ->first();

            if (!$userReward) {
                throw new \Exception('User reward not found');
            }

            if ($userReward->isExpired()) {
                throw new \Exception('Reward has expired');
            }

            if ($userReward->isClaimed()) {
                throw new \Exception('Reward already claimed');
            }

            return $userReward->markAsClaimed();
        });
    }

    /**
     * Get reward by ID.
     */
    public function getRewardById(int $rewardId): ?array
    {
        $reward = Reward::find($rewardId);

        if (!$reward) {
            return null;
        }

        return [
            'id' => $reward->id,
            'name' => $reward->name,
            'description' => $reward->description,
            'type' => $reward->type,
            'type_label' => $reward->type_label,
            'value' => $reward->value,
            'formatted_value' => $reward->formatted_value,
            'points_cost' => $reward->points_cost,
            'formatted_points_cost' => $reward->formatted_points_cost,
            'image_url' => $reward->image_url,
            'stock' => $reward->stock,
            'max_redemptions_per_user' => $reward->max_redemptions_per_user,
            'is_available' => $reward->isAvailable(),
            'available_from' => $reward->available_from ? $reward->available_from->toIso8601String() : null,
            'available_until' => $reward->available_until ? $reward->available_until->toIso8601String() : null,
        ];
    }

    /**
     * Get user redemptions count for a reward.
     */
    public function getUserRedemptionsCount(int $userId, int $rewardId): int
    {
        return UserReward::where('user_id', $userId)
            ->where('reward_id', $rewardId)
            ->count();
    }

    /**
     * Get reward statistics.
     */
    public function getRewardStats(int $userId): array
    {
        $totalRedemptions = UserReward::where('user_id', $userId)->count();
        $pendingRedemptions = UserReward::where('user_id', $userId)
            ->where('status', 'pending')
            ->count();
        $claimedRedemptions = UserReward::where('user_id', $userId)
            ->where('status', 'claimed')
            ->count();
        $expiredRedemptions = UserReward::where('user_id', $userId)
            ->where('status', 'expired')
            ->count();
        $cancelledRedemptions = UserReward::where('user_id', $userId)
            ->where('status', 'cancelled')
            ->count();

        $totalPointsSpent = UserReward::where('user_id', $userId)
            ->sum('points_spent');

        return [
            'total_redemptions' => $totalRedemptions,
            'pending_redemptions' => $pendingRedemptions,
            'claimed_redemptions' => $claimedRedemptions,
            'expired_redemptions' => $expiredRedemptions,
            'cancelled_redemptions' => $cancelledRedemptions,
            'total_points_spent' => $totalPointsSpent,
            'formatted_total_points_spent' => number_format($totalPointsSpent) . ' แต้ม',
        ];
    }

    /**
     * Create a new reward (Admin only).
     */
    public function createReward(array $data): array
    {
        $reward = Reward::create([
            'name' => $data['name'],
            'description' => $data['description'],
            'type' => $data['type'],
            'value' => $data['value'] ?? null,
            'points_cost' => $data['points_cost'],
            'image_url' => $data['image_url'] ?? null,
            'stock' => $data['stock'] ?? 0,
            'max_redemptions_per_user' => $data['max_redemptions_per_user'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
            'available_from' => $data['available_from'] ?? null,
            'available_until' => $data['available_until'] ?? null,
        ]);

        return [
            'id' => $reward->id,
            'message' => 'Reward created successfully',
        ];
    }

    /**
     * Update a reward (Admin only).
     */
    public function updateReward(int $rewardId, array $data): bool
    {
        $reward = Reward::find($rewardId);

        if (!$reward) {
            throw new \Exception('Reward not found');
        }

        return $reward->update([
            'name' => $data['name'] ?? $reward->name,
            'description' => $data['description'] ?? $reward->description,
            'type' => $data['type'] ?? $reward->type,
            'value' => $data['value'] ?? $reward->value,
            'points_cost' => $data['points_cost'] ?? $reward->points_cost,
            'image_url' => $data['image_url'] ?? $reward->image_url,
            'stock' => $data['stock'] ?? $reward->stock,
            'max_redemptions_per_user' => $data['max_redemptions_per_user'] ?? $reward->max_redemptions_per_user,
            'is_active' => $data['is_active'] ?? $reward->is_active,
            'available_from' => $data['available_from'] ?? $reward->available_from,
            'available_until' => $data['available_until'] ?? $reward->available_until,
        ]);
    }

    /**
     * Delete a reward (Admin only).
     */
    public function deleteReward(int $rewardId): bool
    {
        $reward = Reward::find($rewardId);

        if (!$reward) {
            throw new \Exception('Reward not found');
        }

        return $reward->delete();
    }
}
