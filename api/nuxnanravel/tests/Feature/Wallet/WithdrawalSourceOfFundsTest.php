<?php

namespace Tests\Feature\Wallet;

use App\Models\Role;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\PointsService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the admin fraud-review control: tracing where the money behind a
 * withdrawal came from. The abuse pattern is "sign into a victim's account ->
 * transfer their points out -> convert to wallet money -> withdraw", so the
 * trace must surface the incoming points transfer, resolve the sender, and
 * raise the risk level.
 */
class WithdrawalSourceOfFundsTest extends TestCase
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

    /**
     * Build the full fraud trail: victim -> attacker points transfer ->
     * conversion -> withdrawal. Returns the attacker's pending withdrawal.
     */
    private function buildTrail(User $attacker, User $victim, int $points = 120000): WalletTransaction
    {
        $victim->pp = $points;
        $victim->save();

        $pointsService = app(PointsService::class);
        // Stolen points move from the victim into the attacker's account.
        $pointsService->transfer($victim, $attacker->fresh(), $points, 'stolen');
        // Attacker launders points into withdrawable wallet money (120000 pts = 100 THB).
        $pointsService->convertPointsToWallet($attacker->fresh(), $points);

        // Attacker requests to withdraw the resulting money.
        return app(WalletService::class)->withdraw($attacker->fresh(), '100', 'bank_transfer', [
            'bank_name' => 'kbank',
            'account_number' => '1234567890',
            'account_name' => 'Test User',
        ]);
    }

    public function test_source_of_funds_traces_incoming_points_transfer_and_flags_high_risk(): void
    {
        $victim = User::factory()->create(['name' => 'เหยื่อ ผู้เสียหาย']);
        $attacker = User::factory()->create(['wallet' => 0]);

        $withdrawal = $this->buildTrail($attacker, $victim);
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'api')
            ->getJson("/api/admin/wallet/withdrawals/{$withdrawal->id}/source-of-funds");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.inbound_points_transfers.count', 1)
            ->assertJsonPath('data.summary.inbound_points_transfers.points', 120000)
            ->assertJsonPath('data.summary.inbound_points_transfers.value_thb', 100)
            ->assertJsonPath('data.risk.level', 'high');

        // The stolen-points sender must be resolved so the admin can see it.
        $response->assertJsonPath('data.summary.senders.0.user.id', $victim->id)
            ->assertJsonPath('data.summary.senders.0.user.name', 'เหยื่อ ผู้เสียหาย');

        $flags = $response->json('data.risk.flags');
        $this->assertContains('incoming_points_transfers', $flags);
        $this->assertContains('funded_by_incoming_transfers', $flags);
    }

    public function test_source_of_funds_reports_low_risk_when_no_incoming_transfers(): void
    {
        $user = User::factory()->create(['wallet' => 5000]);
        $withdrawal = app(WalletService::class)->withdraw($user, '100', 'bank_transfer', [
            'bank_name' => 'kbank',
            'account_number' => '1234567890',
            'account_name' => 'Test User',
        ]);

        $response = $this->actingAs($this->admin(), 'api')
            ->getJson("/api/admin/wallet/withdrawals/{$withdrawal->id}/source-of-funds");

        $response->assertStatus(200)
            ->assertJsonPath('data.risk.level', 'low')
            ->assertJsonPath('data.summary.inbound_points_transfers.count', 0)
            ->assertJsonPath('data.summary.incoming_value_thb', 0);
    }

    public function test_source_of_funds_is_forbidden_for_the_owner(): void
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create(['wallet' => 0]);
        $withdrawal = $this->buildTrail($attacker, $victim);

        // The withdrawer must not be able to inspect their own fund-source trail.
        $response = $this->actingAs($attacker->fresh(), 'api')
            ->getJson("/api/admin/wallet/withdrawals/{$withdrawal->id}/source-of-funds");

        $response->assertStatus(403);
    }
}
