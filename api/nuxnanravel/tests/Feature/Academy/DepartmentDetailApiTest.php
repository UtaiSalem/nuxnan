<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\AcademyGroupMember;
use App\Models\AcademyMember;
use App\Models\MemberActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentDetailApiTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $department = AcademyGroup::create(['academy_id' => $academy->id, 'name' => 'ฝ่ายทดสอบ', 'type' => 'department']);
        AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $owner->id, 'academy_role_id' => null, 'status' => 2]);
        AcademyGroupMember::create(['academy_group_id' => $department->id, 'user_id' => $owner->id, 'status' => 2]);

        return [$academy, $owner, $department];
    }

    public function test_show_without_flag_has_no_tree_key(): void
    {
        [$academy, $owner, $department] = $this->context();

        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/departments/{$department->id}");

        $response->assertOk();
        $response->assertJsonMissingPath('data.tree');
        $response->assertJsonStructure([
            'data' => [
                'department',
                'head',
            ],
        ]);
    }

    public function test_show_with_tree_returns_children_and_parent(): void
    {
        [$academy, $owner, $department] = $this->context();

        $office = AcademyGroup::create(['academy_id' => $academy->id, 'name' => 'สำนักงาน', 'type' => 'department']);
        $department->update(['parent_id' => $office->id]);

        $child1 = AcademyGroup::create(['academy_id' => $academy->id, 'name' => 'ลูก 1', 'type' => 'section', 'parent_id' => $department->id]);
        $child2 = AcademyGroup::create(['academy_id' => $academy->id, 'name' => 'ลูก 2', 'type' => 'academic_group', 'parent_id' => $department->id]);

        AcademyGroupMember::create(['academy_group_id' => $child1->id, 'user_id' => $owner->id, 'status' => 2]);

        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/departments/{$department->id}?with_tree=1");

        $response->assertOk();

        $response->assertJsonPath('data.tree.parent.name', 'สำนักงาน');

        // Children should be sorted by name: ลูก 1, ลูก 2
        $response->assertJsonPath('data.tree.children.0.name', 'ลูก 1');
        $response->assertJsonPath('data.tree.children.0.members_count', 1);
        $response->assertJsonPath('data.tree.children.1.name', 'ลูก 2');
        $response->assertJsonPath('data.tree.children.1.members_count', 0);
    }

    public function test_show_with_tree_excludes_children_of_other_academy(): void
    {
        [$academy, $owner, $department] = $this->context();

        $otherAcademy = Academy::factory()->create();
        AcademyGroup::create(['academy_id' => $otherAcademy->id, 'name' => 'ลูกปลอม', 'type' => 'section', 'parent_id' => $department->id]);

        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/departments/{$department->id}?with_tree=1");

        $response->assertOk();
        $response->assertJsonCount(0, 'data.tree.children');
    }

    public function test_activity_log_department_filter_returns_only_that_department(): void
    {
        [$academy, $owner, $departmentA] = $this->context();
        $departmentB = AcademyGroup::create(['academy_id' => $academy->id, 'name' => 'ฝ่าย B', 'type' => 'department']);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/departments/{$departmentA->id}/members/bulk", [
            'user_ids' => [$user1->id],
        ])->assertOk();

        $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/departments/{$departmentB->id}/members/bulk", [
            'user_ids' => [$user2->id],
        ])->assertOk();

        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/activity-log?department_id={$departmentA->id}&all=1");

        $response->assertOk();
        $logs = $response->json('logs');

        $this->assertCount(1, $logs);
        $this->assertEquals($departmentA->id, $logs[0]['new_values']['department_id']);
    }

    public function test_activity_log_department_filter_includes_permission_update(): void
    {
        [$academy, $owner, $departmentA] = $this->context();

        $this->actingAs($owner, 'api')->putJson("/api/academies/{$academy->id}/departments/{$departmentA->id}/permissions", [
            'permission_keys' => ['students.view', 'groups.view'],
        ])->assertOk();

        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/activity-log?department_id={$departmentA->id}&all=1");

        $response->assertOk();
        $logs = $response->json('logs');

        $this->assertCount(1, $logs);
        $this->assertEquals(MemberActivityLog::ACTION_DEPARTMENT_PERMISSION_UPDATE, $logs[0]['action']);
        $this->assertEquals($departmentA->id, $logs[0]['new_values']['department_id']);
    }

    public function test_activity_log_without_department_filter_is_unchanged(): void
    {
        [$academy, $owner, $department] = $this->context();

        MemberActivityLog::logActivity([
            'academy_id' => $academy->id,
            'action' => MemberActivityLog::ACTION_JOIN,
            'description' => 'User joined',
        ]);

        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/activity-log?all=1");

        $response->assertOk();

        $logs = collect($response->json('logs'));
        $this->assertTrue($logs->contains('action', MemberActivityLog::ACTION_JOIN));
    }
}
