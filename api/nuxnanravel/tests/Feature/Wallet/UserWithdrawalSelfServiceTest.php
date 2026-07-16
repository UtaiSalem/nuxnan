<?php

namespace Tests\Feature\Wallet;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class UserWithdrawalSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(float $wallet = 5000): array
    {
        $user = User::factory()->create(['wallet' => $wallet]);
        $token = JWTAuth::fromUser($user);

        return [$user, $token];
    }

    private function createWithdrawal(string $token): int
    {
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/wallet/withdraw', [
                'amount' => 100,
                'method' => 'bank_transfer',
                'bank_account' => [
                    'bank_name' => 'kbank',
                    'account_number' => '1234567890',
                    'account_name' => 'สมชาย ใจดี',
                ],
            ]);
        $response->assertStatus(200);

        return $response->json('data.transaction_id');
    }

    public function test_user_can_list_own_withdrawals(): void
    {
        [$user, $token] = $this->actingUser();
        $txId = $this->createWithdrawal($token);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/wallet/withdrawals');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.withdrawals.0.id', $txId)
            ->assertJsonPath('data.withdrawals.0.status', 'pending');

        // Sensitive fields never leak in the serialized payload.
        $row = $response->json('data.withdrawals.0');
        $this->assertArrayNotHasKey('destination_snapshot', $row);
        $this->assertArrayNotHasKey('payout_proof_path', $row);
        $this->assertArrayNotHasKey('idempotency_key', $row);
    }

    public function test_user_cannot_see_other_users_withdrawals(): void
    {
        [, $tokenA] = $this->actingUser();
        $txId = $this->createWithdrawal($tokenA);

        [$userB] = $this->actingUser();

        $list = $this->actingAs($userB, 'api')
            ->getJson('/api/wallet/withdrawals');
        $list->assertStatus(200)->assertJsonCount(0, 'data.withdrawals');

        $this->actingAs($userB, 'api')
            ->postJson("/api/wallet/withdrawals/{$txId}/cancel")
            ->assertStatus(404);
    }

    public function test_user_can_cancel_own_pending_withdrawal_and_money_returns(): void
    {
        [$user, $token] = $this->actingUser(1000);
        $txId = $this->createWithdrawal($token);

        $this->assertEquals('900.00', (string) $user->fresh()->wallet);
        $this->assertEquals('100.00', (string) $user->fresh()->locked_balance);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/wallet/withdrawals/{$txId}/cancel");

        $response->assertStatus(200)->assertJsonPath('data.status', 'cancelled');

        $user = $user->fresh();
        $this->assertEquals('1000.00', (string) $user->wallet);
        $this->assertEquals('0.00', (string) $user->locked_balance);

        // A new withdrawal is allowed again after cancelling.
        $this->createWithdrawal($token);
    }

    public function test_user_cannot_cancel_withdrawal_after_review_started(): void
    {
        [$user, $token] = $this->actingUser();
        $txId = $this->createWithdrawal($token);

        WalletTransaction::whereKey($txId)->update(['status' => 'under_review']);

        $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/wallet/withdrawals/{$txId}/cancel")
            ->assertStatus(422);

        $this->assertSame('under_review', WalletTransaction::find($txId)->status);
    }

    public function test_lesson_style_purchase_does_not_block_real_withdrawal(): void
    {
        [$user, $token] = $this->actingUser(1000);

        // Internal purchase (e.g. lesson unlock / exam fee) settles instantly...
        $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/wallet/deduct', [
                'amount' => 200,
                'reason' => 'lesson unlock',
            ])
            ->assertStatus(200);

        $purchase = WalletTransaction::where('user_id', $user->id)->first();
        $this->assertSame('purchase', $purchase->transaction_type);
        $this->assertSame('completed', $purchase->status);
        $this->assertEquals('0.00', (string) $user->fresh()->locked_balance);

        // ...and must NOT trigger "A withdrawal is already pending".
        $this->createWithdrawal($token);
    }
}
