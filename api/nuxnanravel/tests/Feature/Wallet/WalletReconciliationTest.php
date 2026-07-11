<?php

namespace Tests\Feature\Wallet;

use App\Models\User;
use App\Services\WalletReconciliationService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Proves the money-accounting invariants on a clean ledger built entirely
 * through WalletService: money out never exceeds money in, wallets always
 * equal their ledger, and every returned withdrawal has a refund entry.
 */
class WalletReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private WalletService $wallet;

    private WalletReconciliationService $recon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wallet = app(WalletService::class);
        $this->recon = app(WalletReconciliationService::class);
    }

    private function bank(): array
    {
        return ['bank_name' => 'kbank', 'account_number' => '1234567890', 'account_name' => 'T'];
    }

    public function test_full_lifecycle_keeps_money_out_within_money_in(): void
    {
        $admin = User::factory()->create();
        $a = User::factory()->create(['wallet' => 0]);
        $b = User::factory()->create(['wallet' => 0]);

        // Money in.
        $this->wallet->deposit($a, 1000, 'bank_transfer');
        $this->wallet->deposit($b, 500, 'bank_transfer');

        // Withdrawal that gets fully paid out (money genuinely leaves).
        $paid = $this->wallet->withdraw($a, '200', 'bank_transfer', $this->bank());
        $this->wallet->approveWithdrawal($paid->fresh(), $admin);
        $this->wallet->processWithdrawal($paid->fresh(), $admin);
        $this->wallet->markWithdrawalPaid($paid->fresh(), 'REF-1', $admin);

        // Withdrawal rejected -> refunded.
        $rejected = $this->wallet->withdraw($a, '100', 'bank_transfer', $this->bank());
        $this->wallet->rejectWithdrawal($rejected->fresh(), 'bad details', $admin);

        // Withdrawal cancelled by user -> refunded.
        $cancelled = $this->wallet->withdraw($a, '50', 'bank_transfer', $this->bank());
        $this->wallet->cancelWithdrawal($cancelled->fresh(), $a);

        // Withdrawal that fails at payout -> refunded.
        $failed = $this->wallet->withdraw($a, '300', 'bank_transfer', $this->bank());
        $this->wallet->approveWithdrawal($failed->fresh(), $admin);
        $this->wallet->processWithdrawal($failed->fresh(), $admin);
        $this->wallet->markWithdrawalFailed($failed->fresh(), 'bank down', $admin);

        // A peer transfer.
        $this->wallet->transfer($a->fresh(), $b->fresh(), 100);

        // --- Per-user reconciliation ---
        $this->assertTrue($this->recon->reconcileUser($a->fresh())['balanced']);
        $this->assertTrue($this->recon->reconcileUser($b->fresh())['balanced']);

        // A: 1000 in - 200 paid - 100 net transfer = 700. B: 500 + 100 = 600.
        $this->assertSame('700.00', (string) $a->fresh()->wallet);
        $this->assertSame('600.00', (string) $b->fresh()->wallet);

        // --- Global summary invariants ---
        $summary = $this->recon->summary();

        $this->assertTrue($summary['money_out_within_money_in'], 'money out must not exceed money in');
        $this->assertTrue($summary['ledger_matches_wallets'], 'wallets must equal ledger');
        $this->assertTrue($summary['withdrawal_refund_integrity']['balanced'], 'returned withdrawals must be refunded');
        $this->assertSame(0, $summary['negative_balance_count']);
        $this->assertSame(0, $summary['mismatched_user_count']);
        $this->assertTrue($summary['healthy']);

        // Withdrawal money split across meanings.
        $this->assertSame('200.00', $summary['withdrawals']['paid_out']);
        $this->assertSame('450.00', $summary['withdrawals']['returned']); // 100 + 50 + 300
        $this->assertSame('0.00', $summary['withdrawals']['held']);

        // Returned withdrawals exactly match the refunds issued for them.
        $this->assertSame('450.00', $summary['withdrawal_refund_integrity']['returned_withdrawals']);
        $this->assertSame('450.00', $summary['withdrawal_refund_integrity']['withdrawal_refunds']);
    }

    public function test_opening_balance_baseline_reconciles_seeded_wallets(): void
    {
        // A wallet seeded directly (no ledger) — the historical/dev-data case.
        $user = User::factory()->create(['wallet' => 1234.56]);

        $before = $this->recon->reconcileUser($user->fresh());
        $this->assertFalse($before['balanced'], 'seeded wallet starts unbalanced');
        $this->assertSame('1234.56', $before['diff']);

        $tx = $this->wallet->recordOpeningBalance($user->fresh());
        $this->assertNotNull($tx);
        $this->assertSame('opening_balance', $tx->transaction_type);
        $this->assertSame('0.00', $tx->balance_before);
        $this->assertSame('1234.56', $tx->balance_after);

        // Now fully reconciled, and the wallet value is unchanged (no money moved).
        $this->assertTrue($this->recon->reconcileUser($user->fresh())['balanced']);
        $this->assertSame('1234.56', (string) $user->fresh()->wallet);

        // Idempotent: a second run does nothing.
        $this->assertNull($this->wallet->recordOpeningBalance($user->fresh()));
    }

    public function test_flagging_legacy_withdrawal_clears_refund_integrity(): void
    {
        // Simulate a pre-hardening cancelled withdrawal: money left the wallet
        // but was refunded by old code WITHOUT a paired refund ledger row.
        $user = User::factory()->create(['wallet' => 0]);
        $this->wallet->deposit($user, 500, 'bank_transfer');
        $tx = $this->wallet->withdraw($user, '100', 'bank_transfer', $this->bank());
        // Old-style refund: bump wallet back, mark cancelled, no refund entry.
        $user->forceFill(['wallet' => 500])->save();
        $tx->forceFill(['status' => 'cancelled', 'metadata' => ['method' => 'bank_transfer']])->save();

        // Integrity report flags the unrefunded returned withdrawal.
        $this->assertFalse($this->recon->checkWithdrawalRefundIntegrity()['balanced']);

        $flagged = $this->wallet->flagLegacyWithdrawal($tx->fresh());
        $this->assertNotNull($flagged);
        $this->assertTrue($flagged->fresh()->metadata['legacy_no_refund_ledger']);

        // Now the report is clean, and re-flagging is a no-op.
        $this->assertTrue($this->recon->checkWithdrawalRefundIntegrity()['balanced']);
        $this->assertNull($this->wallet->flagLegacyWithdrawal($tx->fresh()));
    }

    public function test_every_withdrawal_status_is_preserved_with_amounts(): void
    {
        // This test intentionally holds several concurrent withdrawals to check
        // the per-status breakdown, so lift the one-pending-at-a-time policy.
        config(['wallet.withdraw.max_pending_requests' => 0]);

        $admin = User::factory()->create();
        $user = User::factory()->create(['wallet' => 0]);
        $this->wallet->deposit($user, 10000, 'bank_transfer'); // fund via ledger

        // pending
        $this->wallet->withdraw($user, '100', 'bank_transfer', $this->bank());
        // under_review
        $ur = $this->wallet->withdraw($user, '110', 'bank_transfer', $this->bank());
        $this->wallet->viewWithdrawal($ur->fresh(), $admin);
        // approved
        $ap = $this->wallet->withdraw($user, '120', 'bank_transfer', $this->bank());
        $this->wallet->approveWithdrawal($ap->fresh(), $admin);
        // processing
        $pr = $this->wallet->withdraw($user, '130', 'bank_transfer', $this->bank());
        $this->wallet->approveWithdrawal($pr->fresh(), $admin);
        $this->wallet->processWithdrawal($pr->fresh(), $admin);

        $breakdown = $this->recon->withdrawalBreakdown();

        $this->assertSame(1, $breakdown['by_status']['pending']['count']);
        $this->assertSame('100.00', $breakdown['by_status']['pending']['amount']);
        $this->assertSame('110.00', $breakdown['by_status']['under_review']['amount']);
        $this->assertSame('120.00', $breakdown['by_status']['approved']['amount']);
        $this->assertSame('130.00', $breakdown['by_status']['processing']['amount']);

        // All four are still holding value (deducted, not yet paid or returned).
        $this->assertSame('460.00', $breakdown['held']);

        // Reconciliation still perfect while money is held.
        $this->assertTrue($this->recon->reconcileUser($user->fresh())['balanced']);
        $this->assertTrue($this->recon->summary()['healthy']);
    }
}
