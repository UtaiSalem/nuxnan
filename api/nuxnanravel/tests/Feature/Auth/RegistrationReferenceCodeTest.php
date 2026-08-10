<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationReferenceCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_multiple_users_can_register_with_admin_referral_code(): void
    {
        $firstResponse = $this->postJson('/api/register', [
            'username' => 'firstuser',
            'email' => 'first@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'reference_code' => '11111111',
        ]);

        $secondResponse = $this->postJson('/api/register', [
            'username' => 'seconduser',
            'email' => 'second@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'reference_code' => '11111111',
        ]);

        $firstResponse->assertOk();
        $secondResponse->assertOk();

        $firstUser = User::where('email', 'first@example.com')->firstOrFail();
        $secondUser = User::where('email', 'second@example.com')->firstOrFail();

        $this->assertSame('11111111', $firstUser->suggester_code);
        $this->assertSame('11111111', $secondUser->suggester_code);
        $this->assertNotSame($firstUser->reference_code, $secondUser->reference_code);
    }

    public function test_normal_suggester_code_is_limited(): void
    {
        $referrer = User::factory()->create([
            'personal_code' => '22222222',
            'reference_code' => User::generateReferenceCode(),
            'no_of_ref' => User::MAX_REFERRALS_PER_SUGGESTER - 1,
        ]);

        $allowedResponse = $this->postJson('/api/register', [
            'username' => 'alloweduser',
            'email' => 'allowed@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'reference_code' => '22222222',
        ]);

        $blockedResponse = $this->postJson('/api/register', [
            'username' => 'blockeduser',
            'email' => 'blocked@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'reference_code' => '22222222',
        ]);

        $allowedResponse->assertOk();
        $blockedResponse->assertUnprocessable();

        $this->assertSame(User::MAX_REFERRALS_PER_SUGGESTER, $referrer->fresh()->no_of_ref);
        $this->assertDatabaseHas('users', [
            'email' => 'allowed@example.com',
            'suggester_code' => '22222222',
        ]);
        $this->assertDatabaseMissing('users', [
            'email' => 'blocked@example.com',
        ]);
    }
}
