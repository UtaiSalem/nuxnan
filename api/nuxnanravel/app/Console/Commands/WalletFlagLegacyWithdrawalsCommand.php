<?php

namespace App\Console\Commands;

use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Console\Command;

/**
 * Flags legacy returned withdrawals (rejected/cancelled/failed) that were
 * refunded by pre-hardening code without a paired refund ledger row. Their
 * money is accounted for by the opening-balance baseline; flagging stops the
 * refund-integrity report from treating them as unrefunded payouts.
 *
 * Dry run by default; writes only with --commit. Idempotent.
 */
class WalletFlagLegacyWithdrawalsCommand extends Command
{
    protected $signature = 'wallet:flag-legacy-withdrawals {--commit : Actually flag the rows (default is a dry run)}';

    protected $description = 'Flag legacy returned withdrawals that have no paired refund ledger row (dry run unless --commit).';

    public function handle(WalletService $wallet): int
    {
        $rows = WalletTransaction::where('transaction_type', 'withdraw')
            ->whereIn('status', ['rejected', 'cancelled', 'failed'])
            ->whereNull('metadata->refund_transaction_id')
            ->whereNull('metadata->legacy_no_refund_ledger')
            ->get(['id', 'user_id', 'amount', 'status', 'created_at']);

        $commit = (bool) $this->option('commit');

        $this->info('=== Legacy returned withdrawals without refund ledger '.($commit ? '(COMMIT)' : '(DRY RUN)').' ===');

        if ($rows->isEmpty()) {
            $this->info('None found. Nothing to do.');

            return self::SUCCESS;
        }

        $this->table(['ID', 'User', 'Amount', 'Status', 'Created'], $rows->map(
            fn ($r) => [$r->id, $r->user_id, $r->amount, $r->status, (string) $r->created_at],
        )->all());
        $this->line('Total: '.$rows->count().' rows, amount '.number_format((float) $rows->sum('amount'), 2, '.', ''));

        if (! $commit) {
            $this->warn('DRY RUN — no rows written. Re-run with --commit to flag.');

            return self::SUCCESS;
        }

        $flagged = 0;
        foreach ($rows as $row) {
            if ($wallet->flagLegacyWithdrawal($row)) {
                $flagged++;
            }
        }

        $this->info("Flagged {$flagged} legacy withdrawal(s).");

        return self::SUCCESS;
    }
}
