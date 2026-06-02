<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserApprovalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles needed for the test
        Role::firstOrCreate(['name' => 'SUPER_ADMIN']);
        Role::firstOrCreate(['name' => 'STUDENT']);

        // Create admin
        $this->admin = User::factory()->create(['email' => 'admin@test.com']);
        $this->admin->assignRole('SUPER_ADMIN');
    }

    /** @test */
    public function pending_user_cannot_login()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(403)->assertJson(['error' => 'AccountPending']);
    }

    /** @test */
    public function verified_user_can_login()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    /** @test */
    public function admin_can_verify_user_and_allow_login()
    {
        $pending = User::factory()->create(['email_verified_at' => null]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/users/{$pending->id}/verify-email");

        $response->assertStatus(200);
        $pending->refresh();
        $this->assertNotNull($pending->email_verified_at);
    }

    /** @test */
    public function admin_can_bulk_verify_users()
    {
        $pendingUsers = User::factory()->count(3)->create(['email_verified_at' => null]);
        $ids = $pendingUsers->pluck('id')->toArray();

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/users/bulk-verify', ['user_ids' => $ids]);

        $response->assertStatus(200)->assertJsonPath('verified_count', 3);

        foreach ($pendingUsers as $user) {
            $this->assertNotNull($user->fresh()->email_verified_at);
        }
    }

    /** @test */
    public function non_admin_cannot_bulk_verify()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $pending = User::factory()->create(['email_verified_at' => null]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/admin/users/bulk-verify', ['user_ids' => [$pending->id]]);

        // Assuming 403 Forbidden for unauthorized permission
        $response->assertStatus(403);
    }
}
