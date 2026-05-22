<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\UserStatsRecalculationService;
use Illuminate\Support\Str;

class UserStatsRecalculateCommand extends Command
{
    protected $signature = 'user-stats:recalculate 
                            {--user= : Recalculate specific user ID} 
                            {--apply : Apply changes to database (default is dry-run)}
                            {--rebuild-limits : Rebuild daily point limits}
                            {--rebuild-summaries : Rebuild user activity summaries}
                            {--chunk=100 : Batch size}';

    protected $description = 'Recalculate user points and XP stats from logs';

    public function handle(UserStatsRecalculationService $recalcService)
    {
        $userId = $this->option('user');
        $apply = $this->option('apply');
        $chunkSize = (int) $this->option('chunk');
        $runId = (string) Str::uuid();

        if (!$apply) {
            $this->warn("DRY RUN MODE: No changes will be saved. Pass --apply to write.");
        } else {
            // In non-interactive environments (CI, cron) confirm() always returns false,
            // so we skip the prompt when stdin is not a TTY.
            $isTty = stream_isatty(STDIN);
            if ($isTty && !$this->confirm("Are you sure you want to APPLY changes to the database? This will modify user stats.")) {
                $this->warn("Aborted.");
                return;
            }
            if (!$isTty) {
                $this->warn("Non-interactive mode: skipping confirmation prompt. Applying changes.");
            }
        }

        $query = User::query();
        if ($userId) {
            $query->where('id', $userId);
        }

        $options = [
            'rebuild_limits' => $this->option('rebuild-limits'),
            'rebuild_summaries' => $this->option('rebuild-summaries'),
        ];

        $this->info("Starting recalculation (Run ID: $runId)...");

        $query->chunk($chunkSize, function ($users) use ($recalcService, $runId, $apply, $options) {
            foreach ($users as $user) {
                $this->comment("Processing User #{$user->id}: {$user->name}...");
                
                $results = $recalcService->recalculateUser($user, $runId, $apply, $options);
                
                if (empty($results['changes'])) {
                    $this->line("  ✓ No changes needed.");
                } else {
                    foreach ($results['changes'] as $change) {
                        $this->info("  → Fixed {$change['field']}: {$change['before']} to {$change['after']}");
                    }
                }
            }
        });

        $this->info("Recalculation complete.");
    }
}
