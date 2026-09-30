<?php

namespace Tests\Feature;

use App\Exceptions\AccountEconomyRestrictedException;
use App\Models\AccountSuspensionAudit;
use App\Models\User;
use App\Services\PointsService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the fraud-suspension freeze: a suspended account must not be able to
 * accumulate, move, or convert points/wallet balance, while normal accounts
 * are unaffected. Learning access never routes through these services.
 */
class AccountSuspensionTest extends TestCase
{
    use RefreshDatabase;

    protected PointsService $pointsService;

    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pointsService = app(PointsService::class);
        $this->walletService = app(WalletService::class);
    }

    // ── Points freeze ────────────────────────────────────────────────

    public function test_earn_is_blocked_and_logged_when_points_suspended(): void
    {
        $user = User::factory()->create(['pp' => 100, 'points_suspended' => true]);

        $tx = $this->pointsService->earn($user, 50, 'test_earn', null, 'blocked earn');

        // Balance unchanged
        $this->assertEquals(100, $user->fresh()->pp);
        // A cancelled audit-trail record is written (no idempotency key)
        $this->assertEquals('cancelled', $tx->status);
        $this->assertDatabaseHas('points_transactions', [
            'user_id' => $user->id,
            'transaction_type' => 'earn',
            'status' => 'cancelled',
        ]);
    }

    public function test_spend_throws_when_points_suspended(): void
    {
        $user = User::factory()->create(['pp' => 500, 'points_suspended' => true]);

        $this->expectException(AccountEconomyRestrictedException::class);
        $this->pointsService->spend($user, 100, 'test_spend');
    }

    public function test_points_transfer_throws_when_sender_suspended(): void
    {
        $sender = User::factory()->create(['pp' => 500, 'points_suspended' => true]);
        $recipient = User::factory()->create(['pp' => 0]);

        $this->expectException(AccountEconomyRestrictedException::class);
        $this->pointsService->transfer($sender, $recipient, 100);
    }

    public function test_points_transfer_throws_when_recipient_suspended(): void
    {
        $sender = User::factory()->create(['pp' => 500]);
        $recipient = User::factory()->create(['pp' => 0, 'points_suspended' => true]);

        $this->expectException(AccountEconomyRestrictedException::class);
        $this->pointsService->transfer($sender, $recipient, 100);
    }

    public function test_convert_points_to_wallet_throws_when_suspended(): void
    {
        $user = User::factory()->create(['pp' => 5000, 'points_suspended' => true]);

        $this->expectException(AccountEconomyRestrictedException::class);
        $this->pointsService->convertPointsToWallet($user, 1200);
    }

    // ── Wallet freeze ────────────────────────────────────────────────

    public function test_wallet_withdraw_throws_when_wallet_suspended(): void
    {
        $user = User::factory()->create(['wallet' => 1000, 'wallet_suspended' => true]);

        $this->expectException(AccountEconomyRestrictedException::class);
        $this->walletService->withdraw($user, '100', 'bank_transfer', []);
    }

    public function test_wallet_transfer_throws_when_suspended(): void
    {
        $sender = User::factory()->create(['wallet' => 1000, 'wallet_suspended' => true]);
        $recipient = User::factory()->create(['wallet' => 0]);

        $this->expectException(AccountEconomyRestrictedException::class);
        $this->walletService->transfer($sender, $recipient, 100);
    }

    public function test_deduct_for_purchase_throws_when_wallet_suspended(): void
    {
        $user = User::factory()->create(['wallet' => 1000, 'wallet_suspended' => true]);

        $this->expectException(AccountEconomyRestrictedException::class);
        $this->walletService->deductForPurchase($user, '100', 'test_purchase');
    }

    // ── Normal accounts are unaffected (regression) ──────────────────

    public function test_normal_user_can_still_earn_and_spend(): void
    {
        $user = User::factory()->create(['pp' => 100]);

        $this->pointsService->earn($user, 50, 'test_earn');
        $this->assertEquals(150, $user->fresh()->pp);

        $spend = $this->pointsService->spend($user, 30, 'test_spend');
        $this->assertNotNull($spend);
        $this->assertEquals(120, $user->fresh()->pp);
    }

    // ── Helpers & audit model ────────────────────────────────────────

    public function test_frozen_helpers_reflect_flags(): void
    {
        $user = User::factory()->create([
            'points_suspended' => true,
            'wallet_suspended' => false,
        ]);

        $this->assertTrue($user->pointsFrozen());
        $this->assertFalse($user->walletFrozen());
    }

    public function test_suspension_audit_record_can_be_created(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();

        AccountSuspensionAudit::create([
            'user_id' => $user->id,
            'user_email' => $user->email,
            'action' => 'suspend',
            'points_suspended' => true,
            'wallet_suspended' => true,
            'reason' => 'suspected fraud',
            'performed_by' => $admin->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
        ]);

        $this->assertDatabaseHas('account_suspension_audits', [
            'user_id' => $user->id,
            'action' => 'suspend',
            'performed_by' => $admin->id,
            'reason' => 'suspected fraud',
        ]);
    }
}
