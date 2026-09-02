<?php

namespace App\Services;

use App\Models\LevelDefinition;
use App\Models\PointRule;
use App\Models\PointsTransaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PointsService
{
    /**
     * Earn points for a user
     */
    public function earn(User $user, float $amount, string $sourceType, ?int $sourceId = null, ?string $description = null, ?array $metadata = null, int $xpAmount = 0, ?string $idempotencyKey = null): PointsTransaction
    {
        return DB::transaction(function () use ($user, $amount, $sourceType, $sourceId, $description, $metadata, $xpAmount, $idempotencyKey) {
            $balanceBefore = $user->pp;
            $balanceAfter = $balanceBefore + $amount;

            // Update user points
            $user->update([
                'pp' => $balanceAfter,
                'total_points_earned' => $user->total_points_earned + $amount,
            ]);

            // Add XP if provided
            if ($xpAmount > 0) {
                $user->increment('xp', $xpAmount);
                $user->refresh(); // Ensure we have the latest XP for level calculation
            }

            // Create transaction record
            $transaction = PointsTransaction::create([
                'user_id' => $user->id,
                'transaction_type' => 'earn',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'idempotency_key' => $idempotencyKey,
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
                'xp_amount' => $xpAmount,
                'source_type' => $sourceType,
            ]);

            return $transaction;
        });
    }

    /**
     * Award PP through rule limits and a database-backed idempotency key.
     */
    public function awardGoverned(User $user, float $amount, string $ruleKey, string $idempotencyKey, ?int $sourceId = null, ?string $description = null, ?array $metadata = null): ?PointsTransaction
    {
        if ($amount <= 0 || PointsTransaction::where('idempotency_key', $idempotencyKey)->exists()) {
            return null;
        }

        $rule = $this->getRule($ruleKey);
        if ($rule && ! $this->canEarnFromRule($user, $rule)) {
            Log::info('Governed PP award blocked by rule limit', [
                'user_id' => $user->id,
                'rule_key' => $ruleKey,
                'amount' => $amount,
                'idempotency_key' => $idempotencyKey,
            ]);

            return null;
        }

        try {
            return $this->earn(
                $user,
                $amount,
                $rule?->source_type ?? $ruleKey,
                $sourceId,
                $description ?? $rule?->rule_name,
                $metadata,
                0,
                $idempotencyKey
            );
        } catch (QueryException $exception) {
            if (PointsTransaction::where('idempotency_key', $idempotencyKey)->exists()) {
                return null;
            }

            throw $exception;
        }
    }

    /**
     * Add XP to a user and update their level
     */
    public function addXp(User $user, int $amount): void
    {
        DB::transaction(function () use ($user, $amount) {
            $user->increment('xp', $amount);
            $user->refresh();
            $this->updateUserLevel($user);
        });
    }

    /**
     * Spend points for a user
     */
    public function spend(User $user, float $amount, string $sourceType, ?int $sourceId = null, ?string $description = null, ?array $metadata = null, ?string $idempotencyKey = null): ?PointsTransaction
    {
        return DB::transaction(function () use ($user, $amount, $sourceType, $sourceId, $description, $metadata, $idempotencyKey) {
            if ($idempotencyKey && PointsTransaction::where('idempotency_key', $idempotencyKey)->exists()) {
                return PointsTransaction::where('idempotency_key', $idempotencyKey)->first();
            }

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
                'idempotency_key' => $idempotencyKey,
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
                'description' => $reason ?? 'การปรับแต้มจาก Admin',
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
     * Award points to a user based on a rule key
     */
    public function awardByRule(User $user, string $ruleKey, ?int $sourceId = null, ?string $description = null, ?array $metadata = null): ?PointsTransaction
    {
        $rule = $this->getRule($ruleKey);

        if (! $rule) {
            Log::warning("Point rule not found: {$ruleKey}");

            return null;
        }

        if (! $this->canEarnFromRule($user, $rule)) {
            return null;
        }

        return $this->earn(
            $user,
            $rule->calculateAmount(),
            $rule->source_type ?? $ruleKey,
            $sourceId,
            $description ?? $rule->rule_name,
            $metadata,
            $rule->xp_amount ?? 0
        );
    }

    /**
     * Update user level based on total points
     */
    protected function updateUserLevel(User $user): void
    {
        // A freshly created user may have a null xp; treat it as 0 so the
        // comparison below doesn't fail with "Illegal operator and value combination".
        $xp = (int) ($user->xp ?? 0);

        // Find the highest level where xp_required <= current xp
        $levelDef = LevelDefinition::where('xp_required', '<=', $xp)
            ->orderByDesc('level')
            ->first();

        $level = $levelDef ? $levelDef->level : 1;

        // Find next level for XP info
        $nextLevelDef = LevelDefinition::where('level', $level + 1)->first();
        $xpForNextLevel = $nextLevelDef ? $nextLevelDef->xp_required : $xp;

        $currentLevelXp = $levelDef ? $levelDef->xp_required : 0;
        $progressXp = $xp - $currentLevelXp;
        $neededXpForNext = $nextLevelDef ? ($nextLevelDef->xp_required - $currentLevelXp) : 0;

        $user->update([
            'level' => $level,
            'xp_level' => $level,
            'xp_for_next_level' => $neededXpForNext,
            'current_xp' => $progressXp,
        ]);
    }

    /**
     * Update daily point limits
     */
    protected function updateDailyLimits(User $user, float $pointsEarned, float $pointsSpent): void
    {
        $today = now()->toDateString();

        $dailyLimit = $user->dailyPointLimits()->where('date', $today)->first();

        if (! $dailyLimit) {
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
     * Check if user can earn points from a rule at a given moment.
     *
     * $at คือเวลาที่ event เกิดจริง (UserUsageEvent::occurred_at) ไม่ใช่เวลาที่ประมวลผล
     * เพราะ job อาจถูกประมวลผลช้ากว่าเวลาที่ event เกิดหลายเดือน
     * ไม่ส่งมา = ตัดสินด้วยเวลาปัจจุบัน (พฤติกรรมเดิม)
     */
    public function canEarnFromRule(User $user, PointRule $rule, ?CarbonInterface $at = null): bool
    {
        // ต้องเป็น immutable — ไม่งั้น startOfDay()/startOfMonth() จะไปกลายพันธุ์ $at
        // แล้วการเช็ค cooldown ข้างล่างจะใช้เวลาที่ผิด
        $at = $at ? CarbonImmutable::parse($at) : CarbonImmutable::now();

        if (! $rule->isActiveAt($at)) {
            return false;
        }

        // Check daily limits (scoped to this rule's source, mirroring the
        // monthly check below — the aggregate dailyPointLimits.points_earned
        // would otherwise let PP earned from unrelated sources block this rule).
        if ($rule->max_daily_earnings) {
            $dailyEarned = PointsTransaction::where('user_id', $user->id)
                ->where('source_type', $rule->source_type)
                ->where('transaction_type', 'earn')
                ->whereBetween('created_at', [$at->startOfDay(), $at->endOfDay()])
                ->sum('amount');

            if ($dailyEarned >= $rule->max_daily_earnings) {
                return false;
            }
        }

        // Check monthly limits
        if ($rule->max_monthly_earnings) {
            $monthlyEarned = PointsTransaction::where('user_id', $user->id)
                ->where('source_type', $rule->source_type)
                ->where('transaction_type', 'earn')
                ->whereBetween('created_at', [$at->startOfMonth(), $at->endOfMonth()])
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
                ->where('created_at', '<=', $at)
                ->latest('created_at')
                ->first();

            if ($lastTransaction) {
                // Carbon 3 คืนค่ามีเครื่องหมาย ต้องนับจากรายการเก่า -> $at ให้ได้ค่าบวก
                $minutesSinceLast = $lastTransaction->created_at->diffInMinutes($at);
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
            'total_points' => $user->pp,
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
                'description' => "แปลง {$points} แต้มเป็น ".number_format($walletAmount, 2).' บาท',
                'metadata' => [
                    'exchange_rate' => $exchangeRate,
                    'conversion_type' => 'points_to_money',
                    'wallet_amount' => $walletAmount,
                ],
                'status' => 'completed',
            ]);

            // Delegate wallet addition to WalletService
            $walletService = new WalletService;
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
            'description' => 'รับจากการแปลง '.number_format($walletAmount, 2).' บาท',
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
