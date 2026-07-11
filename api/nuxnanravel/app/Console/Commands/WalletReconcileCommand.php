<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\WalletReconciliationService;
use Illuminate\Console\Command;

class WalletReconcileCommand extends Command
{
    protected $signature = 'wallet:reconcile
        {--user= : Reconcile a single user id}
        {--mismatched : List every user whose wallet != ledger}';

    protected $description = 'Verify wallet ledger integrity: money out never exceeds money in, wallets match their ledger, and returned withdrawals are refunded.';

    public function handle(WalletReconciliationService $recon): int
    {
        if ($userId = $this->option('user')) {
            $user = User::find($userId);
            if (! $user) {
                $this->error("User {$userId} not found");

                return self::FAILURE;
            }
            $result = $recon->reconcileUser($user);
            $this->table(array_keys($result), [array_map(fn ($v) => is_bool($v) ? ($v ? 'yes' : 'no') : $v, $result)]);

            return $result['balanced'] ? self::SUCCESS : self::FAILURE;
        }

        $summary = $recon->summary();

        $this->info('=== Wallet money-in / money-out ===');
        $this->table(['Metric', 'Value'], [
            ['Money in (ledger)', $summary['money_in']],
            ['Money out (ledger)', $summary['money_out']],
            ['Net (in - out)', $summary['ledger_net']],
            ['Sum of all wallets', $summary['wallet_total']],
            ['Money out ≤ money in', $summary['money_out_within_money_in'] ? 'OK' : 'VIOLATION'],
            ['Wallets == ledger', $summary['ledger_matches_wallets'] ? 'OK' : 'MISMATCH'],
            ['Mismatched users', $summary['mismatched_user_count']],
            ['Negative balances', $summary['negative_balance_count']],
        ]);

        $w = $summary['withdrawals'];
        $this->info('=== Withdrawals by status ===');
        $this->table(['Status', 'Count', 'Amount'], array_map(
            fn ($status, $d) => [$status, $d['count'], $d['amount']],
            array_keys($w['by_status']),
            $w['by_status'],
        ));
        $this->line("Held: {$w['held']}   Paid out: {$w['paid_out']}   Returned: {$w['returned']}");

        $ri = $summary['withdrawal_refund_integrity'];
        $this->info('=== Withdrawal refund integrity ===');
        $this->line("Returned withdrawals: {$ri['returned_withdrawals']}   Refunds issued: {$ri['withdrawal_refunds']}   Diff: {$ri['diff']} (".($ri['balanced'] ? 'OK' : 'MISMATCH').')');

        $li = $summary['locked_balance_integrity'];
        $this->info('=== Locked balance integrity ===');
        $this->line("locked_balance column: {$li['locked_column']}   Active withdrawals: {$li['active_withdrawals']}   Diff: {$li['diff']} (".($li['balanced'] ? 'OK' : 'MISMATCH').')');

        if ($this->option('mismatched')) {
            $mismatched = $recon->findMismatchedUsers();
            if ($mismatched) {
                $this->warn('Mismatched users (wallet != ledger):');
                $this->table(['User', 'Wallet', 'Ledger', 'Diff'], array_map(
                    fn ($m) => [$m['user_id'], $m['wallet'], $m['ledger'], $m['diff']],
                    $mismatched,
                ));
            }
        }

        if ($summary['healthy']) {
            $this->info('Ledger is HEALTHY.');

            return self::SUCCESS;
        }

        $this->error('Ledger has integrity issues (see above). Investigate before processing real payouts.');

        return self::FAILURE;
    }
}
