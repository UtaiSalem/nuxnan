<?php

namespace App\Services;

use App\Models\PointsTransaction;
use App\Models\PointRule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PointsService
{
    /**
     * Earn points for a user
     */
    public function earn(User $user, float $amount, string $sourceType, ?int $sourceId = null, ?string $description = null, ?array $metadata = null): PointsTransaction
    {
        return DB::transaction(function () use ($user, $amount, $sourceType, $sourceId, $description, $metadata) {
            $balanceBefore = $user->pp;
            $balanceAfter = $balanceBefore + $amount;

            // Update user points
            $user->update([
                'pp' => $balanceAfter,
                'total_points_earned' => $user->total_points_earned + $amount,
            ]);

            // Create transaction record
            $transaction = PointsTransaction::create([
                'user_id' => $user->id,
                'transaction_type' => 'earn',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description,
                'metadata' => $metadata,
                'status' => 'completed',
            ]);

            // Update daily limits
            $this->updateDailyLimits($user, $amount, 0);

            // Update level
            $this->updateUserLevel($user);

            Log::info('Points earned', [
                'user_id' => $user->id,
                'amount' => $amount,
                'source_type' => $sourceType,
            ]);

            return $transaction;
        });
    }

    /**
     * Spend points for a user
     */
    public function spend(User $user, float $amount, string $sourceType, ?int $sourceId = null, ?string $description = null, ?array $metadata = null): ?PointsTransaction
    {
        return DB::transaction(function () use ($user, $amount, $sourceType, $sourceId, $description, $metadata) {
            $balanceBefore = $user->pp;

            // Check if user has enough points
            if ($balanceBefore < $amount) {
                Log::warning('Insufficient points', [
                    'user_id' => $user->id,
                    'required' => $amount,
                    'available' => $balanceBefore,
                ]);
                return null;
            }

            $balanceAfter = $balanceBefore - $amount;

            // Update user points
            $user->update([
                'pp' => $balanceAfter,
                'total_points_spent' => $user->total_points_spent + $amount,
            ]);

            // Create transaction record
            $transaction = PointsTransaction::create([
                'user_id' => $user->id,
                'transaction_type' => 'spend',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description,
                'metadata' => $metadata,
                'status' => 'completed',
            ]);

            // Update daily limits
            $this->updateDailyLimits($user, 0, $amount);

            Log::info('Points spent', [
                'user_id' => $user->id,
                'amount' => $amount,
                'source_type' => $sourceType,
            ]);

            return $transaction;
        });
    }

    /**
     * Refund points to a user
     */
    public function refund(User $user, float $amount, string $sourceType, ?int $sourceId = null, ?string $description = null, ?array $metadata = null): PointsTransaction
    {
        return DB::transaction(function () use ($user, $amount, $sourceType, $sourceId, $description, $metadata) {
            $balanceBefore = $user->pp;
            $balanceAfter = $balanceBefore + $amount;

            // Update user points
            $user->update([
                'pp' => $balanceAfter,
            ]);

            // Create transaction record
            $transaction = PointsTransaction::create([
                'user_id' => $user->id,
                'transaction_type' => 'refund',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description,
                'metadata' => $metadata,
                'status' => 'completed',
            ]);

            Log::info('Points refunded', [
                'user_id' => $user->id,
                'amount' => $amount,
                'source_type' => $sourceType,
            ]);

            return $transaction;
        });
    }

    /**
     * Transfer points between users
     */
    public function transfer(User $fromUser, User $toUser, float $amount, ?string $message = null): array
    {
        return DB::transaction(function () use ($fromUser, $toUser, $amount, $message) {
            $fromBalanceBefore = $fromUser->pp;

            // Check if sender has enough points
            if ($fromBalanceBefore < $amount) {
                return [
                    'success' => false,
                    'message' => 'แต้มของคุณไม่เพียงพอ',
                ];
            }

            $fromBalanceAfter = $fromBalanceBefore - $amount;
            $toBalanceBefore = $toUser->pp;
            $toBalanceAfter = $toBalanceBefore + $amount;

            // Update sender points
            $fromUser->update([
                'pp' => $fromBalanceAfter,
                'total_points_spent' => $fromUser->total_points_spent + $amount,
            ]);

            // Update receiver points
            $toUser->update([
                'pp' => $toBalanceAfter,
                'total_points_earned' => $toUser->total_points_earned + $amount,
            ]);

            // Create sender transaction
            PointsTransaction::create([
                'user_id' => $fromUser->id,
                'transaction_type' => 'transfer_out',
                'amount' => $amount,
                'balance_before' => $fromBalanceBefore,
                'balance_after' => $fromBalanceAfter,
                'source_type' => 'user_transfer',
                'source_id' => $toUser->id,
                'description' => $message ?? "โอนแต้มให้ {$toUser->name}",
                'metadata' => ['to_user_id' => $toUser->id, 'to_user_name' => $toUser->name],
                'status' => 'completed',
            ]);

            // Create receiver transaction
            PointsTransaction::create([
                'user_id' => $toUser->id,
                'transaction_type' => 'transfer_in',
                'amount' => $amount,
                'balance_before' => $toBalanceBefore,
                'balance_after' => $toBalanceAfter,
                'source_type' => 'user_transfer',
                'source_id' => $fromUser->id,
                'description' => "รับแต้มจาก {$fromUser->name}",
                'metadata' => ['from_user_id' => $fromUser->id, 'from_user_name' => $fromUser->name],
                'status' => 'completed',
            ]);

            Log::info('Points transferred', [
                'from_user_id' => $fromUser->id,
                'to_user_id' => $toUser->id,
                'amount' => $amount,
            ]);

            return [
                'success' => true,
                'message' => 'โอนแต้มสำเร็จ',
            ];
        });
    }

    /**
     * Admin adjust points
     */
    public function adminAdjust(User $user, float $amount, string $actionType, ?string $reason = null): PointsTransaction
    {
        return DB::transaction(function () use ($user, $amount, $actionType, $reason) {
            $balanceBefore = $user->pp;

            if ($actionType === 'add') {
                $balanceAfter = $balanceBefore + $amount;
                $transactionType = 'admin_adjust';
                $user->update([
                    'pp' => $balanceAfter,
                    'total_points_earned' => $user->total_points_earned + $amount,
                ]);
            } elseif ($actionType === 'deduct') {
                $balanceAfter = $balanceBefore - $amount;
                $transactionType = 'admin_adjust';
                $user->update([
                    'pp' => $balanceAfter,
                    'total_points_spent' => $user->total_points_spent + $amount,
                ]);
            } elseif ($actionType === 'set') {
                $balanceAfter = $amount;
                $transactionType = 'admin_adjust';
                $user->update([
                    'pp' => $balanceAfter,
                ]);
            } else {
                throw new \InvalidArgumentException('Invalid action type');
            }

            // Create transaction record
            $transaction = PointsTransaction::create([
                'user_id' => $user->id,
                'transaction_type' => $transactionType,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'source_type' => 'admin_adjust',
                'description' => $reason ?? "การปรับแต้มจาก Admin",
                'metadata' => ['admin_action' => $actionType],
                'status' => 'completed',
            ]);

            // Update level
            $this->updateUserLevel($user);

            Log::info('Points adjusted by admin', [
                'user_id' => $user->id,
                'action' => $actionType,
                'amount' => $amount,
                'reason' => $reason,
            ]);

            return $transaction;
        });
    }

    /**
     * Update user level based on total points
     */
    protected function updateUserLevel(User $user): void
    {
        $totalPoints = $user->pp;

        // Calculate level: Level = floor((Points / 100) ^ (2/3))
        $level = floor(pow($totalPoints / 100, 2/3));

        // Calculate XP for next level: XP for Next Level = 100 × (Level + 1)^1.5
        $xpForNextLevel = 100 * pow($level + 1, 1.5);

        // Calculate current XP: Current XP = Points - Total XP for Current Level
        $totalXpForCurrentLevel = 0;
        for ($i = 1; $i < $level; $i++) {
            $totalXpForCurrentLevel += 100 * pow($i, 1.5);
        }
        $currentXp = $totalPoints - $totalXpForCurrentLevel;

        $user->update([
            'level' => $level,
            'xp_for_next_level' => $xpForNextLevel,
            'current_xp' => $currentXp,
        ]);
    }

    /**
     * Update daily point limits
     */
    protected function updateDailyLimits(User $user, float $pointsEarned, float $pointsSpent): void
    {
        $today = now()->toDateString();

        $dailyLimit = $user->dailyPointLimits()->where('date', $today)->first();

        if (!$dailyLimit) {
            $dailyLimit = $user->dailyPointLimits()->create([
                'date' => $today,
                'points_earned' => $pointsEarned,
                'points_spent' => $pointsSpent,
            ]);
        } else {
            $dailyLimit->update([
                'points_earned' => $dailyLimit->points_earned + $pointsEarned,
                'points_spent' => $dailyLimit->points_spent + $pointsSpent,
            ]);
        }
    }

    /**
     * Get point rule by key
     */
    public function getRule(string $ruleKey): ?PointRule
    {
        return PointRule::where('rule_key', $ruleKey)->active()->first();
    }

    /**
     * Check if user can earn points from a rule
     */
    public function canEarnFromRule(User $user, PointRule $rule): bool
    {
        if (!$rule->isActiveNow()) {
            return false;
        }

        // Check daily limits
        $today = now()->toDateString();
        $dailyLimit = $user->dailyPointLimits()->where('date', $today)->first();

        if ($dailyLimit && $rule->max_daily_earnings) {
            if ($dailyLimit->points_earned >= $rule->max_daily_earnings) {
                return false;
            }
        }

        // Check monthly limits
        if ($rule->max_monthly_earnings) {
            $thisMonth = now()->startOfMonth();
            $monthlyEarned = PointsTransaction::where('user_id', $user->id)
                ->where('source_type', $rule->source_type)
                ->where('transaction_type', 'earn')
                ->where('created_at', '>=', $thisMonth)
                ->sum('amount');

            if ($monthlyEarned >= $rule->max_monthly_earnings) {
                return false;
            }
        }

        // Check cooldown
        if ($rule->cooldown_minutes) {
            $lastTransaction = PointsTransaction::where('user_id', $user->id)
                ->where('source_type', $rule->source_type)
                ->where('transaction_type', 'earn')
                ->latest()
                ->first();

            if ($lastTransaction) {
                $minutesSinceLast = now()->diffInMinutes($lastTransaction->created_at);
                if ($minutesSinceLast < $rule->cooldown_minutes) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get user balance
     */
    public function getBalance(User $user): array
    {
        return [
            'current_points' => $user->pp,
            'total_earned' => $user->total_points_earned,
            'total_spent' => $user->total_points_spent,
            'level' => $user->level,
            'current_xp' => $user->current_xp,
            'xp_for_next_level' => $user->xp_for_next_level,
            'progress_percentage' => $user->xp_for_next_level > 0 
                ? round(($user->current_xp / $user->xp_for_next_level) * 100, 2)
                : 100,
        ];
    }

    /**
     * Convert points to wallet - handles Points side, delegates Wallet side to WalletService
     */
    public function convertPointsToWallet(User $user, int $points): array
    {
        return DB::transaction(function () use ($user, $points) {
            $exchangeRate = 1200; // 1 THB = 1200 points
            $walletAmount = $points / $exchangeRate;

            $pointsBalanceBefore = $user->pp;

            // Check if user has enough points
            if ($pointsBalanceBefore < $points) {
                return [
                    'success' => false,
                    'message' => 'แต้มของคุณไม่เพียงพอ',
                ];
            }

            $pointsBalanceAfter = $pointsBalanceBefore - $points;

            // Update user points
            $user->update([
                'pp' => $pointsBalanceAfter,
                'total_points_spent' => $user->total_points_spent + $points,
            ]);

            // Create points transaction (PointsService responsibility)
            $pointsTransaction = PointsTransaction::create([
                'user_id' => $user->id,
                'transaction_type' => 'conversion',
                'amount' => $points,
                'balance_before' => $pointsBalanceBefore,
                'balance_after' => $pointsBalanceAfter,
                'source_type' => 'points_to_wallet',
                'description' => "แปลง {$points} แต้มเป็น " . number_format($walletAmount, 2) . " บาท",
                'metadata' => [
                    'exchange_rate' => $exchangeRate,
                    'conversion_type' => 'points_to_money',
                    'wallet_amount' => $walletAmount,
                ],
                'status' => 'completed',
            ]);

            // Delegate wallet addition to WalletService
            $walletService = new \App\Services\WalletService();
            $walletResult = $walletService->addFromPointsConversion($user, $walletAmount, $points, $exchangeRate);

            Log::info('Points converted to wallet', [
                'user_id' => $user->id,
                'points' => $points,
                'wallet_amount' => $walletAmount,
            ]);

            return [
                'success' => true,
                'points_converted' => $points,
                'wallet_amount' => $walletAmount,
                'new_points_balance' => $pointsBalanceAfter,
                'new_wallet_balance' => $walletResult['new_balance'],
                'points_transaction_id' => $pointsTransaction->id,
                'wallet_transaction_id' => $walletResult['transaction_id'],
            ];
        });
    }

    /**
     * Add points from wallet conversion (called by WalletService)
     */
    public function addFromWalletConversion(User $user, int $points, float $walletAmount, int $exchangeRate): array
    {
        $pointsBalanceBefore = $user->pp;
        $pointsBalanceAfter = $pointsBalanceBefore + $points;

        // Update user points
        $user->update([
            'pp' => $pointsBalanceAfter,
            'total_points_earned' => $user->total_points_earned + $points,
        ]);

        // Create points transaction
        $pointsTransaction = PointsTransaction::create([
            'user_id' => $user->id,
            'transaction_type' => 'conversion',
            'amount' => $points,
            'balance_before' => $pointsBalanceBefore,
            'balance_after' => $pointsBalanceAfter,
            'source_type' => 'wallet_to_points',
            'description' => "รับจากการแปลง " . number_format($walletAmount, 2) . " บาท",
            'metadata' => [
                'exchange_rate' => $exchangeRate,
                'conversion_type' => 'money_to_points',
                'wallet_amount' => $walletAmount,
            ],
            'status' => 'completed',
        ]);

        return [
            'new_balance' => $pointsBalanceAfter,
            'transaction_id' => $pointsTransaction->id,
        ];
    }
}
