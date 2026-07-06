<?php

namespace App\Services;

use App\Models\DailyPointLimit;
use App\Models\GamificationRuleLog;
use App\Models\LevelDefinition;
use App\Models\PointsTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UserStatsRecalculationService
{
    protected $pointsService;

    protected $activitySummaryService;

    public function __construct(PointsService $pointsService, ActivitySummaryService $activitySummaryService)
    {
        $this->pointsService = $pointsService;
        $this->activitySummaryService = $activitySummaryService;
    }

    /**
     * Audit a user's stats and return a mismatch report.
     */
    public function auditUser(User $user): array
    {
        $pointsSnapshot = $this->calculateExpectedPoints($user);
        $xpSnapshot = $this->calculateExpectedXp($user);

        $mismatches = [
            'points' => [
                'pp' => [
                    'stored' => (float) $user->pp,
                    'calculated' => $pointsSnapshot['pp'],
                    'diff' => (float) $user->pp - $pointsSnapshot['pp'],
                ],
                'earned' => [
                    'stored' => (float) $user->total_points_earned,
                    'calculated' => $pointsSnapshot['earned'],
                    'diff' => (float) $user->total_points_earned - $pointsSnapshot['earned'],
                ],
                'spent' => [
                    'stored' => (float) $user->total_points_spent,
                    'calculated' => $pointsSnapshot['spent'],
                    'diff' => (float) $user->total_points_spent - $pointsSnapshot['spent'],
                ],
                'unmapped_conversions' => $pointsSnapshot['unmapped_conversions'],
            ],
            'xp' => [
                'total_xp' => [
                    'stored' => (int) $user->xp, // Legacy shadow
                    'calculated' => $xpSnapshot['total_xp'],
                    'diff' => (int) $user->xp - $xpSnapshot['total_xp'],
                ],
                'level' => [
                    'stored' => (int) $user->level,
                    'calculated' => $xpSnapshot['level'],
                    'diff' => (int) $user->level - $xpSnapshot['level'],
                ],
                'current_xp' => [
                    'stored' => (int) $user->current_xp,
                    'calculated' => $xpSnapshot['current_xp'],
                    'diff' => (int) $user->current_xp - $xpSnapshot['current_xp'],
                ],
            ],
            'out_of_sync' => [
                'legacy_level' => (int) $user->level !== (int) $user->xp_level,
            ],
        ];

        return $mismatches;
    }

    /**
     * Calculate expected points from transactions.
     */
    public function calculateExpectedPoints(User $user): array
    {
        $transactions = PointsTransaction::where('user_id', $user->id)
            ->where('status', 'completed')
            ->orderBy('created_at')   // critical: admin_adjust 'set' is order-dependent
            ->orderBy('id')           // stable tie-break within same second
            ->get();

        $pp = 0.0;
        $earned = 0.0;
        $spent = 0.0;
        $unmapped = 0;

        foreach ($transactions as $tx) {
            $amount = (float) $tx->amount;
            switch ($tx->transaction_type) {
                case 'earn':
                case 'refund':
                case 'transfer_in':
                    $pp += $amount;
                    $earned += $amount;
                    break;
                case 'spend':
                case 'transfer_out':
                    $pp -= $amount;
                    $spent += $amount;
                    break;
                case 'transfer':
                    // Legacy transfer - need to check source_id or metadata
                    // If source_id is to_user_id, it's transfer_out
                    // This depends on how old data was stored.
                    // For now, assume transfer means 'out' if no metadata
                    $pp -= $amount;
                    $spent += $amount;
                    break;
                case 'conversion':
                    $direction = $tx->metadata['conversion_type'] ?? null;
                    if ($direction === 'points_to_money') {
                        $pp -= $amount;
                        $spent += $amount;
                    } elseif ($direction === 'money_to_points') {
                        $pp += $amount;
                        $earned += $amount;
                    } else {
                        $unmapped++;
                    }
                    break;
                case 'admin_adjust':
                    $action = $tx->metadata['admin_action'] ?? null;
                    if ($action === 'add') {
                        $pp += $amount;
                        $earned += $amount;
                    } elseif ($action === 'deduct') {
                        $pp -= $amount;
                        $spent += $amount;
                    } elseif ($action === 'set') {
                        $pp = $amount;
                    } else {
                        $unmapped++;
                    }
                    break;
            }
        }

        return [
            'pp' => $pp,
            'earned' => $earned,
            'spent' => $spent,
            'unmapped_conversions' => $unmapped,
        ];
    }

    /**
     * Calculate expected XP and Level from logs.
     */
    public function calculateExpectedXp(User $user): array
    {
        $totalXp = (int) GamificationRuleLog::where('user_id', $user->id)
            ->where('result', 'awarded')
            ->sum('xp_awarded');

        $levelDef = LevelDefinition::where('xp_required', '<=', $totalXp)
            ->orderByDesc('level')
            ->first();

        $level = $levelDef ? $levelDef->level : 1;
        $nextLevelDef = LevelDefinition::where('level', $level + 1)->first();

        $currentLevelXpRequired = $levelDef ? $levelDef->xp_required : 0;
        $currentXp = $totalXp - $currentLevelXpRequired;
        $xpForNextLevel = $nextLevelDef ? ($nextLevelDef->xp_required - $currentLevelXpRequired) : 0;

        return [
            'total_xp' => $totalXp,
            'level' => $level,
            'current_xp' => $currentXp,
            'xp_for_next_level' => $xpForNextLevel,
        ];
    }

    /**
     * Apply fixes to a user's stats.
     */
    public function recalculateUser(User $user, string $runId, bool $apply = false, array $options = []): array
    {
        return DB::transaction(function () use ($user, $runId, $apply, $options) {
            // Always re-fetch inside transaction for consistent snapshot.
            // In apply mode, lock the row to prevent concurrent modifications.
            $user = $apply
                ? User::where('id', $user->id)->lockForUpdate()->first()
                : User::find($user->id);

            $results = [
                'user_id' => $user->id,
                'changes' => [],
            ];

            // 1. Recalculate Points
            $expectedPoints = $this->calculateExpectedPoints($user);
            $this->logChange($user, $runId, 'pp', $user->pp, $expectedPoints['pp'], $apply, $results);
            $this->logChange($user, $runId, 'total_points_earned', $user->total_points_earned, $expectedPoints['earned'], $apply, $results);
            $this->logChange($user, $runId, 'total_points_spent', $user->total_points_spent, $expectedPoints['spent'], $apply, $results);

            if ($apply) {
                $user->update([
                    'pp' => $expectedPoints['pp'],
                    'total_points_earned' => $expectedPoints['earned'],
                    'total_points_spent' => $expectedPoints['spent'],
                ]);
            }

            // 2. Recalculate XP
            $expectedXp = $this->calculateExpectedXp($user);
            $this->logChange($user, $runId, 'xp', $user->xp, $expectedXp['total_xp'], $apply, $results);
            $this->logChange($user, $runId, 'level', $user->level, $expectedXp['level'], $apply, $results);
            $this->logChange($user, $runId, 'xp_level', $user->xp_level, $expectedXp['level'], $apply, $results);
            $this->logChange($user, $runId, 'current_xp', $user->current_xp, $expectedXp['current_xp'], $apply, $results);
            $this->logChange($user, $runId, 'xp_for_next_level', $user->xp_for_next_level, $expectedXp['xp_for_next_level'], $apply, $results);

            if ($apply) {
                $user->update([
                    'xp' => $expectedXp['total_xp'],
                    'level' => $expectedXp['level'],
                    'xp_level' => $expectedXp['level'],
                    'current_xp' => $expectedXp['current_xp'],
                    'xp_for_next_level' => $expectedXp['xp_for_next_level'],
                ]);
            }

            // 3. Rebuild Daily Limits (Optional)
            if (! empty($options['rebuild_limits'])) {
                $this->rebuildDailyLimits($user, $apply);
            }

            // 4. Rebuild Summaries (Optional)
            if (! empty($options['rebuild_summaries'])) {
                $this->rebuildSummaries($user, $apply);
            }

            // 5. Clear leaderboard caches (GamificationService key pattern: leaderboard_{type}_{page}_{perPage})
            if ($apply && ! empty($results['changes'])) {
                $this->clearLeaderboardCache();
            }

            return $results;
        });
    }

    /**
     * Flush known leaderboard cache keys.
     * GamificationService uses: Cache::remember("leaderboard_{type}_{page}_{perPage}", 10min, ...)
     * TTL is only 10 min, but we force-clear after apply to avoid stale ranks.
     */
    public function clearLeaderboardCache(): void
    {
        $types = ['points', 'xp', 'streak', 'achievement'];
        foreach ($types as $type) {
            for ($page = 1; $page <= 5; $page++) {
                foreach ([10, 20, 50] as $perPage) {
                    Cache::forget("leaderboard_{$type}_{$page}_{$perPage}");
                }
            }
        }
    }

    protected function logChange(User $user, string $runId, string $field, $before, $after, bool $apply, array &$results): void
    {
        if ($before != $after) {
            $results['changes'][] = [
                'field' => $field,
                'before' => $before,
                'after' => $after,
            ];

            if ($apply) {
                DB::table('user_stats_recalculation_logs')->insert([
                    'user_id' => $user->id,
                    'run_id' => $runId,
                    'field' => $field,
                    'value_before' => $before,
                    'value_after' => $after,
                    'triggered_by' => 'command',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function rebuildDailyLimits(User $user, bool $apply): void
    {
        if (! $apply) {
            return;
        }

        // Group transactions by date.
        // Mirrors the sign rules in calculateExpectedPoints():
        //   positive side: earn, refund, transfer_in, conversion(money_to_points), admin_adjust(add)
        //   negative side: spend, transfer_out, transfer, conversion(points_to_money), admin_adjust(deduct)
        //   admin_adjust(set) and unmapped conversions are excluded from daily limits (edge cases).
        $limits = PointsTransaction::where('user_id', $user->id)
            ->where('status', 'completed')
            ->selectRaw("DATE(created_at) as date,
                SUM(CASE
                    WHEN transaction_type IN ('earn', 'refund', 'transfer_in') THEN amount
                    WHEN transaction_type = 'conversion' AND JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.conversion_type')) = 'money_to_points' THEN amount
                    WHEN transaction_type = 'admin_adjust' AND JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.admin_action')) = 'add' THEN amount
                    ELSE 0
                END) as earned,
                SUM(CASE
                    WHEN transaction_type IN ('spend', 'transfer_out', 'transfer') THEN amount
                    WHEN transaction_type = 'conversion' AND JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.conversion_type')) = 'points_to_money' THEN amount
                    WHEN transaction_type = 'admin_adjust' AND JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.admin_action')) = 'deduct' THEN amount
                    ELSE 0
                END) as spent")
            ->groupBy('date')
            ->get();

        foreach ($limits as $limit) {
            DailyPointLimit::updateOrCreate(
                ['user_id' => $user->id, 'date' => $limit->date],
                ['points_earned' => $limit->earned, 'points_spent' => $limit->spent]
            );
        }
    }

    public function rebuildSummaries(User $user, bool $apply): void
    {
        if (! $apply) {
            return;
        }

        // Find all dates with activity
        $dates = DB::table('user_usage_events')
            ->where('user_id', $user->id)
            ->selectRaw('DISTINCT DATE(occurred_at) as date')
            ->pluck('date');

        foreach ($dates as $date) {
            $this->activitySummaryService->updateDailySummary($user, $date);
        }
    }
}
