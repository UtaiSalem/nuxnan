<?php

namespace App\Services;

use App\Models\User;
use App\Models\PointsTransaction;
use App\Models\PointRule;
use App\Models\DailyPointLimit;
use App\Models\Achievement;
use App\Models\UserAchievement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AdminPointsService
{
    /**
     * Get comprehensive points statistics
     */
    public function getStats(array $filters = []): array
    {
        $cacheKey = 'admin_points_stats_' . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, 300, function () use ($filters) {
            $startDate = $filters['start_date'] ?? now()->subDays(30);
            $endDate = $filters['end_date'] ?? now();

            // Basic stats
            $stats = [
                'total_points' => User::sum('pp'),
                'total_points_earned' => User::sum('total_points_earned'),
                'total_points_spent' => User::sum('total_points_spent'),
                'net_points' => User::sum('total_points_earned') - User::sum('total_points_spent'),
                'total_users' => User::count(),
                'active_users' => User::where('pp', '>', 0)->count(),
            ];

            // Today's stats
            $today = now()->toDateString();
            $stats['today'] = [
                'earned' => DailyPointLimit::where('date', $today)->sum('points_earned'),
                'spent' => DailyPointLimit::where('date', $today)->sum('points_spent'),
                'transactions' => PointsTransaction::whereDate('created_at', $today)->count(),
            ];

            // Transaction stats
            $stats['transactions'] = [
                'total' => PointsTransaction::count(),
                'pending' => PointsTransaction::where('status', 'pending')->count(),
                'completed' => PointsTransaction::where('status', 'completed')->count(),
                'failed' => PointsTransaction::where('status', 'failed')->count(),
            ];

            // Level distribution
            $stats['level_distribution'] = User::select(
                'level',
                DB::raw('COUNT(*) as count')
            )
                ->groupBy('level')
                ->orderBy('level')
                ->get()
                ->pluck('count', 'level')
                ->toArray();

            return $stats;
        });
    }

    /**
     * Get daily points trend
     */
    public function getDailyTrend(int $days = 30): array
    {
        return PointsTransaction::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(CASE WHEN transaction_type = "earn" THEN amount ELSE 0 END) as earned'),
            DB::raw('SUM(CASE WHEN transaction_type = "spend" THEN amount ELSE 0 END) as spent'),
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
     * Get points distribution by source type
     */
    public function getSourceDistribution(int $days = 30): array
    {
        return PointsTransaction::select(
            'source_type',
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(amount) as total_amount'),
            DB::raw('AVG(amount) as average_amount')
        )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('source_type')
            ->orderByDesc('total_amount')
            ->get()
            ->toArray();
    }

    /**
     * Get top points earners
     */
    public function getTopEarners(int $limit = 10): array
    {
        return User::orderByDesc('total_points_earned')
            ->limit($limit)
            ->get(['id', 'name', 'avatar', 'pp', 'total_points_earned', 'level', 'created_at'])
            ->toArray();
    }

    /**
     * Get top points spenders
     */
    public function getTopSpenders(int $limit = 10): array
    {
        return User::orderByDesc('total_points_spent')
            ->limit($limit)
            ->get(['id', 'name', 'avatar', 'pp', 'total_points_spent', 'level', 'created_at'])
            ->toArray();
    }

    /**
     * Get user points history
     */
    public function getUserPointsHistory(int $userId, int $limit = 50): array
    {
        return PointsTransaction::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Bulk adjust points for multiple users
     */
    public function bulkAdjustPoints(array $userIds, string $action, float $amount, string $reason): array
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        DB::beginTransaction();

        try {
            foreach ($userIds as $userId) {
                try {
                    $user = User::findOrFail($userId);
                    $balanceBefore = $user->pp;

                    switch ($action) {
                        case 'add':
                            $user->pp += $amount;
                            $user->total_points_earned += $amount;
                            $transactionAmount = $amount;
                            break;
                        case 'deduct':
                            $user->pp = max(0, $user->pp - $amount);
                            $user->total_points_spent += $amount;
                            $transactionAmount = -$amount;
                            break;
                        case 'set':
                            $difference = $amount - $user->pp;
                            $user->pp = $amount;
                            if ($difference > 0) {
                                $user->total_points_earned += $difference;
                            } else {
                                $user->total_points_spent += abs($difference);
                            }
                            $transactionAmount = $difference;
                            break;
                        default:
                            throw new \Exception('Invalid action');
                    }

                    $balanceAfter = $user->pp;
                    $user->save();

                    // Record transaction
                    PointsTransaction::create([
                        'user_id' => $user->id,
                        'transaction_type' => 'admin_adjust',
                        'amount' => abs($transactionAmount),
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                        'source_type' => 'admin_bulk',
                        'source_id' => auth()->id(),
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
     * Get points analytics report
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
            'source_distribution' => $this->getSourceDistribution($startDate->diffInDays($endDate)),
            'top_earners' => $this->getTopEarners(10),
            'top_spenders' => $this->getTopSpenders(10),
            'transaction_types' => $this->getTransactionTypeBreakdown($startDate, $endDate),
            'user_activity' => $this->getUserActivityStats($startDate, $endDate),
        ];
    }

    /**
     * Get transaction type breakdown
     */
    protected function getTransactionTypeBreakdown($startDate, $endDate): array
    {
        return PointsTransaction::select(
            'transaction_type',
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(amount) as total_amount'),
            DB::raw('AVG(amount) as average_amount')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('transaction_type')
            ->get()
            ->toArray();
    }

    /**
     * Get user activity stats
     */
    protected function getUserActivityStats($startDate, $endDate): array
    {
        $activeUsers = PointsTransaction::whereBetween('created_at', [$startDate, $endDate])
            ->distinct('user_id')
            ->count('user_id');

        $avgPointsPerUser = $activeUsers > 0
            ? PointsTransaction::whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount') / $activeUsers
            : 0;

        return [
            'active_users' => $activeUsers,
            'avg_points_per_user' => round($avgPointsPerUser, 2),
        ];
    }

    /**
     * Export points transactions to CSV
     */
    public function exportTransactions(array $filters): string
    {
        $query = PointsTransaction::with(['user:id,name,email']);

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

        $transactions = $query->orderBy('created_at', 'desc')->get();

        $csv = "ID,User,Email,Type,Amount,Balance Before,Balance After,Source,Description,Status,Created At\n";

        foreach ($transactions as $transaction) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $transaction->id,
                $transaction->user->name ?? 'N/A',
                $transaction->user->email ?? 'N/A',
                $transaction->transaction_type,
                $transaction->amount,
                $transaction->balance_before,
                $transaction->balance_after,
                $transaction->source_type,
                str_replace(',', ' ', $transaction->description ?? ''),
                $transaction->status,
                $transaction->created_at
            );
        }

        return $csv;
    }
}
