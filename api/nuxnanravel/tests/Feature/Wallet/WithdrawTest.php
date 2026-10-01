<?php

namespace Tests\Feature\Wallet;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class WithdrawTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(float $wallet = 5000): array
    {
        $user = User::factory()->create(['wallet' => $wallet]);
        // Withdrawals require a completed profile whose name matches the
        // payout account name (see WalletController::withdraw fraud guard).
        $user->profile()->create(['first_name' => 'สมชาย', 'last_name' => 'ใจดี']);
        $token = JWTAuth::fromUser($user);

        return [$user, $token];
    }

    public function test_withdraw_uses_display_name_when_profile_name_is_empty(): void
    {
        $user = User::factory()->create(['wallet' => 5000, 'name' => 'พัชรี หนูวงค์']);
        $user->profile()->create(['first_name' => '', 'last_name' => '']);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/wallet/withdraw', [
                'amount' => 100,
                'method' => 'bank_transfer',
                'bank_account' => [
                    'bank_name' => 'kbank',
                    'account_number' => '1234567890',
                    'account_name' => 'ด.ญ.พัชรี หนูวงค์',
                ],
            ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_withdraw_via_bank_transfer_creates_pending_with_destination_type_bank(): void
    {
        [$user, $token] = $this->actingUser();

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

        $response->assertStatus(200)->assertJson(['success' => true]);

        $tx = WalletTransaction::where('user_id', $user->id)->first();
        $this->assertNotNull($tx);
        $this->assertSame('withdraw', $tx->transaction_type);
        $this->assertSame('pending', $tx->status);
        $this->assertSame('bank_transfer', $tx->metadata['destination_type']);
    }

    public function test_withdraw_via_promptpay_with_phone_number_succeeds(): void
    {
        [$user, $token] = $this->actingUser();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/wallet/withdraw', [
                'amount' => 100,
                'method' => 'promptpay',
                'bank_account' => [
                    'bank_name' => 'promptpay',
                    'account_number' => '0812345678',
                    'account_name' => 'สมชาย ใจดี',
                ],
            ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $tx = WalletTransaction::where('user_id', $user->id)->first();
        $this->assertSame('promptpay', $tx->metadata['destination_type']);
        $this->assertSame('promptpay', $tx->metadata['bank_account']['bank_name']);
        // metadata keeps only a masked account number; the full value is stored
        // encrypted in destination_snapshot.
        $this->assertSame('******5678', $tx->metadata['bank_account']['account_number']);
        $this->assertSame('0812345678', decrypt($tx->destination_snapshot)['account_number']);
    }

    public function test_withdraw_via_promptpay_with_national_id_succeeds(): void
    {
        [$user, $token] = $this->actingUser();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/wallet/withdraw', [
                'amount' => 100,
                'method' => 'promptpay',
                'bank_account' => [
                    'bank_name' => 'promptpay',
                    'account_number' => '1234567890123',
                    'account_name' => 'สมชาย ใจดี',
                ],
            ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_withdraw_rejects_promptpay_with_9_digits(): void
    {
        [$user, $token] = $this->actingUser();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/wallet/withdraw', [
                'amount' => 100,
                'method' => 'promptpay',
                'bank_account' => [
                    'bank_name' => 'promptpay',
                    'account_number' => '081234567',
                    'account_name' => 'สมชาย ใจดี',
                ],
            ]);

        $response->assertStatus(422);
        $this->assertSame(0, WalletTransaction::where('user_id', $user->id)->count());
    }

    public function test_withdraw_rejects_promptpay_with_landline_prefix(): void
    {
        [$user, $token] = $this->actingUser();

        // 10 digits but starts with 02 (landline) — not an allowed mobile prefix.
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/wallet/withdraw', [
                'amount' => 100,
                'method' => 'promptpay',
                'bank_account' => [
                    'bank_name' => 'promptpay',
                    'account_number' => '0212345678',
                    'account_name' => 'สมชาย ใจดี',
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_withdraw_rejects_amount_below_25(): void
    {
        [, $token] = $this->actingUser();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/wallet/withdraw', [
                'amount' => 24,
                'method' => 'bank_transfer',
                'bank_account' => [
                    'bank_name' => 'kbank',
                    'account_number' => '1234567890',
                    'account_name' => 'สมชาย ใจดี',
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_withdraw_allows_exactly_25(): void
    {
        [$user, $token] = $this->actingUser(50);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/wallet/withdraw', [
                'amount' => 25,
                'method' => 'bank_transfer',
                'bank_account' => [
                    'bank_name' => 'kbank',
                    'account_number' => '1234567890',
                    'account_name' => 'สมชาย ใจดี',
                ],
            ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertSame(1, WalletTransaction::where('user_id', $user->id)->count());
    }

    public function test_withdraw_rejects_unknown_method(): void
    {
        [, $token] = $this->actingUser();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/wallet/withdraw', [
                'amount' => 100,
                'method' => 'crypto',
                'bank_account' => [
                    'bank_name' => 'kbank',
                    'account_number' => '1234567890',
                    'account_name' => 'สมชาย ใจดี',
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_withdraw_rejects_unknown_bank(): void
    {
        [, $token] = $this->actingUser();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/wallet/withdraw', [
                'amount' => 100,
                'method' => 'bank_transfer',
                'bank_account' => [
                    'bank_name' => 'notabank',
                    'account_number' => '1234567890',
                    'account_name' => 'สมชาย ใจดี',
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_withdraw_normalizes_dashes_in_promptpay_number(): void
    {
        [$user, $token] = $this->actingUser();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/wallet/withdraw', [
                'amount' => 100,
                'method' => 'promptpay',
                'bank_account' => [
                    'bank_name' => 'promptpay',
                    'account_number' => '081-234-5678',
                    'account_name' => 'สมชาย ใจดี',
                ],
            ]);

        $response->assertStatus(200);

        $tx = WalletTransaction::where('user_id', $user->id)->first();
        // Dashes are normalized before storage; metadata is masked, snapshot holds the full value.
        $this->assertSame('******5678', $tx->metadata['bank_account']['account_number']);
        $this->assertSame('0812345678', decrypt($tx->destination_snapshot)['account_number']);
    }

    public function test_withdraw_applies_minimum_fee_floor_for_small_amounts(): void
    {
        [$user, $token] = $this->actingUser();

        // 100 * 0.5% = 0.50, below the 5 THB floor → fee = 5.
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

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'fee' => 5,
                    'net_amount' => 95,
                ],
            ]);
    }

    public function test_withdraw_applies_percentage_fee_above_the_floor(): void
    {
        [$user, $token] = $this->actingUser();

        // 5000 * 1% = 50, above the 5 THB floor → fee = 50.
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/wallet/withdraw', [
                'amount' => 5000,
                'method' => 'bank_transfer',
                'bank_account' => [
                    'bank_name' => 'kbank',
                    'account_number' => '1234567890',
                    'account_name' => 'สมชาย ใจดี',
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'fee' => 50,
                    'net_amount' => 4950,
                ],
            ]);
    }

    public function test_withdraw_rounds_fee_to_two_decimals(): void
    {
        [$user, $token] = $this->actingUser();

        // 3333.50 * 1% = 33.335 → rounded to 33.34, net = 3300.16.
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/wallet/withdraw', [
                'amount' => 3333.50,
                'method' => 'bank_transfer',
                'bank_account' => [
                    'bank_name' => 'kbank',
                    'account_number' => '1234567890',
                    'account_name' => 'สมชาย ใจดี',
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'fee' => 33.34,
                    'net_amount' => 3300.16,
                ],
            ]);
    }

    public function test_withdraw_rejects_when_balance_insufficient(): void
    {
        [$user, $token] = $this->actingUser(50);

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

        $response->assertStatus(400)->assertJson(['success' => false]);
    }

    public function test_withdraw_rejects_when_account_name_does_not_match_profile(): void
    {
        // Fraud guard (bug C): payout account name must contain the profile's
        // first + last name. A mismatching name is rejected before any money moves.
        [$user, $token] = $this->actingUser(5000); // profile = สมชาย ใจดี

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/wallet/withdraw', [
                'amount' => 100,
                'method' => 'bank_transfer',
                'bank_account' => [
                    'bank_name' => 'kbank',
                    'account_number' => '1234567890',
                    'account_name' => 'สมหญิง รวยทรัพย์',
                ],
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['errors' => ['bank_account.account_name']]);

        // No withdrawal row and the wallet is untouched.
        $this->assertDatabaseMissing('wallet_transactions', [
            'user_id' => $user->id,
            'transaction_type' => 'withdraw',
        ]);
        $this->assertSame('5000.00', (string) $user->fresh()->wallet);
    }

    public function test_withdraw_rejects_when_profile_name_and_display_name_are_both_empty(): void
    {
        // Fraud guard (bug B): with no profile first/last AND no display name,
        // there is nothing to verify the payout owner against, so withdrawal is
        // blocked with the profile_name_required code.
        $user = User::factory()->create(['wallet' => 5000, 'name' => '']);
        $user->profile()->create(['first_name' => '', 'last_name' => '']);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/wallet/withdraw', [
                'amount' => 100,
                'method' => 'bank_transfer',
                'bank_account' => [
                    'bank_name' => 'kbank',
                    'account_number' => '1234567890',
                    'account_name' => 'ใครก็ได้ นามสกุล',
                ],
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false, 'error_code' => 'profile_name_required']);

        $this->assertDatabaseMissing('wallet_transactions', [
            'user_id' => $user->id,
            'transaction_type' => 'withdraw',
        ]);
    }
}
