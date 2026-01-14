<?php

namespace App\Services;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Reward;
use App\Models\UserReward;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AdminWalletService
{
    /**
     * Get comprehensive wallet statistics
     */
    public function getStats(array $filters = []): array
    {
        $cacheKey = 'admin_wallet_stats_' . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, 300, function () use ($filters) {
            $startDate = $filters['start_date'] ?? now()->subDays(30);
            $endDate = $filters['end_date'] ?? now();

            // Basic stats
            $stats = [
                'total_balance' => User::sum('wallet'),
                'total_users' => User::count(),
                'active_users' => User::where('wallet', '>', 0)->count(),
            ];

            // Transaction stats
            $stats['transactions'] = [
                'total' => WalletTransaction::count(),
                'pending' => WalletTransaction::where('status', 'pending')->count(),
                'completed' => WalletTransaction::where('status', 'completed')->count(),
                'failed' => WalletTransaction::where('status', 'failed')->count(),
                'cancelled' => WalletTransaction::where('status', 'cancelled')->count(),
            ];

            // Today's stats
            $today = now()->toDateString();
            $stats['today'] = [
                'deposits' => WalletTransaction::whereDate('created_at', $today)
                    ->where('transaction_type', 'deposit')
                    ->sum('amount'),
                'withdrawals' => WalletTransaction::whereDate('created_at', $today)
                    ->where('transaction_type', 'withdraw')
                    ->sum('amount'),
                'conversions' => WalletTransaction::whereDate('created_at', $today)
                    ->where('transaction_type', 'conversion')
                    ->sum('amount'),
                'transfers' => WalletTransaction::whereDate('created_at', $today)
                    ->where('transaction_type', 'transfer')
                    ->sum('amount'),
            ];

            // Pending withdrawals
            $stats['pending_withdrawals'] = [
                'count' => WalletTransaction::where('status', 'pending')
                    ->where('transaction_type', 'withdraw')
                    ->count(),
                'total_amount' => WalletTransaction::where('status', 'pending')
                    ->where('transaction_type', 'withdraw')
                    ->sum('amount'),
            ];

            return $stats;
        });
    }

    /**
     * Get daily wallet trend
     */
    public function getDailyTrend(int $days = 30): array
    {
        return WalletTransaction::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(CASE WHEN transaction_type = "deposit" THEN amount ELSE 0 END) as deposited'),
            DB::raw('SUM(CASE WHEN transaction_type = "withdraw" THEN amount ELSE 0 END) as withdrawn'),
            DB::raw('SUM(CASE WHEN transaction_type = "conversion" THEN amount ELSE 0 END) as converted'),
            DB::raw('SUM(CASE WHEN transaction_type = "transfer" THEN amount ELSE 0 END) as transferred'),
            DB::raw('COUNT(*) as total_transactions'),
            DB::raw('COUNT(DISTINCT user_id) as unique_users')
        )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Get transaction type breakdown
     */
    public function getTransactionTypeBreakdown(int $days = 30): array
    {
        return WalletTransaction::select(
            'transaction_type',
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(amount) as total_amount'),
            DB::raw('AVG(amount) as average_amount')
        )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('transaction_type')
            ->get()
            ->toArray();
    }

    /**
     * Get status breakdown
     */
    public function getStatusBreakdown(int $days = 30): array
    {
        return WalletTransaction::select(
            'status',
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(amount) as total_amount')
        )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('status')
            ->get()
            ->toArray();
    }

    /**
     * Get top depositors
     */
    public function getTopDepositors(int $limit = 10): array
    {
        return User::orderByDesc('wallet')
            ->limit($limit)
            ->get(['id', 'name', 'avatar', 'wallet', 'created_at'])
            ->toArray();
    }

    /**
     * Get top withdrawers
     */
    public function getTopWithdrawers(int $days = 30, int $limit = 10): array
    {
        return WalletTransaction::select(
            'user_id',
            DB::raw('SUM(amount) as total_withdrawn'),
            DB::raw('COUNT(*) as withdrawal_count'),
            DB::raw('AVG(amount) as average_withdrawal')
        )
            ->where('transaction_type', 'withdraw')
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays($days))
            ->with('user:id,name,avatar')
            ->groupBy('user_id')
            ->orderByDesc('total_withdrawn')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get reward redemption stats
     */
    public function getRewardRedemptions(int $days = 30, int $limit = 10): array
    {
        return Reward::select(
            'rewards.id',
            'rewards.name',
            'rewards.type',
            'rewards.points_cost',
            DB::raw('COUNT(user_rewards.id) as redemption_count'),
            DB::raw('SUM(user_rewards.points_spent) as total_points_spent'),
            DB::raw('SUM(CASE WHEN user_rewards.status = "claimed" THEN 1 ELSE 0 END) as claimed_count')
        )
            ->join('user_rewards', 'rewards.id', '=', 'user_rewards.reward_id')
            ->where('user_rewards.redeemed_at', '>=', now()->subDays($days))
            ->groupBy('rewards.id', 'rewards.name', 'rewards.type', 'rewards.points_cost')
            ->orderByDesc('redemption_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get user wallet history
     */
    public function getUserWalletHistory(int $userId, int $limit = 50): array
    {
        return WalletTransaction::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Bulk adjust wallet balances
     */
    public function bulkAdjustWallet(array $userIds, string $action, float $amount, string $reason): array
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        DB::beginTransaction();

        try {
            foreach ($userIds as $userId) {
                try {
                    $user = User::findOrFail($userId);
                    $balanceBefore = $user->wallet;

                    switch ($action) {
                        case 'add':
                            $user->wallet += $amount;
                            $transactionAmount = $amount;
                            break;
                        case 'deduct':
                            $user->wallet = max(0, $user->wallet - $amount);
                            $transactionAmount = -$amount;
                            break;
                        case 'set':
                            $difference = $amount - $user->wallet;
                            $user->wallet = $amount;
                            $transactionAmount = $difference;
                            break;
                        default:
                            throw new \Exception('Invalid action');
                    }

                    $balanceAfter = $user->wallet;
                    $user->save();

                    // Record transaction
                    WalletTransaction::create([
                        'user_id' => $user->id,
                        'transaction_type' => 'admin_adjust',
                        'amount' => abs($transactionAmount),
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                        'currency' => 'THB',
                        'description' => $reason,
                        'metadata' => json_encode([
                            'admin_id' => auth()->id(),
                            'action' => $action,
                            'original_amount' => $amount,
                            'bulk_operation' => true
                        ]),
                        'status' => 'completed',
                    ]);

                    $results[] = [
                        'user_id' => $userId,
                        'success' => true,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                    ];
                    $successCount++;
                } catch (\Exception $e) {
                    $results[] = [
                        'user_id' => $userId,
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                    $failureCount++;
                }
            }

            DB::commit();

            return [
                'success' => true,
                'results' => $results,
                'summary' => [
                    'total' => count($userIds),
                    'success' => $successCount,
                    'failure' => $failureCount,
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get wallet analytics report
     */
    public function getAnalyticsReport(array $filters): array
    {
        $startDate = $filters['start_date'] ?? now()->subDays(30);
        $endDate = $filters['end_date'] ?? now();

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'days' => $startDate->diffInDays($endDate),
            ],
            'daily_trend' => $this->getDailyTrend($startDate->diffInDays($endDate)),
            'transaction_types' => $this->getTransactionTypeBreakdown($startDate->diffInDays($endDate)),
            'status_breakdown' => $this->getStatusBreakdown($startDate->diffInDays($endDate)),
            'top_depositors' => $this->getTopDepositors(10),
            'top_withdrawers' => $this->getTopWithdrawers($startDate->diffInDays($endDate), 10),
            'reward_redemptions' => $this->getRewardRedemptions($startDate->diffInDays($endDate), 10),
            'user_activity' => $this->getUserActivityStats($startDate, $endDate),
        ];
    }

    /**
     * Get user activity stats
     */
    protected function getUserActivityStats($startDate, $endDate): array
    {
        $activeUsers = WalletTransaction::whereBetween('created_at', [$startDate, $endDate])
            ->distinct('user_id')
            ->count('user_id');

        $avgBalancePerUser = $activeUsers > 0
            ? WalletTransaction::whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount') / $activeUsers
            : 0;

        return [
            'active_users' => $activeUsers,
            'avg_balance_per_user' => round($avgBalancePerUser, 2),
        ];
    }

    /**
     * Export wallet transactions to CSV
     */
    public function exportTransactions(array $filters): string
    {
        $query = WalletTransaction::with(['user:id,name,email']);

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        if (isset($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        $csv = "ID,User,Email,Type,Amount,Balance Before,Balance After,Currency,Description,Status,Reference,Created At\n";

        foreach ($transactions as $transaction) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $transaction->id,
                $transaction->user->name ?? 'N/A',
                $transaction->user->email ?? 'N/A',
                $transaction->transaction_type,
                $transaction->amount,
                $transaction->balance_before,
                $transaction->balance_after,
                $transaction->currency,
                str_replace(',', ' ', $transaction->description ?? ''),
                $transaction->status,
                $transaction->reference_number ?? 'N/A',
                $transaction->created_at
            );
        }

        return $csv;
    }

    /**
     * Get pending withdrawals summary
     */
    public function getPendingWithdrawalsSummary(): array
    {
        $withdrawals = WalletTransaction::with(['user:id,name,email,avatar'])
            ->where('transaction_type', 'withdraw')
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        return [
            'count' => $withdrawals->count(),
            'total_amount' => $withdrawals->sum('amount'),
            'withdrawals' => $withdrawals->toArray(),
        ];
    }

    /**
     * Process all pending withdrawals
     */
    public function processPendingWithdrawals(string $action, string $reason): array
    {
        $withdrawals = WalletTransaction::where('transaction_type', 'withdraw')
            ->where('status', 'pending')
            ->get();

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        DB::beginTransaction();

        try {
            foreach ($withdrawals as $withdrawal) {
                try {
                    if ($action === 'approve') {
                        $withdrawal->status = 'completed';
                        $withdrawal->metadata = array_merge(
                            json_decode($withdrawal->metadata ?? '{}', true),
                            [
                                'admin_id' => auth()->id(),
                                'admin_notes' => $reason,
                                'processed_at' => now()->toIso8601String(),
                            ]
                        );
                        $withdrawal->save();
                    } elseif ($action === 'reject') {
                        $withdrawal->status = 'cancelled';
                        $withdrawal->metadata = array_merge(
                            json_decode($withdrawal->metadata ?? '{}', true),
                            [
                                'admin_id' => auth()->id(),
                                'rejection_reason' => $reason,
                                'rejected_at' => now()->toIso8601String(),
                            ]
                        );
                        $withdrawal->save();

                        // Refund the amount back to user's wallet
                        $user = User::findOrFail($withdrawal->user_id);
                        $user->wallet += $withdrawal->amount;
                        $user->save();

                        // Create refund transaction
                        WalletTransaction::create([
                            'user_id' => $user->id,
                            'transaction_type' => 'admin_adjust',
                            'amount' => $withdrawal->amount,
                            'balance_before' => $user->wallet - $withdrawal->amount,
                            'balance_after' => $user->wallet,
                            'currency' => 'THB',
                            'description' => 'Refund from rejected withdrawal',
                            'metadata' => json_encode([
                                'admin_id' => auth()->id(),
                                'original_withdrawal_id' => $withdrawal->id,
                                'rejection_reason' => $reason,
                            ]),
                            'status' => 'completed',
                        ]);
                    }

                    $results[] = [
                        'withdrawal_id' => $withdrawal->id,
                        'success' => true,
                        'user_id' => $withdrawal->user_id,
                    ];
                    $successCount++;
                } catch (\Exception $e) {
                    $results[] = [
                        'withdrawal_id' => $withdrawal->id,
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                    $failureCount++;
                }
            }

            DB::commit();

            return [
                'success' => true,
                'results' => $results,
                'summary' => [
                    'total' => $withdrawals->count(),
                    'success' => $successCount,
                    'failure' => $failureCount,
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
