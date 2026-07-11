<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\WalletReconciliationService;
use App\Services\WalletService;
use Illuminate\Console\Command;

/**
 * One-time opening-balance baseline. For every user whose wallet is not fully
 * backed by ledger history, records a single reconciling 'opening_balance'
 * entry so the ledger starts from a clean, verifiable point.
 *
 * Safe by default: runs as a DRY RUN and writes nothing unless --commit is
 * given. Idempotent: re-running never double-baselines a user.
 */
class WalletBaselineCommand extends Command
{
    protected $signature = 'wallet:baseline
        {--commit : Actually write the baseline entries (default is a dry run)}
        {--force : Skip the confirmation prompt (for non-interactive runs)}
        {--user= : Baseline only this user id}';

    protected $description = 'Record opening-balance baseline entries so every wallet is backed by the ledger (dry run unless --commit).';

    public function handle(WalletService $wallet, WalletReconciliationService $recon): int
    {
        $commit = (bool) $this->option('commit');
        $singleUser = $this->option('user');

        if ($singleUser) {
            $user = User::find($singleUser);
            if (! $user) {
                $this->error("User {$singleUser} not found");

                return self::FAILURE;
            }
            $before = $recon->reconcileUser($user);
            if ($before['balanced']) {
                $this->info("User {$singleUser} is already balanced. Nothing to do.");

                return self::SUCCESS;
            }
            $this->line("User {$singleUser}: wallet={$before['wallet']} ledger={$before['ledger']} diff={$before['diff']}");
            if (! $commit) {
                $this->warn('DRY RUN — pass --commit to write this baseline entry.');

                return self::SUCCESS;
            }
            $tx = $wallet->recordOpeningBalance($user);
            $this->info($tx ? "Baseline recorded (tx #{$tx->id})." : 'Skipped (already baselined).');

            return self::SUCCESS;
        }

        $mismatched = $recon->findMismatchedUsers();
        $count = count($mismatched);

        $positive = 0.0;
        $negative = 0.0;
        foreach ($mismatched as $m) {
            $d = (float) $m['diff'];
            $d >= 0 ? $positive += $d : $negative += $d;
        }

        $this->info('=== Opening-balance baseline '.($commit ? '(COMMIT)' : '(DRY RUN)').' ===');
        $this->table(['Metric', 'Value'], [
            ['Users needing baseline', $count],
            ['Total positive diff (wallet > ledger)', number_format($positive, 2, '.', '')],
            ['Total negative diff (wallet < ledger)', number_format($negative, 2, '.', '')],
        ]);

        if ($count > 0) {
            $this->line('Sample (first 10):');
            $this->table(['User', 'Wallet', 'Ledger', 'Diff'], array_map(
                fn ($m) => [$m['user_id'], $m['wallet'], $m['ledger'], $m['diff']],
                array_slice($mismatched, 0, 10),
            ));
        }

        if (! $commit) {
            $this->warn('DRY RUN — no rows written. Re-run with --commit to apply.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Write {$count} opening-balance entries to the ledger?", false)) {
            $this->warn('Aborted.');

            return self::FAILURE;
        }

        $created = 0;
        $bar = $this->output->createProgressBar($count);
        foreach ($mismatched as $m) {
            $user = User::find($m['user_id']);
            if ($user && $wallet->recordOpeningBalance($user)) {
                $created++;
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);
        $this->info("Recorded {$created} baseline entries.");

        $summary = $recon->summary();
        $this->line('Post-baseline: wallets==ledger='.($summary['ledger_matches_wallets'] ? 'OK' : 'MISMATCH')
            .', mismatched='.$summary['mismatched_user_count']
            .', negatives='.$summary['negative_balance_count']);

        return self::SUCCESS;
    }
}
