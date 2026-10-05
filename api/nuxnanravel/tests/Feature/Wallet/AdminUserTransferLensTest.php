<?php

namespace Tests\Feature\Wallet;

use App\Models\PointsTransaction;
use App\Models\Role;
use App\Models\User;
use App\Services\PointsService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin "user profile" transaction lens: list a member's peer transfers
 * (money + points) annotated with direction, counterparty and whether the row
 * can still be clawed back. Backs the reverse buttons on the user profile page.
 */
class AdminUserTransferLensTest extends TestCase
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

    public function test_points_transfer_lens_marks_incoming_reversible_and_outgoing_not(): void
    {
        $sender = User::factory()->create(['pp' => 5000]);
        $recipient = User::factory()->create();
        app(PointsService::class)->transfer($sender->fresh(), $recipient->fresh(), 1000, 'gift');

        $admin = $this->admin();

        // Recipient's incoming transfer is reversible and points back to the sender.
        $this->actingAs($admin, 'api')
            ->getJson("/api/admin/wallet/users/{$recipient->id}/points-transactions?type=transfers")
            ->assertOk()
            ->assertJsonPath('data.transactions.0.direction', 'in')
            ->assertJsonPath('data.transactions.0.reversible', true)
            ->assertJsonPath('data.transactions.0.reversed', false)
            ->assertJsonPath('data.transactions.0.counterparty.id', $sender->id);

        // Sender's outgoing transfer is visible but never reversible from here.
        $this->actingAs($admin, 'api')
            ->getJson("/api/admin/wallet/users/{$sender->id}/points-transactions?type=transfers")
            ->assertOk()
            ->assertJsonPath('data.transactions.0.direction', 'out')
            ->assertJsonPath('data.transactions.0.reversible', false)
            ->assertJsonPath('data.transactions.0.counterparty.id', $recipient->id);
    }

    public function test_points_transfer_lens_marks_row_reversed_after_claw_back(): void
    {
        $sender = User::factory()->create(['pp' => 5000]);
        $recipient = User::factory()->create();
        app(PointsService::class)->transfer($sender->fresh(), $recipient->fresh(), 1000, 'gift');

        $transferIn = PointsTransaction::where('user_id', $recipient->id)
            ->where('transaction_type', 'transfer_in')->firstOrFail();

        $admin = $this->admin();
        $this->actingAs($admin, 'api')
            ->postJson("/api/admin/wallet/points-transactions/{$transferIn->id}/reverse", ['reason' => 'fraud'])
            ->assertOk();

        // The reversal adds a correction row on the recipient, so locate the
        // original transfer_in row by id rather than by position.
        $response = $this->actingAs($admin, 'api')
            ->getJson("/api/admin/wallet/users/{$recipient->id}/points-transactions?type=transfers")
            ->assertOk();

        $rows = collect($response->json('data.transactions'));
        $original = $rows->firstWhere('id', $transferIn->id);

        $this->assertNotNull($original, 'original transfer_in row should still be listed');
        $this->assertTrue($original['reversed'], 'original row should be marked reversed');
        $this->assertFalse($original['reversible'], 'a reversed row is no longer reversible');
    }

    public function test_wallet_transfer_lens_marks_incoming_reversible(): void
    {
        $sender = User::factory()->create(['wallet' => 5000]);
        $recipient = User::factory()->create();
        app(WalletService::class)->transfer($sender->fresh(), $recipient->fresh(), 1000, 'payment');

        $this->actingAs($this->admin(), 'api')
            ->getJson("/api/admin/wallet/users/{$recipient->id}/wallet-transactions?type=transfers")
            ->assertOk()
            ->assertJsonPath('data.transactions.0.direction', 'in')
            ->assertJsonPath('data.transactions.0.reversible', true)
            ->assertJsonPath('data.transactions.0.reversed', false)
            ->assertJsonPath('data.transactions.0.counterparty.id', $sender->id);
    }

    public function test_transfer_lens_requires_admin(): void
    {
        $recipient = User::factory()->create();

        $this->actingAs(User::factory()->create(), 'api')
            ->getJson("/api/admin/wallet/users/{$recipient->id}/wallet-transactions?type=transfers")
            ->assertForbidden();
    }
}
