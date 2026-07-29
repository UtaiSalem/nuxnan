<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyGroup;
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
}
