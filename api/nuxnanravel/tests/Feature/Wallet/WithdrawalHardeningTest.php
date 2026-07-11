<?php

namespace Tests\Feature\Wallet;

use App\Models\Role;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for the money-critical fixes in the withdrawal hardening
 * work (findings C1–C3, H1–H2). These exercise WalletService directly so the
 * financial invariants are asserted without the HTTP layer.
 */
class WithdrawalHardeningTest extends TestCase
{
    use RefreshDatabase;

    private WalletService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WalletService::class);

        foreach (['SUPER_ADMIN', 'ADMIN', 'MODERATOR'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }

    private function bankAccount(): array
    {
        return ['bank_name' => 'kbank', 'account_number' => '1234567890', 'account_name' => 'Test User'];
    }

    private function createWithdrawal(User $user, string $amount): WalletTransaction
    {
        return $this->service->withdraw($user, $amount, 'bank_transfer', $this->bankAccount());
    }

    /** C1: a failed payout must refund the money (wallet + compensating ledger). */
    public function test_failed_withdrawal_refunds_money_with_ledger_entry(): void
    {
        $user = User::factory()->create(['wallet' => 5000]);
        $admin = User::factory()->create();

        $tx = $this->createWithdrawal($user, '100');
        $this->assertSame('4900.00', (string) $user->fresh()->wallet, 'wallet debited on request');

        $this->assertTrue($this->service->approveWithdrawal($tx, $admin));
        $this->assertTrue($this->service->processWithdrawal($tx->fresh(), $admin));
        $this->assertTrue($this->service->markWithdrawalFailed($tx->fresh(), 'bank rejected', $admin));

        $this->assertSame('failed', $tx->fresh()->status);
        $this->assertSame('5000.00', (string) $user->fresh()->wallet, 'money returned after failure');

        $refund = WalletTransaction::where('transaction_type', 'refund')
            ->where('reference_number', "WDR-{$tx->id}")->first();
        $this->assertNotNull($refund, 'a compensating refund ledger entry exists');
        $this->assertSame('100.00', (string) $refund->amount);
    }

    /** C2: user cancellation must refund the money AND create a ledger entry (parity with reject). */
    public function test_cancel_withdrawal_refunds_money_with_ledger_entry(): void
    {
        $user = User::factory()->create(['wallet' => 5000]);

        $tx = $this->createWithdrawal($user, '250');
        $this->assertSame('4750.00', (string) $user->fresh()->wallet);

        $this->assertTrue($this->service->cancelWithdrawal($tx->fresh(), $user));

        $this->assertSame('cancelled', $tx->fresh()->status);
        $this->assertSame('5000.00', (string) $user->fresh()->wallet);
        $this->assertDatabaseHas('wallet_transactions', [
            'transaction_type' => 'refund',
            'reference_number' => "WDR-{$tx->id}",
            'amount' => '250.00',
        ]);
    }

    /** C3: a high-value withdrawal cannot be approved by a single admin. */
    public function test_maker_checker_blocks_single_admin_for_high_value(): void
    {
        config(['wallet.withdraw.maker_checker_threshold' => 10000]);

        $user = User::factory()->create(['wallet' => 50000]);
        $adminA = User::factory()->create();

        $tx = $this->createWithdrawal($user, '20000');

        // No prior reviewer -> blocked.
        try {
            $this->service->approveWithdrawal($tx->fresh(), $adminA);
            $this->fail('expected DomainException for un-reviewed high-value approval');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('maker-checker', $e->getMessage());
        }

        // Admin A reviews it (becomes the first reviewer).
        $this->service->viewWithdrawal($tx->fresh(), $adminA);
        $this->assertSame('under_review', $tx->fresh()->status);

        // Same admin still blocked.
        try {
            $this->service->approveWithdrawal($tx->fresh(), $adminA);
            $this->fail('expected DomainException when reviewer approves their own review');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('maker-checker', $e->getMessage());
        }
    }

    /** C3: two distinct admins can complete a high-value approval. */
    public function test_maker_checker_allows_two_distinct_admins(): void
    {
        config(['wallet.withdraw.maker_checker_threshold' => 10000]);

        $user = User::factory()->create(['wallet' => 50000]);
        $adminA = User::factory()->create();
        $adminB = User::factory()->create();

        $tx = $this->createWithdrawal($user, '20000');
        $this->service->viewWithdrawal($tx->fresh(), $adminA);

        $this->assertTrue($this->service->approveWithdrawal($tx->fresh(), $adminB));
        $this->assertSame('approved', $tx->fresh()->status);
        $this->assertSame($adminA->id, $tx->fresh()->reviewed_by, 'first reviewer preserved');
        $this->assertSame($adminB->id, $tx->fresh()->metadata['approved_by'], 'approver recorded');
    }

    /** C3: low-value withdrawals are unaffected and can be approved by one admin. */
    public function test_low_value_withdrawal_approves_with_single_admin(): void
    {
        config(['wallet.withdraw.maker_checker_threshold' => 10000]);

        $user = User::factory()->create(['wallet' => 5000]);
        $admin = User::factory()->create();

        $tx = $this->createWithdrawal($user, '100');
        $this->assertTrue($this->service->approveWithdrawal($tx->fresh(), $admin));
        $this->assertSame('approved', $tx->fresh()->status);
    }

    /** H2: the per-day withdrawal ceiling is enforced. */
    public function test_daily_limit_is_enforced(): void
    {
        config([
            'wallet.withdraw.daily_limit' => 150,
            'wallet.withdraw.monthly_limit' => 0,
            'wallet.withdraw.max_pending_requests' => 0, // isolate the daily-limit check
        ]);

        $user = User::factory()->create(['wallet' => 5000]);

        $this->createWithdrawal($user, '100'); // ok, running total 100

        $this->expectException(\DomainException::class);
        $this->createWithdrawal($user, '100'); // would make 200 > 150
    }

    /** H1: approve/reject privilege is symmetric — MODERATOR gets neither. */
    public function test_withdrawal_authorization_is_symmetric(): void
    {
        $user = User::factory()->create(['wallet' => 5000]);
        $tx = $this->createWithdrawal($user, '100');

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('SUPER_ADMIN');
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');
        $moderator = User::factory()->create();
        $moderator->assignRole('MODERATOR');

        foreach (['approve', 'reject'] as $ability) {
            $this->assertTrue($superAdmin->can($ability, $tx), "SUPER_ADMIN can {$ability}");
            $this->assertTrue($admin->can($ability, $tx), "ADMIN can {$ability}");
            $this->assertFalse($moderator->can($ability, $tx), "MODERATOR cannot {$ability}");
        }

        // MODERATOR may still view.
        $this->assertTrue($moderator->can('view', $tx));
    }
}
