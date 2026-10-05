<?php

namespace Tests\Feature\Wallet;

use App\Models\PointsTransaction;
use App\Models\Role;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\PointsService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin cancellation of an intra-user points↔money conversion. Same owner-
 * approved policy as a transfer: claw back up to the credited side's available
 * balance, restore the other side at the stored rate, record any shortfall,
 * and mark both legs so it cannot be reversed twice.
 */
class ConversionReversalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['SUPER_ADMIN', 'ADMIN', 'MODERATOR'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');

        return $admin;
    }

    public function test_reverse_points_to_money_conversion_restores_both_sides(): void
    {
        $user = User::factory()->create(['pp' => 5000, 'wallet' => 0]);
        app(PointsService::class)->convertPointsToWallet($user->fresh(), 1200); // 1200 pts -> 1.00 THB

        $pointsLeg = PointsTransaction::where('user_id', $user->id)
            ->where('transaction_type', 'conversion')->firstOrFail();
        // Capture the wallet leg now (before any reversal/correction rows exist).
        $walletLeg = WalletTransaction::where('user_id', $user->id)
            ->where('transaction_type', 'conversion')->firstOrFail();

        $this->actingAs($this->admin(), 'api')
            ->postJson("/api/admin/wallet/points-transactions/{$pointsLeg->id}/reverse", ['reason' => 'fraud'])
            ->assertOk()
            ->assertJsonPath('data.kind', 'conversion')
            ->assertJsonPath('data.unit', 'THB')
            ->assertJsonPath('data.reversed', 1)
            ->assertJsonPath('data.shortfall', 0);

        // Wallet clawed back to 0, points restored to the original 5000.
        $this->assertSame('0.00', $user->fresh()->wallet);
        $this->assertSame('5000.00', $user->fresh()->pp);

        // Both legs are now stamped and a second reverse (either leg) is rejected.
        $this->actingAs($this->admin(), 'api')
            ->postJson("/api/admin/wallet/points-transactions/{$pointsLeg->id}/reverse", ['reason' => 'again'])
            ->assertStatus(422);

        $this->actingAs($this->admin(), 'api')
            ->postJson("/api/admin/wallet/transactions/{$walletLeg->id}/reverse", ['reason' => 'again'])
            ->assertStatus(422);
    }

    public function test_reverse_money_to_points_conversion_claws_back_up_to_available(): void
    {
        $user = User::factory()->create(['wallet' => 10, 'pp' => 0]);
        app(WalletService::class)->convertWalletToPoints($user->fresh(), 5.00); // 5 THB -> 6000 pts

        // User spends 1000 of the credited points → only 5000 left to claw back.
        $user->refresh();
        $user->update(['pp' => 5000]);

        $walletLeg = WalletTransaction::where('user_id', $user->id)
            ->where('transaction_type', 'conversion')->firstOrFail();

        $this->actingAs($this->admin(), 'api')
            ->postJson("/api/admin/wallet/transactions/{$walletLeg->id}/reverse", ['reason' => 'fraud'])
            ->assertOk()
            ->assertJsonPath('data.kind', 'conversion')
            ->assertJsonPath('data.unit', 'points')
            ->assertJsonPath('data.reversed', 5000)
            ->assertJsonPath('data.shortfall', 1000);

        // Points clawed back to 0; wallet restored at the stored rate
        // (5000 / 1200 = 4.17), not the full 5.00 that was converted.
        $this->assertSame('0.00', $user->fresh()->pp);
        $this->assertSame('9.17', $user->fresh()->wallet);
    }

    public function test_non_conversion_wallet_row_is_not_reversible_as_conversion(): void
    {
        $user = User::factory()->create(['wallet' => 100]);
        $deposit = WalletTransaction::create([
            'user_id' => $user->id,
            'transaction_type' => 'deposit',
            'amount' => 100,
            'balance_before' => 0,
            'balance_after' => 100,
            'currency' => 'THB',
            'status' => 'completed',
        ]);

        $this->actingAs($this->admin(), 'api')
            ->postJson("/api/admin/wallet/transactions/{$deposit->id}/reverse", ['reason' => 'x'])
            ->assertStatus(422);
    }
}
