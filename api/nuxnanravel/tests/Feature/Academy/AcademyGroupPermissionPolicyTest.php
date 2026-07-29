<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\AcademyGroupMember;
use App\Models\AcademyGroupPermission;
use App\Models\AcademyMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyGroupPermissionPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_manage_is_rejected_without_persisting_any_row(): void
    {
        [$academy, $group] = $this->group();
        $this->actingAs($academy->user, 'api')->putJson("/api/academies/{$academy->id}/departments/{$group->id}/permissions", ['permission_keys' => ['students.view', 'roles.manage']])->assertStatus(422);
        $this->assertDatabaseCount('academy_group_permissions', 0);
    }

    public function test_settings_manage_is_rejected(): void
    {
        [$academy, $group] = $this->group();
        $this->actingAs($academy->user, 'api')->putJson("/api/academies/{$academy->id}/departments/{$group->id}/permissions", ['permission_keys' => ['settings.manage']])->assertStatus(422);
    }

    public function test_students_view_is_saved_and_grants_access(): void
    {
        [$academy, $group, $member] = $this->group();
        $this->actingAs($academy->user, 'api')->putJson("/api/academies/{$academy->id}/departments/{$group->id}/permissions", ['permission_keys' => ['students.view']])->assertOk();
        $this->actingAs($member, 'api')->getJson("/api/academies/{$academy->id}/student-intakes/list")->assertOk();
    }

    public function test_raw_roles_manage_row_is_refused_at_access_layer(): void
    {
        [$academy, $group, $member] = $this->group();
        AcademyGroupPermission::create(['academy_group_id' => $group->id, 'permission_key' => 'roles.manage', 'enabled' => true]);
        $this->actingAs($member, 'api')->postJson("/api/academies/{$academy->id}/roles", ['name' => 'Nope', 'display_name_th' => 'Nope', 'permissions' => []])->assertForbidden();
    }

    public function test_groups_view_is_allowed_but_groups_manage_is_rejected(): void
    {
        [$academy, $group] = $this->group();
        $this->actingAs($academy->user, 'api')->putJson("/api/academies/{$academy->id}/departments/{$group->id}/permissions", ['permission_keys' => ['groups.view']])->assertOk();
        $this->actingAs($academy->user, 'api')->putJson("/api/academies/{$academy->id}/departments/{$group->id}/permissions", ['permission_keys' => ['groups.manage']])->assertStatus(422);
    }

    private function group(): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $member = User::factory()->create();
        AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $member->id, 'academy_role_id' => null, 'status' => 2]);
        $group = AcademyGroup::create(['academy_id' => $academy->id, 'name' => 'Department', 'type' => 'department']);
        AcademyGroupMember::create(['academy_group_id' => $group->id, 'user_id' => $member->id, 'status' => 2]);

        return [$academy, $group, $member];
    }
}
