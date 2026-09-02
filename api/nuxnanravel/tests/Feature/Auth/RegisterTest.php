<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/register', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'username' => 'สมชาย ใจดี',
            'reference_code' => '11111111', // User::ADMIN_SUGGESTER_CODE
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'pending_approval',
            ])
            ->assertJsonStructure([
                'success',
                'status',
                'message',
                'user' => [
                    'id',
                    'username',
                    'email',
                ],
            ])
            ->assertJsonMissing(['access_token'])
            ->assertJsonMissing(['token_type']);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'username' => 'สมชาย ใจดี',
            'name' => 'สมชาย ใจดี',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        // $this->assertTrue($user->hasRole('STUDENT'));
    }

    public function test_registration_validation()
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'email',
                    'password',
                    'username',
                ],
            ]);
    }

    public function test_register_does_not_issue_a_token_and_account_stays_pending()
    {
        $response = $this->postJson('/api/register', [
            'email' => 'pending@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'username' => 'Pending User',
            'reference_code' => '11111111',
        ]);

        $response->assertStatus(200);
        $response->assertJsonMissing(['access_token']);
        $this->assertArrayNotHasKey('access_token', $response->json());

        $user = User::where('email', 'pending@example.com')->first();
        $this->assertNull($user->email_verified_at);
    }

    public function test_pending_account_cannot_log_in_yet()
    {
        $this->postJson('/api/register', [
            'email' => 'login_pending@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'username' => 'Login Pending User',
            'reference_code' => '11111111',
        ]);

        $response = $this->postJson('/api/login', [
            'login' => 'login_pending@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson(['error' => 'AccountPending']);
    }
}
