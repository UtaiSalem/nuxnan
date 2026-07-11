<?php

namespace App\Services;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Verifies the integrity of the wallet ledger.
 *
 * Core invariant: every mutation of users.wallet is recorded as a
 * WalletTransaction row whose (balance_after - balance_before) equals the
 * applied change. Therefore, for any user:
 *
 *     users.wallet  ==  Σ (balance_after - balance_before)
 *
 * and globally the total money that has left the system (withdrawals paid +
 * external outflows) can never exceed the total money that came in.
 */
class WalletReconciliationService
{
    /**
     * Withdrawal statuses grouped by what they mean for the money.
     */
    public const WITHDRAWAL_HELD = ['pending', 'under_review', 'approved', 'processing'];

    public const WITHDRAWAL_PAID = ['completed', 'paid'];

    public const WITHDRAWAL_RETURNED = ['rejected', 'cancelled', 'failed'];

    /**
     * Reconcile a single user's wallet against their ledger.
     *
     * @return array{user_id:int, wallet:string, ledger:string, diff:string, balanced:bool}
     */
    public function reconcileUser(User $user): array
    {
        $ledger = (float) WalletTransaction::where('user_id', $user->id)
            ->sum(DB::raw('balance_after - balance_before'));

        $wallet = (float) $user->wallet;
        $diff = round($wallet - $ledger, 2);

        return [
            'user_id' => $user->id,
            'wallet' => number_format($wallet, 2, '.', ''),
            'ledger' => number_format($ledger, 2, '.', ''),
            'diff' => number_format($diff, 2, '.', ''),
            'balanced' => abs($diff) < 0.01,
        ];
    }

    /**
     * Find every user whose wallet does not match their ledger.
     *
     * @return array<int, array{user_id:int, wallet:string, ledger:string, diff:string, balanced:bool}>
     */
    public function findMismatchedUsers(): array
    {
        $rows = WalletTransaction::query()
            ->selectRaw('user_id, SUM(balance_after - balance_before) AS ledger')
            ->groupBy('user_id')
            ->pluck('ledger', 'user_id');

        $mismatched = [];
        User::query()->select('id', 'wallet')->chunkById(500, function ($users) use (&$mismatched, $rows) {
            foreach ($users as $user) {
                $ledger = (float) ($rows[$user->id] ?? 0);
                $diff = round((float) $user->wallet - $ledger, 2);
                if (abs($diff) >= 0.01) {
                    $mismatched[] = [
                        'user_id' => $user->id,
                        'wallet' => number_format((float) $user->wallet, 2, '.', ''),
                        'ledger' => number_format($ledger, 2, '.', ''),
                        'diff' => number_format($diff, 2, '.', ''),
                        'balanced' => false,
                    ];
                }
            }
        });

        return $mismatched;
    }

    /**
     * Users whose wallet is negative — this must never happen.
     *
     * @return array<int, array{user_id:int, wallet:string}>
     */
    public function findNegativeBalances(): array
    {
        return User::query()
            ->where('wallet', '<', 0)
            ->get(['id', 'wallet'])
            ->map(fn ($u) => ['user_id' => $u->id, 'wallet' => number_format((float) $u->wallet, 2, '.', '')])
            ->all();
    }

    /**
     * Withdrawal totals broken down by every status, plus grouped meaning.
     */
    public function withdrawalBreakdown(): array
    {
        $rows = WalletTransaction::query()
            ->where('transaction_type', 'withdraw')
            ->selectRaw('status, COUNT(*) AS cnt, COALESCE(SUM(amount),0) AS total')
            ->groupBy('status')
            ->get();

        $byStatus = [];
        foreach (['pending', 'under_review', 'approved', 'processing', 'completed', 'paid', 'rejected', 'failed', 'cancelled'] as $status) {
            $byStatus[$status] = ['count' => 0, 'amount' => '0.00'];
        }
        foreach ($rows as $row) {
            $byStatus[$row->status] = [
                'count' => (int) $row->cnt,
                'amount' => number_format((float) $row->total, 2, '.', ''),
            ];
        }

        $sumOf = fn (array $statuses) => number_format(
            array_sum(array_map(fn ($s) => (float) $byStatus[$s]['amount'], $statuses)), 2, '.', ''
        );

        return [
            'by_status' => $byStatus,
            'held' => $sumOf(self::WITHDRAWAL_HELD),
            'paid_out' => $sumOf(self::WITHDRAWAL_PAID),
            'returned' => $sumOf(self::WITHDRAWAL_RETURNED),
        ];
    }

    /**
     * Every withdrawal that was returned (rejected/cancelled/failed) must have a
     * matching refund ledger entry. This checks the two totals agree.
     *
     * @return array{returned_withdrawals:string, withdrawal_refunds:string, diff:string, balanced:bool}
     */
    public function checkWithdrawalRefundIntegrity(): array
    {
        // Legacy pre-hardening returned withdrawals were refunded by old code
        // without a paired refund ledger row; their money is accounted for by
        // the opening-balance baseline instead, so they are flagged and skipped.
        $returned = (float) WalletTransaction::where('transaction_type', 'withdraw')
            ->whereIn('status', self::WITHDRAWAL_RETURNED)
            ->whereNull('metadata->legacy_no_refund_ledger')
            ->sum('amount');

        $refunds = (float) WalletTransaction::where('transaction_type', 'refund')
            ->whereIn('metadata->refund_type', ['withdrawal_reversal', 'withdrawal_cancellation', 'withdrawal_failure'])
            ->sum('amount');

        $diff = round($returned - $refunds, 2);

        return [
            'returned_withdrawals' => number_format($returned, 2, '.', ''),
            'withdrawal_refunds' => number_format($refunds, 2, '.', ''),
            'diff' => number_format($diff, 2, '.', ''),
            'balanced' => abs($diff) < 0.01,
        ];
    }

    /**
     * Global money-in / money-out summary derived from ledger deltas, plus the
     * key integrity checks. This is the single source of truth for "did any
     * money get created or destroyed?".
     */
    public function summary(): array
    {
        $moneyIn = (float) WalletTransaction::where(DB::raw('balance_after - balance_before'), '>', 0)
            ->sum(DB::raw('balance_after - balance_before'));
        $moneyOut = (float) WalletTransaction::where(DB::raw('balance_after - balance_before'), '<', 0)
            ->sum(DB::raw('balance_before - balance_after'));

        $ledgerNet = round($moneyIn - $moneyOut, 2);
        $walletTotal = round((float) User::sum('wallet'), 2);

        $mismatched = $this->findMismatchedUsers();
        $negatives = $this->findNegativeBalances();
        $refundIntegrity = $this->checkWithdrawalRefundIntegrity();

        return [
            'money_in' => number_format($moneyIn, 2, '.', ''),
            'money_out' => number_format($moneyOut, 2, '.', ''),
            'ledger_net' => number_format($ledgerNet, 2, '.', ''),
            'wallet_total' => number_format($walletTotal, 2, '.', ''),
            'ledger_matches_wallets' => abs($ledgerNet - $walletTotal) < 0.01,
            'money_out_within_money_in' => $moneyOut <= $moneyIn + 0.001,
            'withdrawals' => $this->withdrawalBreakdown(),
            'withdrawal_refund_integrity' => $refundIntegrity,
            'mismatched_user_count' => count($mismatched),
            'negative_balance_count' => count($negatives),
            'healthy' => abs($ledgerNet - $walletTotal) < 0.01
                && count($mismatched) === 0
                && count($negatives) === 0
                && $refundIntegrity['balanced'],
        ];
    }
}
