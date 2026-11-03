<?php

namespace Tests\Feature;

use App\Models\PointsTransaction;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class SelfMintEndpointsClosedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function createVerifiedUser(): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            'pp' => 0,
            'wallet' => 0,
            'xp' => 0,
        ]);
    }

    public function test_wallet_deposit_route_is_gone(): void
    {
        $user = $this->createVerifiedUser();

        $res = $this->withHeader('Authorization', 'Bearer '.JWTAuth::fromUser($user))
            ->postJson('/api/wallet/deposit', [
                'amount' => 1000,
                'method' => 'x',
            ]);

        $this->assertTrue(in_array($res->status(), [404, 405]));
        $this->assertEquals(0.0, (float) $user->fresh()->wallet);
        $this->assertEquals(0, WalletTransaction::where('user_id', $user->id)->count());
    }

    public function test_points_refund_route_is_gone(): void
    {
        $user = $this->createVerifiedUser();

        $res = $this->withHeader('Authorization', 'Bearer '.JWTAuth::fromUser($user))
            ->postJson('/api/points/refund', [
                'amount' => 1000,
            ]);

        $this->assertTrue(in_array($res->status(), [404, 405]));
        $this->assertEquals(0.0, (float) $user->fresh()->pp);
        $this->assertEquals(0, PointsTransaction::where('user_id', $user->id)->count());
    }

    public function test_xp_earn_rejects_unknown_source(): void
    {
        $user = $this->createVerifiedUser();

        $res = $this->withHeader('Authorization', 'Bearer '.JWTAuth::fromUser($user))
            ->postJson('/api/points/xp/earn', [
                'source_type' => 'anything',
                'amount' => 10,
            ]);

        $res->assertStatus(422);
        $this->assertEquals(0, (int) $user->fresh()->xp);
    }

    public function test_xp_earn_rejects_amount_over_per_call_cap(): void
    {
        $user = $this->createVerifiedUser();

        $res = $this->withHeader('Authorization', 'Bearer '.JWTAuth::fromUser($user))
            ->postJson('/api/points/xp/earn', [
                'source_type' => 'game_snake',
                'amount' => 101,
            ]);

        $res->assertStatus(422);
        $this->assertEquals(0, (int) $user->fresh()->xp);
    }

    public function test_xp_earn_grants_game_xp(): void
    {
        $user = $this->createVerifiedUser();

        $res = $this->withHeader('Authorization', 'Bearer '.JWTAuth::fromUser($user))
            ->postJson('/api/points/xp/earn', [
                'source_type' => 'game_snake',
                'amount' => 100,
            ]);

        $res->assertStatus(200);
        $this->assertEquals(100, (int) $res->json('data.xp_earned'));
        $this->assertEquals(100, (int) $user->fresh()->xp);
    }

    public function test_xp_earn_daily_cap(): void
    {
        $user = $this->createVerifiedUser();
        $token = JWTAuth::fromUser($user);

        $lastRes = null;
        for ($i = 0; $i < 6; $i++) {
            $lastRes = $this->withHeader('Authorization', 'Bearer '.$token)
                ->postJson('/api/points/xp/earn', [
                    'source_type' => 'game_snake',
                    'amount' => 100,
                ]);
            $lastRes->assertStatus(200);
        }

        $this->assertEquals(0, (int) $lastRes->json('data.xp_earned'));
        $this->assertEquals(500, (int) $user->fresh()->xp);
    }

    public function test_xp_daily_cap_is_per_source(): void
    {
        $user = $this->createVerifiedUser();
        $token = JWTAuth::fromUser($user);

        for ($i = 0; $i < 5; $i++) {
            $this->withHeader('Authorization', 'Bearer '.$token)
                ->postJson('/api/points/xp/earn', [
                    'source_type' => 'game_snake',
                    'amount' => 100,
                ])
                ->assertStatus(200);
        }

        $res = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/points/xp/earn', [
                'source_type' => 'english_vocab_match',
                'amount' => 40,
            ]);

        $res->assertStatus(200);
        $this->assertEquals(40, (int) $res->json('data.xp_earned'));
        $this->assertEquals(540, (int) $user->fresh()->xp);
    }

    public function test_deposit_request_route_still_exists(): void
    {
        $user = $this->createVerifiedUser();

        $res = $this->withHeader('Authorization', 'Bearer '.JWTAuth::fromUser($user))
            ->postJson('/api/wallet/deposit-request', []);

        $this->assertFalse(in_array($res->status(), [404, 405]));
    }
}
