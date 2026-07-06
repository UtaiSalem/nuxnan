<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\UserStatsRecalculationService;
use Illuminate\Console\Command;

class UserStatsAuditCommand extends Command
{
    protected $signature = 'user-stats:audit 
                            {--user= : Audit specific user ID} 
                            {--limit=100 : Limit number of users to audit}
                            {--mismatch-only : Only show users with mismatches}';

    protected $description = 'Audit user points and XP stats against transaction logs';

    public function handle(UserStatsRecalculationService $recalcService)
    {
        $userId = $this->option('user');
        $limit = (int) $this->option('limit');
        $mismatchOnly = $this->option('mismatch-only');

        $query = User::query();
        if ($userId) {
            $query->where('id', $userId);
        } else {
            $query->limit($limit);
        }

        $totalChecked = 0;
        $totalMismatches = 0;

        $query->chunk(100, function ($users) use ($recalcService, $mismatchOnly, &$totalChecked, &$totalMismatches) {
            foreach ($users as $user) {
                $totalChecked++;
                $report = $recalcService->auditUser($user);
                $hasMismatch = $this->hasMismatch($report);

                if ($mismatchOnly && ! $hasMismatch) {
                    continue;
                }

                if ($hasMismatch) {
                    $totalMismatches++;
                }

                $this->displayReport($user, $report);
            }
        });

        $this->info("Audit complete. Total users checked: {$totalChecked}");
        $this->info("Total users with mismatches: {$totalMismatches}");

        return $totalMismatches > 0 ? 1 : 0;
    }

    protected function hasMismatch(array $report): bool
    {
        return abs($report['points']['pp']['diff']) > 0.01 ||
               abs($report['points']['earned']['diff']) > 0.01 ||
               abs($report['points']['spent']['diff']) > 0.01 ||
               $report['xp']['total_xp']['diff'] !== 0 ||
               $report['xp']['level']['diff'] !== 0 ||
               $report['out_of_sync']['legacy_level'];
    }

    protected function displayReport(User $user, array $report)
    {
        $this->line('--------------------------------------------------');
        $this->info("User #{$user->id}: {$user->name} ({$user->email})");

        $points = $report['points'];
        $this->table(
            ['Point Metric', 'Stored', 'Calculated', 'Diff'],
            [
                ['PP (Balance)', $points['pp']['stored'], $points['pp']['calculated'], $points['pp']['diff']],
                ['Total Earned', $points['earned']['stored'], $points['earned']['calculated'], $points['earned']['diff']],
                ['Total Spent', $points['spent']['stored'], $points['spent']['calculated'], $points['spent']['diff']],
            ]
        );

        if ($points['unmapped_conversions'] > 0) {
            $this->warn("Unmapped transactions found: {$points['unmapped_conversions']}");
        }

        $xp = $report['xp'];
        $this->table(
            ['XP Metric', 'Stored', 'Calculated', 'Diff'],
            [
                ['Total XP', $xp['total_xp']['stored'], $xp['total_xp']['calculated'], $xp['total_xp']['diff']],
                ['Level', $xp['level']['stored'], $xp['level']['calculated'], $xp['level']['diff']],
                ['Current XP', $xp['current_xp']['stored'], $xp['current_xp']['calculated'], $xp['current_xp']['diff']],
            ]
        );

        if ($report['out_of_sync']['legacy_level']) {
            $this->error("LEGACY SYNC ERROR: level ({$user->level}) != xp_level ({$user->xp_level})");
        }
    }
}
