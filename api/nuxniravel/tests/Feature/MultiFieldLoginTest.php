<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MultiFieldLoginTest extends TestCase
{
    /**
     * Test login with username (name field)
     */
    public function test_login_with_username(): void
    {
        $user = User::factory()->create([
            'name' => 'testuser',
            'email' => 'test@example.com',
            'phone_number' => '0812345678',
            'personal_code' => '12345678',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'login' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => ['token', 'user']
                 ]);
    }

    /**
     * Test login with email
     */
    public function test_login_with_email(): void
    {
        $user = User::factory()->create([
            'name' => 'testuser2',
            'email' => 'test2@example.com',
            'phone_number' => '0823456789',
            'personal_code' => '23456789',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'login' => 'test2@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => ['token', 'user']
                 ]);
    }

    /**
     * Test login with phone number
     */
    public function test_login_with_phone_number(): void
    {
        $user = User::factory()->create([
            'name' => 'testuser3',
            'email' => 'test3@example.com',
            'phone_number' => '0834567890',
            'personal_code' => '34567890',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'login' => '0834567890',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => ['token', 'user']
                 ]);
    }

    /**
     * Test login with personal code
     */
    public function test_login_with_personal_code(): void
    {
        $user = User::factory()->create([
            'name' => 'testuser4',
            'email' => 'test4@example.com',
            'phone_number' => '0845678901',
            'personal_code' => '45678901',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'login' => '45678901',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => ['token', 'user']
                 ]);
    }

    /**
     * Test login with invalid credentials
     */
    public function test_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/login', [
            'login' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Invalid credentials'
                 ]);
    }
}
