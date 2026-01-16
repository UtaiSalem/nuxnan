<?php

namespace App\Services;

use App\Models\Reward;
use App\Models\UserReward;
use App\Models\User;
use App\Services\PointsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RewardService
{
    protected PointsService $pointsService;

    public function __construct(PointsService $pointsService)
    {
        $this->pointsService = $pointsService;
    }

    /**
     * Get all available rewards.
     */
    public function getAllRewards(): array
    {
        return Reward::active()->available()->get()->toArray();
    }

    /**
     * Get rewards by type.
     */
    public function getRewardsByType(string $type): array
    {
        return Reward::active()->type($type)->available()->get()->toArray();
    }

    /**
     * Get reward details.
     */
    public function getRewardDetails(int $rewardId): ?array
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
            'value' => $reward->value,
            'points_cost' => $reward->points_cost,
            'image_url' => $reward->image_url,
            'stock' => $reward->stock,
            'max_redemptions_per_user' => $reward->max_redemptions_per_user,
            'is_active' => $reward->is_active,
            'available_from' => $reward->available_from ? $reward->available_from->format('Y-m-d H:i:s') : null,
            'available_until' => $reward->available_until ? $reward->available_until->format('Y-m-d H:i:s') : null,
            'is_available' => $reward->isAvailable(),
            'formatted_points_cost' => $reward->formatted_points_cost,
            'formatted_value' => $reward->formatted_value,
        ];
    }

    /**
     * Get user rewards.
     */
    public function getUserRewards(User $user): array
    {
        $userRewards = UserReward::with('reward')
            ->where('user_id', $user->id)
            ->get()
            ->map(function ($userReward) {
                return [
                    'id' => $userReward->id,
                    'reward_id' => $userReward->reward_id,
                    'reward_name' => $userReward->reward->name ?? '',
                    'reward_description' => $userReward->reward->description ?? '',
                    'reward_type' => $userReward->reward->type ?? '',
                    'reward_value' => $userReward->reward->value ?? 0,
                    'reward_image_url' => $userReward->reward->image_url ?? '',
                    'points_spent' => $userReward->points_spent,
                    'status' => $userReward->status,
                    'redeemed_at' => $userReward->redeemed_at ? $userReward->redeemed_at->format('Y-m-d H:i:s') : null,
                    'expires_at' => $userReward->expires_at ? $userReward->expires_at->format('Y-m-d H:i:s') : null,
                    'is_expired' => $userReward->isExpired(),
                    'formatted_points_spent' => $userReward->formatted_points_spent,
                    'formatted_redeemed_at' => $userReward->formatted_redeemed_at,
                    'formatted_expires_at' => $userReward->formatted_expires_at,
                ];
            })
            ->toArray();

        return $userRewards;
    }

    /**
     * Redeem reward.
     */
    public function redeemReward(User $user, int $rewardId, int $quantity = 1): array
    {
        return DB::transaction(function () use ($user, $rewardId, $quantity) {
            $reward = Reward::find($rewardId);

            if (!$reward) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบรางวัล',
                ];
            }

            if (!$reward->isAvailable()) {
                return [
                    'success' => false,
                    'message' => 'รางวัลนี้ไม่สามารถแลกได้',
                ];
            }

            // Check stock
            if ($reward->stock !== null && $reward->stock < $quantity) {
                return [
                    'success' => false,
                    'message' => 'สต็อกของรางวัลไม่เพียงพอ',
                ];
            }

            // Check max redemptions per user
            if ($reward->max_redemptions_per_user) {
                $userRedemptions = UserReward::where('user_id', $user->id)
                    ->where('reward_id', $rewardId)
                    ->where('status', '!=', 'cancelled')
                    ->count();

                if ($userRedemptions >= $reward->max_redemptions_per_user) {
                    return [
                        'success' => false,
                        'message' => 'คุณแลกรางวัลนี้ครบแล้ว',
                    ];
                }
            }

            // Check if user has enough points
            $totalPointsCost = $reward->points_cost * $quantity;
            if ($user->pp < $totalPointsCost) {
                return [
                    'success' => false,
                    'message' => 'แต้มของคุณไม่เพียงพอ',
                ];
            }

            // Spend points
            $spendResult = $this->pointsService->spend(
                $user,
                $totalPointsCost,
                'reward_redeem',
                $rewardId,
                "แลกซื้อ: {$reward->name}",
                [
                    'reward_name' => $reward->name,
                    'quantity' => $quantity,
                ]
            );

            if (!$spendResult) {
                return [
                    'success' => false,
                    'message' => 'ไม่สามารถใช้แต้ม',
                ];
            }

            // Update stock
            if ($reward->stock !== null) {
                $reward->stock -= $quantity;
                $reward->save();
            }

            // Create user reward record
            $userReward = UserReward::create([
                'user_id' => $user->id,
                'reward_id' => $rewardId,
                'points_spent' => $totalPointsCost,
                'status' => 'claimed',
                'redeemed_at' => now(),
                'expires_at' => $reward->available_until ?? null,
                'metadata' => [
                    'reward_name' => $reward->name,
                    'quantity' => $quantity,
                    'reward_type' => $reward->type,
                    'reward_value' => $reward->value,
                ],
            ]);

            Log::info('Reward redeemed', [
                'user_id' => $user->id,
                'reward_id' => $rewardId,
                'quantity' => $quantity,
                'points_spent' => $totalPointsCost,
            ]);

            return [
                'success' => true,
                'message' => 'แลกซื้อสำเร็จ',
                'data' => [
                    'user_reward_id' => $userReward->id,
                    'reward_id' => $rewardId,
                    'reward_name' => $reward->name,
                    'quantity' => $quantity,
                    'points_spent' => $totalPointsCost,
                    'new_points_balance' => $user->pp,
                    'redeemed_at' => $userReward->redeemed_at->format('Y-m-d H:i:s'),
                    'expires_at' => $userReward->expires_at ? $userReward->expires_at->format('Y-m-d H:i:s') : null,
                ],
            ];
        });
    }

    /**
     * Cancel user reward.
     */
    public function cancelUserReward(User $user, int $userRewardId): bool
    {
        return DB::transaction(function () use ($user, $userRewardId) {
            $userReward = UserReward::where('user_id', $user->id)
                ->where('id', $userRewardId)
                ->first();

            if (!$userReward) {
                return false;
            }

            if ($userReward->status !== 'pending' && $userReward->status !== 'claimed') {
                return false;
            }

            // Refund points
            $refundResult = $this->pointsService->refund(
                $user,
                $userReward->points_spent,
                'reward_cancel',
                $userReward->reward_id,
                "ยกเลิกรางวัล: {$userReward->reward->name}",
                [
                    'user_reward_id' => $userRewardId,
                ]
            );

            // Update user reward status
            $userReward->status = 'cancelled';
            $userReward->save();

            // Update stock
            if ($userReward->reward) {
                $reward = $userReward->reward;
                if ($reward->stock !== null) {
                    $reward->stock += 1;
                    $reward->save();
                }
            }

            Log::info('User reward cancelled', [
                'user_id' => $user->id,
                'user_reward_id' => $userRewardId,
                'reward_id' => $userReward->reward_id,
                'points_refunded' => $userReward->points_spent,
            ]);

            return true;
        });
    }

    /**
     * Get reward statistics.
     */
    public function getRewardStats(): array
    {
        $totalRewards = Reward::active()->count();
        $totalRedemptions = UserReward::claimed()->count();
        $totalPointsSpent = UserReward::claimed()->sum('points_spent');

        return [
            'total_rewards' => $totalRewards,
            'total_redemptions' => $totalRedemptions,
            'total_points_spent' => $totalPointsSpent,
            'average_points_per_redemption' => $totalRedemptions > 0 
                ? round($totalPointsSpent / $totalRedemptions, 2)
                : 0,
        ];
    }

    /**
     * Create default rewards.
     */
    public function createDefaultRewards(): void
    {
        $defaultRewards = [
            // Wallet Rewards
            [
                'name' => 'บัตรกำนัล 100 บาท',
                'description' => 'แลกแต้มเป็นบัตรกำนัลมูลค่า 100 บาท',
                'type' => 'wallet',
                'value' => 100.00,
                'points_cost' => 120000,
                'image_url' => '/rewards/gift-card-100.jpg',
                'stock' => 100,
                'max_redemptions_per_user' => 5,
            ],
            [
                'name' => 'บัตรกำนัล 500 บาท',
                'description' => 'แลกแต้มเป็นบัตรกำนัลมูลค่า 500 บาท',
                'type' => 'wallet',
                'value' => 500.00,
                'points_cost' => 600000,
                'image_url' => '/rewards/gift-card-500.jpg',
                'stock' => 50,
                'max_redemptions_per_user' => 3,
            ],
            [
                'name' => 'บัตรกำนัล 1000 บาท',
                'description' => 'แลกแต้มเป็นบัตรกำนัลมูลค่า 1000 บาท',
                'type' => 'wallet',
                'value' => 1000.00,
                'points_cost' => 1200000,
                'image_url' => '/rewards/gift-card-1000.jpg',
                'stock' => 20,
                'max_redemptions_per_user' => 1,
            ],

            // Badge Rewards
            [
                'name' => 'Badge พิเศษ: Rising Star',
                'description' => 'Badge พิเศษสำหรับผู้ที่สะสม 1,000 แต้ม',
                'type' => 'badge',
                'value' => 'Rising Star Badge',
                'points_cost' => 5000,
                'image_url' => '/badges/rising-star.png',
                'stock' => null,
                'max_redemptions_per_user' => 1,
            ],
            [
                'name' => 'Badge พิเศษ: Like Magnet',
                'description' => 'Badge พิเศษสำหรับผู้ที่ได้รับ 100 ไลค์',
                'type' => 'badge',
                'value' => 'Like Magnet Badge',
                'points_cost' => 10000,
                'image_url' => '/badges/like-magnet.png',
                'stock' => null,
                'max_redemptions_per_user' => 1,
            ],
            [
                'name' => 'Badge พิเศษ: Comment King',
                'description' => 'Badge พิเศษสำหรับผู้ที่คอมเมนต์ 50 ครั้ง',
                'type' => 'badge',
                'value' => 'Comment King Badge',
                'points_cost' => 15000,
                'image_url' => '/badges/comment-king.png',
                'stock' => null,
                'max_redemptions_per_user' => 1,
            ],
            [
                'name' => 'Badge พิเศษ: Social Butterfly',
                'description' => 'Badge พิเศษสำหรับผู้ที่โพสต์ 10 ครั้ง',
                'type' => 'badge',
                'value' => 'Social Butterfly Badge',
                'points_cost' => 20000,
                'image_url' => '/badges/social-butterfly.png',
                'stock' => null,
                'max_redemptions_per_user' => 1,
            ],
            [
                'name' => 'Badge พิเศษ: Dedicated',
                'description' => 'Badge พิเศษสำหรับผู้ที่เข้า 7 วันต่อเนื่อง',
                'type' => 'badge',
                'value' => 'Dedicated Badge',
                'points_cost' => 30000,
                'image_url' => '/badges/dedicated.png',
                'stock' => null,
                'max_redemptions_per_user' => 1,
            ],
            [
                'name' => 'Badge พิเศษ: Loyal',
                'description' => 'Badge พิเศษสำหรับผู้ที่เข้า 30 วันต่อเนื่อง',
                'type' => 'badge',
                'value' => 'Loyal Badge',
                'points_cost' => 50000,
                'image_url' => '/badges/loyal.png',
                'stock' => null,
                'max_redemptions_per_user' => 1,
            ],

            // Feature Rewards
            [
                'name' => 'ปลดล็อกธีมพิเศษ',
                'description' => 'ธีมพิเศษสำหรับโปรไฟล์ของคุณ',
                'type' => 'feature',
                'value' => 'Special Theme',
                'points_cost' => 50000,
                'image_url' => '/rewards/special-theme.jpg',
                'stock' => null,
                'max_redemptions_per_user' => 1,
            ],
            [
                'name' => 'เพิ่มพื้นที่จัดเก็บ 1 GB',
                'description' => 'เพิ่มพื้นที่จัดเก็บอีก 1 GB',
                'type' => 'feature',
                'value' => '+1 GB Storage',
                'points_cost' => 10000,
                'image_url' => '/rewards/storage-1gb.jpg',
                'stock' => null,
                'max_redemptions_per_user' => 10,
            ],
            [
                'name' => 'เปลี่ยนชื่อผู้ใช้',
                'description' => 'เปลี่ยนชื่อผู้ใช้ของคุณ',
                'type' => 'feature',
                'value' => 'Username Change',
                'points_cost' => 5000,
                'image_url' => '/rewards/username-change.jpg',
                'stock' => null,
                'max_redemptions_per_user' => 3,
            ],
            [
                'name' => 'เปลี่ยนรูปโปรไฟล์พิเศษ',
                'description' => 'เปลี่ยนรูปโปรไฟล์พิเศษของคุณ',
                'type' => 'feature',
                'value' => 'Special Avatar',
                'points_cost' => 20000,
                'image_url' => '/rewards/special-avatar.jpg',
                'stock' => null,
                'max_redemptions_per_user' => 1,
            ],
        ];

        foreach ($defaultRewards as $rewardData) {
            Reward::firstOrCreate(
                ['name' => $rewardData['name']],
                $rewardData
            );
        }

        Log::info('Default rewards created', [
            'count' => count($defaultRewards),
        ]);
    }
}
