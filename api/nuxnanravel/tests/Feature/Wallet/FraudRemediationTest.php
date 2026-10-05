<?php

namespace Tests\Feature\Wallet;

use App\Models\PointsTransaction;
use App\Models\Role;
use App\Models\User;
use App\Services\PointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin fraud remediation: reverse a fraudulent points transfer (clawing back
 * up to the recipient's available balance) and freeze/unfreeze a member's
 * wallets so they can no longer move funds while under investigation.
 */
class FraudRemediationTest extends TestCase
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

    public function test_reverse_points_transfer_claws_back_up_to_available_balance(): void
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();
        $victim->pp = 10000;
        $victim->save();
        app(PointsService::class)->transfer($victim, $attacker->fresh(), 10000, 'stolen');

        // Attacker spends 7000 → only 3000 remains to claw back.
        $attacker->refresh();
        $attacker->update(['pp' => 3000]);

        $transferIn = PointsTransaction::where('user_id', $attacker->id)
            ->where('transaction_type', 'transfer_in')->firstOrFail();

        $res = $this->actingAs($this->admin(), 'api')
            ->postJson("/api/admin/wallet/points-transactions/{$transferIn->id}/reverse", ['reason' => 'fraud']);

        $res->assertOk()
            ->assertJsonPath('data.reversed', 3000)
            ->assertJsonPath('data.shortfall', 7000);

        $this->assertSame('0.00', $attacker->fresh()->pp);
        $this->assertSame('3000.00', $victim->fresh()->pp);

        // Idempotent: a second reverse is rejected.
        $this->actingAs($this->admin(), 'api')
            ->postJson("/api/admin/wallet/points-transactions/{$transferIn->id}/reverse", ['reason' => 'again'])
            ->assertStatus(422);
    }

    public function test_reverse_requires_admin(): void
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();
        $victim->pp = 5000;
        $victim->save();
        app(PointsService::class)->transfer($victim, $attacker->fresh(), 5000, 'x');
        $transferIn = PointsTransaction::where('user_id', $attacker->id)->where('transaction_type', 'transfer_in')->firstOrFail();

        $this->actingAs(User::factory()->create(), 'api')
            ->postJson("/api/admin/wallet/points-transactions/{$transferIn->id}/reverse", ['reason' => 'x'])
            ->assertForbidden();
    }

    public function test_freeze_blocks_points_transfer_and_unfreeze_restores_it(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['pp' => 5000]);
        $recipient = User::factory()->create();

        $this->actingAs($admin, 'api')
            ->postJson("/api/admin/wallet/users/{$user->id}/freeze", ['scope' => 'points', 'reason' => 'fraud'])
            ->assertOk()
            ->assertJsonPath('data.points_frozen', true);

        // Frozen: the user can no longer transfer points.
        $this->actingAs($user->fresh(), 'api')
            ->postJson('/api/points/transfer', ['recipient_id' => $recipient->id, 'amount' => 100])
            ->assertStatus(403);

        $this->actingAs($admin, 'api')
            ->postJson("/api/admin/wallet/users/{$user->id}/unfreeze", ['scope' => 'points'])
            ->assertOk()
            ->assertJsonPath('data.points_frozen', false);

        // Unfrozen: transfer works again.
        $this->actingAs($user->fresh(), 'api')
            ->postJson('/api/points/transfer', ['recipient_id' => $recipient->id, 'amount' => 100])
            ->assertOk();
    }

    public function test_wallet_freeze_blocks_withdrawal(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['wallet' => 5000]);
        $user->profile()->create(['first_name' => 'สมชาย', 'last_name' => 'ใจดี']);

        $this->actingAs($admin, 'api')
            ->postJson("/api/admin/wallet/users/{$user->id}/freeze", ['scope' => 'wallet', 'reason' => 'fraud'])
            ->assertOk();

        $this->actingAs($user->fresh(), 'api')
            ->postJson('/api/wallet/withdraw', [
                'amount' => 100,
                'method' => 'bank_transfer',
                'bank_account' => ['bank_name' => 'kbank', 'account_number' => '1234567890', 'account_name' => 'สมชาย ใจดี'],
            ])
            ->assertStatus(403);
    }
}
