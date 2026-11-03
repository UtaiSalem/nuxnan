<?php

namespace Tests\Feature;

use App\Models\PlearndAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class ForgotPasswordExchangeMoneyTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        PlearndAdmin::create(['user_id' => $admin->id]);

        return $admin;
    }

    private function makeUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['email_verified_at' => now()], $attributes));
    }

    private function actingWithJwt(User $actor)
    {
        return $this->withHeader('Authorization', 'Bearer '.JWTAuth::fromUser($actor));
    }

    public function test_non_admin_cannot_exchange_money()
    {
        $actor = $this->makeUser();
        $target = $this->makeUser(['pp' => 0]);

        $this->actingWithJwt($actor)
            ->postJson("/api/forgot-password/exchange/{$target->id}", ['money' => 50])
            ->assertStatus(403);

        $this->assertEquals(0, (float) $target->fresh()->pp);
        $this->assertDatabaseCount('points_transactions', 0);
        $this->assertDatabaseMissing('points_transactions', ['user_id' => $target->id]);
    }

    public function test_non_admin_cannot_exchange_money_for_self()
    {
        $actor = $this->makeUser(['pp' => 10]);

        $this->actingWithJwt($actor)
            ->postJson("/api/forgot-password/exchange/{$actor->id}", ['money' => 50])
            ->assertStatus(403);

        $this->assertEquals(10, (float) $actor->fresh()->pp);
        $this->assertDatabaseMissing('points_transactions', ['user_id' => $actor->id]);
    }

    public function test_admin_can_exchange_up_to_100_baht()
    {
        $admin = $this->makeAdmin();
        $target = $this->makeUser(['pp' => 0]);

        $this->actingWithJwt($admin)
            ->postJson("/api/forgot-password/exchange/{$target->id}", ['money' => 100])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals(108000, (float) $target->fresh()->pp);
        $this->assertDatabaseHas('points_transactions', [
            'user_id' => $target->id,
            'transaction_type' => 'admin_adjust',
            'amount' => 108000,
        ]);
    }

    public function test_admin_cannot_exceed_100_baht()
    {
        $admin = $this->makeAdmin();
        $target = $this->makeUser(['pp' => 0]);

        $this->actingWithJwt($admin)
            ->postJson("/api/forgot-password/exchange/{$target->id}", ['money' => 100.01])
            ->assertStatus(422);

        $this->assertEquals(0, (float) $target->fresh()->pp);
        $this->assertDatabaseMissing('points_transactions', ['user_id' => $target->id]);
    }

    public function test_admin_cannot_send_zero_or_negative()
    {
        $admin = $this->makeAdmin();
        $target = $this->makeUser(['pp' => 0]);

        $this->actingWithJwt($admin)
            ->postJson("/api/forgot-password/exchange/{$target->id}", ['money' => 0])
            ->assertStatus(422);

        $this->actingWithJwt($admin)
            ->postJson("/api/forgot-password/exchange/{$target->id}", ['money' => -5])
            ->assertStatus(422);

        $this->assertEquals(0, (float) $target->fresh()->pp);
        $this->assertDatabaseMissing('points_transactions', ['user_id' => $target->id]);
    }

    public function test_non_admin_cannot_search_users()
    {
        $actor = $this->makeUser();

        $this->actingWithJwt($actor)
            ->postJson('/api/forgot-password/getuser', ['email' => 'a'])
            ->assertStatus(403);
    }

    public function test_admin_can_search_users()
    {
        $admin = $this->makeAdmin();
        $target = $this->makeUser();

        $this->actingWithJwt($admin)
            ->postJson('/api/forgot-password/getuser', ['email' => $target->email])
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
