<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyActivityLogGuardsTest extends TestCase
{
    use RefreshDatabase;

    private function setupAcademy(): array
    {
        foreach (AcademyRole::SYSTEM_ROLES as $name => $data) {
            AcademyRole::create(array_merge($data, [
                'academy_id' => null,
                'name' => $name,
                'is_system' => true,
                'is_active' => true,
            ]));
        }
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        return [$owner, $academy];
    }

    private function createMemberWithPermissions(Academy $academy, array $permissions): User
    {
        $role = AcademyRole::create([
            'academy_id' => $academy->id,
            'name' => fake()->unique()->lexify('role_????'),
            'display_name_th' => 'บทบาททดสอบ',
            'display_name_en' => 'Test role',
            'description' => 'desc',
            'permissions' => $permissions,
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 99,
            'color' => 'gray',
            'icon' => 'test',
        ]);

        $user = User::factory()->create();
        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $role->id,
            'role' => $role->name,
            'status' => 2, // APPROVED
        ]);

        return $user;
    }

    public function test_outsider_cannot_read_activity_log(): void
    {
        [$owner, $academy] = $this->setupAcademy();
        $outsider = User::factory()->create();

        $this->actingAs($outsider, 'api')
            ->getJson("/api/academies/{$academy->id}/activity-log")
            ->assertForbidden();
    }

    public function test_member_without_permission_cannot_read_activity_log(): void
    {
        [$owner, $academy] = $this->setupAcademy();
        $member = $this->createMemberWithPermissions($academy, []);

        $this->actingAs($member, 'api')
            ->getJson("/api/academies/{$academy->id}/activity-log")
            ->assertForbidden();
    }

    public function test_member_with_members_view_can_read_activity_log(): void
    {
        [$owner, $academy] = $this->setupAcademy();
        $member = $this->createMemberWithPermissions($academy, ['members.view']);

        $this->actingAs($member, 'api')
            ->getJson("/api/academies/{$academy->id}/activity-log")
            ->assertOk();
    }

    public function test_member_with_reports_view_can_read_activity_log(): void
    {
        [$owner, $academy] = $this->setupAcademy();
        $member = $this->createMemberWithPermissions($academy, ['reports.view']);

        $this->actingAs($member, 'api')
            ->getJson("/api/academies/{$academy->id}/activity-log")
            ->assertOk();
    }

    public function test_owner_can_read_activity_log(): void
    {
        [$owner, $academy] = $this->setupAcademy();

        $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/activity-log")
            ->assertOk();
    }

    public function test_statistics_endpoint_is_guarded_too(): void
    {
        [$owner, $academy] = $this->setupAcademy();
        $outsider = User::factory()->create();

        $this->actingAs($outsider, 'api')
            ->getJson("/api/academies/{$academy->id}/activity-log/statistics")
            ->assertForbidden();
    }
}
