<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\Academy;
use App\Models\PointsTransaction;
use App\Services\PointsService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletAndPointsTest extends TestCase
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

    public function test_user_can_earn_points()
    {
        $user = User::factory()->create(['pp' => 0]);
        
        $this->pointsService->earn($user, 100, 'test_earn', null, 'Test earning');

        $this->assertEquals(100, $user->fresh()->pp);
        $this->assertDatabaseHas('points_transactions', [
            'user_id' => $user->id,
            'amount' => 100,
            'transaction_type' => 'earn'
        ]);
    }

    public function test_user_can_spend_points()
    {
        $user = User::factory()->create(['pp' => 200]);
        
        $result = $this->pointsService->spend($user, 50, 'test_spend', null, 'Test spending');

        $this->assertNotNull($result);
        $this->assertEquals(150, $user->fresh()->pp);
        $this->assertDatabaseHas('points_transactions', [
            'user_id' => $user->id,
            'amount' => 50,
            'transaction_type' => 'spend'
        ]);
    }

    public function test_user_cannot_spend_more_than_balance()
    {
        $user = User::factory()->create(['pp' => 30]);
        
        $result = $this->pointsService->spend($user, 50, 'test_spend');

        $this->assertNull($result);
        $this->assertEquals(30, $user->fresh()->pp);
    }

    public function test_wallet_deduction_endpoint()
    {
        $user = User::factory()->create(['wallet' => 500]);
        $token = \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::fromUser($user);
        
        $response = $this->withHeader('Authorization', "Bearer $token")
                 ->postJson('/api/wallet/deduct', [
            'amount' => 100,
            'reason' => 'Test deduction'
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);

        $this->assertEquals(400, $user->fresh()->wallet);
    }

    public function test_wallet_deduction_fails_if_insufficient_balance()
    {
        $user = User::factory()->create(['wallet' => 50]);
        $token = \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::fromUser($user);
        
        $response = $this->withHeader('Authorization', "Bearer $token")
                 ->postJson('/api/wallet/deduct', [
            'amount' => 100,
            'reason' => 'Test deduction'
        ]);

        $response->assertStatus(400)
                 ->assertJsonPath('success', false);

        $this->assertEquals(50, $user->fresh()->wallet);
    }
}
