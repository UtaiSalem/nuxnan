<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_roles_are_seeded_correctly()
    {
        $this->assertDatabaseHas('roles', ['name' => 'ADMIN']);
        $this->assertDatabaseHas('roles', ['name' => 'INSTRUCTOR']);
        $this->assertDatabaseHas('roles', ['name' => 'VENDOR']);
        $this->assertDatabaseHas('roles', ['name' => 'STUDENT']);
    }

    public function test_new_user_is_assigned_student_role()
    {
        $response = $this->postJson('/api/register', [
            'username' => 'studentuser',
            'email' => 'student@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(200);

        $user = User::where('email', 'student@example.com')->first();
        $this->assertTrue($user->hasRole('STUDENT'));
        $this->assertFalse($user->hasRole('ADMIN'));
    }
}
