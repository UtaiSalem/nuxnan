<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class WalletBalanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that user wallet balance endpoint returns correct JSON structure and values.
     */
    public function test_user_wallet_balance_endpoint_returns_correct_fields()
    {
        // 1. Create a user with a specific balance (e.g. 123.45)
        $user = User::factory()->create([
            'wallet' => 123.45,
        ]);

        // 2. Generate a JWT token for the user
        $token = JWTAuth::fromUser($user);

        // 3. Make GET request to the wallet balance endpoint
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/wallet/balance');

        // 4. Assert response is successful and matches the expected JSON API contract
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'cash_balance' => 123.45,
                    'reward_balance' => 0,
                    'total_balance' => 123.45,
                    'locked_balance' => 0,
                    'currency' => 'THB',
                ],
            ]);
    }
}
