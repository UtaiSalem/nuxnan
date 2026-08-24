<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\AcademyGroupMember;
use App\Models\AcademyMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AcademyMemberDepartmentMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_memberships_are_absent_without_opt_in(): void
    {
        [$academy, $actor, $member] = $this->fixture();

        $response = $this->actingAs($actor, 'api')->getJson($this->url($academy));

        $response->assertOk();
        $this->assertArrayNotHasKey('department_memberships', $response->json('members.0'));
    }

    public function test_memberships_are_returned_with_opt_in(): void
    {
        [$academy, $actor, $member] = $this->fixture();
        $department = AcademyGroup::create(['academy_id' => $academy->id, 'name' => 'ฝ่ายวิชาการ', 'type' => 'department']);
        AcademyGroupMember::create(['academy_group_id' => $department->id, 'user_id' => $member->user_id, 'role' => 'head']);

        $row = $this->actingAs($actor, 'api')->getJson($this->url($academy).'?with_departments=1')->assertOk()->json('members.0');

        $this->assertSame([['id' => $department->id, 'name' => 'ฝ่ายวิชาการ', 'role' => 'head']], $row['department_memberships']);
    }

    public function test_non_department_membership_is_excluded(): void
    {
        [$academy, $actor, $member] = $this->fixture();
        $club = AcademyGroup::create(['academy_id' => $academy->id, 'name' => 'Club', 'type' => 'club']);
        AcademyGroupMember::create(['academy_group_id' => $club->id, 'user_id' => $member->user_id, 'role' => 'member']);

        $row = $this->actingAs($actor, 'api')->getJson($this->url($academy).'?with_departments=1')->assertOk()->json('members.0');

        $this->assertSame([], $row['department_memberships']);
    }

    public function test_member_without_departments_gets_empty_array(): void
    {
        [$academy, $actor] = $this->fixture();

        $row = $this->actingAs($actor, 'api')->getJson($this->url($academy).'?with_departments=1')->assertOk()->json('members.0');

        $this->assertSame([], $row['department_memberships']);
    }

    public function test_membership_query_count_does_not_grow_with_page_size(): void
    {
        [$academy, $actor] = $this->fixture();
        foreach (range(1, 49) as $i) {
            AcademyMember::create(['academy_id' => $academy->id, 'user_id' => User::factory()->create()->id, 'status' => 2]);
        }

        DB::enableQueryLog();
        $this->actingAs($actor, 'api')->getJson($this->url($academy).'?with_departments=1&per_page=5')->assertOk();
        $fiveQueries = count(DB::getQueryLog());
        DB::flushQueryLog();
        $this->actingAs($actor, 'api')->getJson($this->url($academy).'?with_departments=1&per_page=50')->assertOk();
        $fiftyQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($fiveQueries, $fiftyQueries);
    }

    private function fixture(): array
    {
        $actor = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $actor->id]);
        $member = AcademyMember::create(['academy_id' => $academy->id, 'user_id' => User::factory()->create()->id, 'status' => 2]);

        return [$academy, $actor, $member];
    }

    private function url(Academy $academy): string
    {
        return "/api/academies/{$academy->id}/members/search";
    }
}
