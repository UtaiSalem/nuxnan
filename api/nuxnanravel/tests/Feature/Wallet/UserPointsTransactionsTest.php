<?php

namespace Tests\Feature\Wallet;

use App\Models\Role;
use App\Models\User;
use App\Services\PointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin fraud lens: browse a user's points-transfer history from the wallet
 * withdrawal review, with transfer counterparties resolved so an admin can
 * see who sent the user their points.
 */
class UserPointsTransactionsTest extends TestCase
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

    public function test_admin_sees_incoming_transfer_with_resolved_sender(): void
    {
        $victim = User::factory()->create(['name' => 'เหยื่อ ผู้เสียหาย']);
        $attacker = User::factory()->create();
        $victim->pp = 5000;
        $victim->save();
        app(PointsService::class)->transfer($victim, $attacker->fresh(), 5000, 'stolen');

        $res = $this->actingAs($this->admin(), 'api')
            ->getJson("/api/admin/wallet/users/{$attacker->id}/points-transactions?type=transfers");

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.transactions.0.transaction_type', 'transfer_in')
            ->assertJsonPath('data.transactions.0.direction', 'in')
            ->assertJsonPath('data.transactions.0.counterparty.id', $victim->id)
            ->assertJsonPath('data.transactions.0.counterparty.name', 'เหยื่อ ผู้เสียหาย');
    }

    public function test_transfers_filter_excludes_non_transfer_rows(): void
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();
        $victim->pp = 5000;
        $victim->save();
        app(PointsService::class)->transfer($victim, $attacker->fresh(), 3600, 'x');
        // A conversion row exists too, but the transfers filter must hide it.
        app(PointsService::class)->convertPointsToWallet($attacker->fresh(), 3600);

        $res = $this->actingAs($this->admin(), 'api')
            ->getJson("/api/admin/wallet/users/{$attacker->id}/points-transactions?type=transfers");

        $res->assertOk();
        foreach ($res->json('data.transactions') as $tx) {
            $this->assertContains($tx['transaction_type'], ['transfer_in', 'transfer_out']);
        }
    }

    public function test_non_admin_is_forbidden(): void
    {
        $target = User::factory()->create();

        $this->actingAs(User::factory()->create(), 'api')
            ->getJson("/api/admin/wallet/users/{$target->id}/points-transactions")
            ->assertForbidden();
    }
}
