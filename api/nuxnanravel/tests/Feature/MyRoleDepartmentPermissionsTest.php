<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\AcademyGroupMember;
use App\Models\AcademyGroupPermission;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyRoleDepartmentPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_with_no_role_permissions_and_no_department_returns_empty_arrays(): void
    {
        [$academy, $user] = $this->academyWithDepartment([]);

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/my-role")
            ->assertOk()
            ->assertJson([
                'success' => true,
                'permissions' => [],
                'department_permissions' => [],
            ]);
    }

    public function test_member_in_department_with_enabled_permission_includes_it_in_both_arrays(): void
    {
        [$academy, $user] = $this->academyWithGroupPermission(true, 'guardians.view');

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/my-role")
            ->assertOk()
            ->assertJson([
                'success' => true,
                'permissions' => ['guardians.view'],
                'department_permissions' => ['guardians.view'],
            ]);
    }

    public function test_member_in_department_with_disabled_permission_excludes_it(): void
    {
        [$academy, $user] = $this->academyWithGroupPermission(false, 'guardians.view');

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/my-role")
            ->assertOk()
            ->assertJsonMissing([
                'permissions' => ['guardians.view'],
                'department_permissions' => ['guardians.view'],
            ]);
    }

    public function test_department_permission_from_another_academy_is_excluded(): void
    {
        [$academy, $user] = $this->academyWithDepartment([]);
        [$otherAcademy, , $group] = $this->academyWithDepartment([]);

        AcademyGroupMember::create(['academy_group_id' => $group->id, 'user_id' => $user->id, 'status' => 2]);
        AcademyGroupPermission::create(['academy_group_id' => $group->id, 'permission_key' => 'guardians.view', 'enabled' => true]);

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/my-role")
            ->assertOk()
            ->assertJsonMissing([
                'permissions' => ['guardians.view'],
                'department_permissions' => ['guardians.view'],
            ])
            ->assertJson([
                'success' => true,
                'permissions' => [],
                'department_permissions' => [],
            ]);
    }

    public function test_permissions_are_merged_without_duplicates_from_role_and_department(): void
    {
        [$academy, $user] = $this->academyWithDepartment(['members.view', 'guardians.view']);

        $group = AcademyGroup::create(['academy_id' => $academy->id, 'name' => 'Test Department', 'type' => 'department']);
        AcademyGroupMember::create(['academy_group_id' => $group->id, 'user_id' => $user->id, 'status' => 2]);
        AcademyGroupPermission::create(['academy_group_id' => $group->id, 'permission_key' => 'guardians.view', 'enabled' => true]);
        AcademyGroupPermission::create(['academy_group_id' => $group->id, 'permission_key' => 'guardians.manage', 'enabled' => true]);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/my-role")
            ->assertOk();

        $permissions = $response->json('permissions');
        $departmentPermissions = $response->json('department_permissions');

        $this->assertCount(3, $permissions);
        $this->assertContains('members.view', $permissions);
        $this->assertContains('guardians.view', $permissions);
        $this->assertContains('guardians.manage', $permissions);

        $this->assertCount(2, $departmentPermissions);
        $this->assertContains('guardians.view', $departmentPermissions);
        $this->assertContains('guardians.manage', $departmentPermissions);
    }

    private function academyWithDepartment(array $permissions = []): array
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

    private function academyWithGroupPermission(bool $enabled, string $key): array
    {
        [$academy, $user] = $this->academyWithDepartment([]);
        $group = AcademyGroup::create(['academy_id' => $academy->id, 'name' => 'Assigned department', 'type' => 'department']);
        AcademyGroupMember::create(['academy_group_id' => $group->id, 'user_id' => $user->id, 'status' => 2]);
        AcademyGroupPermission::create(['academy_group_id' => $group->id, 'permission_key' => $key, 'enabled' => $enabled]);

        return [$academy, $user];
    }
}
