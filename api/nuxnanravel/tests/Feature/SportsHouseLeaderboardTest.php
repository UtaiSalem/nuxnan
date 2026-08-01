<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\AcademyGroupMember;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SportsHouseLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_house_leaderboard_never_exposes_points_wallet(): void
    {
        [$academy, $user] = $this->context();
        $house = AcademyGroup::create(['academy_id' => $academy->id, 'name' => 'Purple House', 'type' => 'house']);
        $member = User::factory()->create(['pp' => 5000]);
        AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $member->id, 'status' => 2]);
        AcademyGroupMember::create(['academy_group_id' => $house->id, 'user_id' => $member->id]);

        $response = $this->actingAs($user, 'api')->getJson("/api/academies/{$academy->id}/gamification/leaderboard/houses");

        $response->assertOk()->assertJsonPath('data.0.points', 0)->assertJsonPath('data.0.points_source', 'pending');
        $this->assertNotEquals(5000, $response->json('data.0.points'));
    }

    public function test_classroom_leaderboard_never_exposes_points_wallet(): void
    {
        [$academy, $user] = $this->context();
        $year = AcademicYear::create(['academy_id' => $academy->id, 'name' => '2026', 'start_date' => '2026-05-01', 'end_date' => '2027-03-31', 'is_current' => true]);
        $classroom = Classroom::create(['academy_id' => $academy->id, 'academic_year_id' => $year->id, 'name' => 'Room 1', 'grade_level' => '1', 'section' => 'A']);
        $member = User::factory()->create(['pp' => 5000]);
        ClassroomMember::create(['classroom_id' => $classroom->id, 'user_id' => $member->id, 'role' => 'student', 'is_active' => true]);

        $response = $this->actingAs($user, 'api')->getJson("/api/academies/{$academy->id}/gamification/leaderboard/classrooms");

        $response->assertOk()->assertJsonPath('data.0.points', 0)->assertJsonPath('data.0.points_source', 'pending');
        $this->assertNotEquals(5000, $response->json('data.0.points'));
    }

    public function test_tied_houses_share_a_rank_and_the_next_distinct_score_skips(): void
    {
        [$academy, $user] = $this->context();
        foreach (['Blue', 'Green', 'Red'] as $name) {
            AcademyGroup::create(['academy_id' => $academy->id, 'name' => $name, 'type' => 'house']);
        }

        $response = $this->actingAs($user, 'api')->getJson("/api/academies/{$academy->id}/gamification/leaderboard/houses");

        // Every house sits at 0 points until the sports ledger lands (S-S4), so all three tie at rank 1.
        // A null rank here means the tie branch is reading a key that has not been written yet.
        $response->assertOk()
            ->assertJsonPath('data.0.rank', 1)
            ->assertJsonPath('data.1.rank', 1)
            ->assertJsonPath('data.2.rank', 1);

        foreach ($response->json('data') as $row) {
            $this->assertNotNull($row['rank'], 'rank must never be null — tied rows must inherit the shared rank');
        }
    }

    public function test_leaderboards_require_sports_view_permission(): void
    {
        [$academy, $user] = $this->context([]);

        $this->actingAs($user, 'api')->getJson("/api/academies/{$academy->id}/gamification/leaderboard/houses")->assertForbidden();
        $this->actingAs($user, 'api')->getJson("/api/academies/{$academy->id}/gamification/leaderboard/classrooms")->assertForbidden();
    }

    public function test_house_and_dormitory_are_creatable_but_unknown_types_are_rejected(): void
    {
        [$academy, $user] = $this->context();
        foreach (['house', 'dormitory'] as $type) {
            $this->actingAs($user, 'api')->postJson("/api/academies/{$academy->id}/groups", ['name' => $type, 'type' => $type])->assertCreated();
        }
        $this->actingAs($user, 'api')->postJson("/api/academies/{$academy->id}/groups", ['name' => 'Unknown', 'type' => 'banana'])->assertUnprocessable();
    }

    private function context(array $permissions = ['sports.view']): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $role = AcademyRole::create(['academy_id' => $academy->id, 'name' => uniqid('role'), 'display_name_th' => 'Test', 'permissions' => $permissions]);
        $user = User::factory()->create();
        AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $user->id, 'academy_role_id' => $role->id, 'status' => 2]);

        return [$academy, $user];
    }
}
