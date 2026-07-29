<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\AcademyGroupMember;
use App\Models\AcademyGroupPermission;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_member_cannot_delete_department(): void
    {
        [$academy, , $department] = $this->academyWithDepartment();
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->deleteJson("/api/academies/{$academy->id}/departments/{$department->id}")
            ->assertForbidden();
    }

    public function test_member_without_groups_manage_cannot_create_or_delete_department(): void
    {
        [$academy, $user, $department] = $this->academyWithDepartment(['groups.view']);

        $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/departments", ['name' => 'Blocked'])
            ->assertForbidden();

        $this->actingAs($user, 'api')
            ->deleteJson("/api/academies/{$academy->id}/departments/{$department->id}")
            ->assertForbidden();
    }

    public function test_academy_owner_can_create_update_and_delete_department(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        $created = $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/departments", ['name' => 'Created'])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($owner, 'api')
            ->patchJson("/api/academies/{$academy->id}/departments/{$created}", ['name' => 'Updated'])
            ->assertOk();

        $this->actingAs($owner, 'api')
            ->deleteJson("/api/academies/{$academy->id}/departments/{$created}")
            ->assertOk();
    }

    public function test_member_with_groups_view_can_list_departments(): void
    {
        [$academy, $user] = $this->academyWithDepartment(['groups.view']);

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/departments")
            ->assertOk();
    }

    public function test_group_member_without_permission_row_is_denied(): void
    {
        [$academy, $user] = $this->academyWithGroupPermission(null);

        $this->actingAs($user, 'api')->getJson("/api/academies/{$academy->id}/departments")
            ->assertForbidden();
    }

    public function test_group_member_with_disabled_permission_is_denied(): void
    {
        [$academy, $user] = $this->academyWithGroupPermission(false);

        $this->actingAs($user, 'api')->getJson("/api/academies/{$academy->id}/departments")
            ->assertForbidden();
    }

    public function test_group_member_with_enabled_permission_is_allowed(): void
    {
        [$academy, $user] = $this->academyWithGroupPermission(true);

        $this->actingAs($user, 'api')->getJson("/api/academies/{$academy->id}/departments")
            ->assertOk();
    }

    public function test_group_permission_from_another_academy_is_denied(): void
    {
        [$academy, $user] = $this->academyWithGroupPermission(null);
        [$otherAcademy, , $group] = $this->academyWithDepartment();
        AcademyGroupMember::create(['academy_group_id' => $group->id, 'user_id' => $user->id, 'status' => 2]);
        AcademyGroupPermission::create(['academy_group_id' => $group->id, 'permission_key' => 'groups.view', 'enabled' => true]);

        $this->actingAs($user, 'api')->getJson("/api/academies/{$academy->id}/departments")
            ->assertForbidden();
    }

    public function test_member_without_group_or_role_permission_remains_denied(): void
    {
        [$academy, $user] = $this->academyWithDepartment([]);

        $this->actingAs($user, 'api')->getJson("/api/academies/{$academy->id}/departments")
            ->assertForbidden();
    }

    public function test_academy_owner_remains_allowed(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner, 'api')->getJson("/api/academies/{$academy->id}/departments")
            ->assertOk();
    }

    public function test_member_without_role_with_enabled_group_permission_is_allowed(): void
    {
        [$academy, $user] = $this->academyWithNullRole();
        $group = AcademyGroup::create(['academy_id' => $academy->id, 'name' => 'Assigned department', 'type' => 'department']);
        AcademyGroupMember::create(['academy_group_id' => $group->id, 'user_id' => $user->id, 'status' => 2]);
        AcademyGroupPermission::create(['academy_group_id' => $group->id, 'permission_key' => 'groups.view', 'enabled' => true]);

        $this->actingAs($user, 'api')->getJson("/api/academies/{$academy->id}/departments")
            ->assertOk();
    }

    public function test_member_without_role_and_without_department_is_denied(): void
    {
        [$academy, $user] = $this->academyWithNullRole();

        $this->actingAs($user, 'api')->getJson("/api/academies/{$academy->id}/departments")
            ->assertForbidden();
    }

    public function test_member_without_role_with_disabled_group_permission_is_denied(): void
    {
        [$academy, $user] = $this->academyWithNullRole();
        $group = AcademyGroup::create(['academy_id' => $academy->id, 'name' => 'Assigned department', 'type' => 'department']);
        AcademyGroupMember::create(['academy_group_id' => $group->id, 'user_id' => $user->id, 'status' => 2]);
        AcademyGroupPermission::create(['academy_group_id' => $group->id, 'permission_key' => 'groups.view', 'enabled' => false]);

        $this->actingAs($user, 'api')->getJson("/api/academies/{$academy->id}/departments")
            ->assertForbidden();
    }

    private function academyWithNullRole(): array
    {
        $user = User::factory()->create();
        $academy = Academy::factory()->create();
        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'academy_role_id' => null,
            'status' => 2,
        ]);

        return [$academy, $user];
    }

    private function academyWithDepartment(array $permissions = ['groups.view', 'groups.manage']): array
    {
        $user = User::factory()->create();
        $academy = Academy::factory()->create();
        $role = AcademyRole::create([
            'academy_id' => $academy->id,
            'name' => 'test-role-'.uniqid(),
            'display_name_th' => 'Test role',
            'permissions' => $permissions,
        ]);
        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $role->id,
            'status' => 2,
        ]);
        $department = AcademyGroup::create([
            'academy_id' => $academy->id,
            'name' => 'Test department',
            'type' => 'department',
        ]);

        return [$academy, $user, $department];
    }

    private function academyWithGroupPermission(?bool $enabled): array
    {
        [$academy, $user] = $this->academyWithDepartment([]);
        $group = AcademyGroup::create(['academy_id' => $academy->id, 'name' => 'Assigned department', 'type' => 'department']);
        AcademyGroupMember::create(['academy_group_id' => $group->id, 'user_id' => $user->id, 'status' => 2]);

        if ($enabled !== null) {
            AcademyGroupPermission::create(['academy_group_id' => $group->id, 'permission_key' => 'groups.view', 'enabled' => $enabled]);
        }

        return [$academy, $user];
    }
}
