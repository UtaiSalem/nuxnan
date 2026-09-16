<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyMemberFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_filter_academy_members()
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        $teacherUser = User::factory()->create(['name' => 'Kru Preecha']);
        $studentUser = User::factory()->create(['name' => 'Somchai Jaidee']);
        $otherUser = User::factory()->create(['name' => 'Anonymous Person']);

        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $teacherUser->id,
            'role' => 'teacher',
            'status' => 2,
        ]);

        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $studentUser->id,
            'role' => 'student',
            'status' => 2,
        ]);

        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $otherUser->id,
            'role' => 'student',
            'status' => 0,
        ]);

        // Case 1: No filters
        $response = $this->actingAs($owner, 'api')->getJson("/api/academies/{$academy->id}/members");
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'members');

        // Case 2: ?role=teacher
        $response = $this->actingAs($owner, 'api')->getJson("/api/academies/{$academy->id}/members?role=teacher");
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'members');
        $this->assertEquals('teacher', $response->json('members.0.role'));

        // Case 3: ?status=2
        $response = $this->actingAs($owner, 'api')->getJson("/api/academies/{$academy->id}/members?status=2");
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'members');

        // Case 4: ?search=Somchai
        $response = $this->actingAs($owner, 'api')->getJson("/api/academies/{$academy->id}/members?search=Somchai");
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'members');
        $this->assertEquals('Somchai Jaidee', $response->json('members.0.member_name'));

        // Case: per_page cap at 200
        $response = $this->actingAs($owner, 'api')->getJson("/api/academies/{$academy->id}/members?per_page=500");
        $response->assertStatus(200);
        $this->assertEquals(200, $response->json('pagination.per_page'));
    }
}
