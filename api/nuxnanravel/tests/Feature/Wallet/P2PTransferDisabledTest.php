<?php

namespace Tests\Feature\Wallet;

use App\Exceptions\AccountEconomyRestrictedException;
use App\Models\User;
use App\Services\PointsService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

/**
 * Peer-to-peer points/wallet transfers are disabled platform-wide (anti-fraud).
 * Points and wallet balance may only be earned, spent, or withdrawn by their owner.
 */
class P2PTransferDisabledTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // ระบุชัดเจน เพื่อไม่ให้ค่าใน .env ของนักพัฒนาเปลี่ยนผลการทดสอบ
        config(['economy.p2p_transfers_enabled' => false]);
    }

    private function makeUsers(): array
    {
        $sender = User::factory()->create(['pp' => 1000, 'wallet' => 1000]);
        $recipient = User::factory()->create(['pp' => 0, 'wallet' => 0]);

        return [$sender, $recipient];
    }

    public function test_points_transfer_endpoint_returns_403(): void
    {
        [$sender, $recipient] = $this->makeUsers();
        $token = JWTAuth::fromUser($sender);

        $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/points/transfer', [
                'recipient_id' => $recipient->id,
                'amount' => 100,
            ])
            ->assertStatus(403)
            ->assertJson(['success' => false]);

        $this->assertEquals(1000, (float) $sender->fresh()->pp);
        $this->assertEquals(0, (float) $recipient->fresh()->pp);
        $this->assertDatabaseMissing('points_transactions', ['transaction_type' => 'transfer_out']);
        $this->assertDatabaseMissing('points_transactions', ['transaction_type' => 'transfer_in']);
    }

    public function test_wallet_transfer_endpoint_returns_403(): void
    {
        [$sender, $recipient] = $this->makeUsers();
        $token = JWTAuth::fromUser($sender);

        $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/wallet/transfer', [
                'recipient_id' => $recipient->id,
                'amount' => 100,
            ])
            ->assertStatus(403)
            ->assertJson(['success' => false]);

        $this->assertEquals(1000, (float) $sender->fresh()->wallet);
        $this->assertEquals(0, (float) $recipient->fresh()->wallet);
        $this->assertDatabaseMissing('wallet_transactions', ['transaction_type' => 'transfer']);
    }

    public function test_points_service_transfer_throws_when_disabled(): void
    {
        [$sender, $recipient] = $this->makeUsers();

        $this->expectException(AccountEconomyRestrictedException::class);
        app(PointsService::class)->transfer($sender, $recipient, 100);
    }

    public function test_wallet_service_transfer_throws_when_disabled(): void
    {
        [$sender, $recipient] = $this->makeUsers();

        $this->expectException(AccountEconomyRestrictedException::class);
        app(WalletService::class)->transfer($sender, $recipient, 100);
    }

    public function test_flag_on_restores_points_transfer(): void
    {
        [$sender, $recipient] = $this->makeUsers();

        config(['economy.p2p_transfers_enabled' => true]);
        app(PointsService::class)->transfer($sender, $recipient, 100);

        $this->assertEquals(100, (float) $recipient->fresh()->pp);
    }

    public function test_403_happens_before_validation(): void
    {
        [$sender] = $this->makeUsers();
        $token = JWTAuth::fromUser($sender);

        $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/points/transfer', [
                'recipient_id' => 999999,
                'amount' => 100,
            ])
            ->assertStatus(403);
    }
}
