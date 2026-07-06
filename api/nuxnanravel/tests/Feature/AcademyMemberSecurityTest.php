<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyMemberSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $tag = ''): User
    {
        return User::create([
            'name' => 'U'.$tag,
            'email' => 'u'.$tag.uniqid().'@x.test',
            'password' => bcrypt('password'),
            'username' => 'u'.$tag.uniqid(),
            'reference_code' => 'R'.uniqid(),
            'personal_code' => 'P'.uniqid(),
        ]);
    }

    public function test_user_can_access_their_own_membered_academies()
    {
        $user = $this->makeUser('me');
        $academy = Academy::create(['name' => 'My School', 'user_id' => $user->id]);

        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'status' => 2, // Approved
            'role' => 'student',
        ]);

        $response = $this->actingAs($user, 'api')
            ->json('GET', "/api/academies/users/{$user->id}/membered-academies");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_user_cannot_access_others_membered_academies()
    {
        $userA = $this->makeUser('userA');
        $userB = $this->makeUser('userB');
        $academy = Academy::create(['name' => 'Another School', 'user_id' => $userB->id]);

        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $userB->id,
            'status' => 2, // Approved
            'role' => 'student',
        ]);

        // Access User B's endpoint as User A
        $response = $this->actingAs($userA, 'api')
            ->json('GET', "/api/academies/users/{$userB->id}/membered-academies");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized access.',
            ]);
    }
}
