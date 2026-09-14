<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CL-S4 — เมนู #10 ห้องเรียน: ด่านสิทธิ์ย้ายมาใช้ Academy::userCan (groups.view/groups.manage)
 * พิสูจน์ว่า middleware groups.view + controller groups.manage ทำงานตรง Q1–Q3
 */
class ClassroomPermissionGuardTest extends TestCase
{
    use RefreshDatabase;

    private function memberWith(Academy $academy, array $permissions, int $status = 2): User
    {
        $user = User::factory()->create();
        $role = AcademyRole::create([
            'academy_id' => $academy->id,
            'name' => 'role-'.uniqid(),
            'display_name_th' => 'role',
            'permissions' => $permissions,
        ]);
        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $role->id,
            'status' => $status,
        ]);

        return $user;
    }

    private function storeBody(): array
    {
        return ['academic_year' => '2568', 'grade_level' => 'ม.1', 'section' => '1'];
    }

    public function test_non_member_cannot_read_or_write_classrooms(): void
    {
        $academy = Academy::factory()->create();
        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'api')
            ->getJson("/api/academies/{$academy->id}/classrooms")
            ->assertStatus(403);

        $this->actingAs($stranger, 'api')
            ->postJson("/api/academies/{$academy->id}/classrooms", $this->storeBody())
            ->assertStatus(403);
    }

    public function test_groups_view_member_can_read_but_not_manage(): void
    {
        $academy = Academy::factory()->create();
        $viewer = $this->memberWith($academy, ['groups.view']);

        $this->actingAs($viewer, 'api')
            ->getJson("/api/academies/{$academy->id}/classrooms")
            ->assertStatus(200);

        $this->actingAs($viewer, 'api')
            ->postJson("/api/academies/{$academy->id}/classrooms", $this->storeBody())
            ->assertStatus(403);
    }

    public function test_groups_manage_member_can_create_classroom(): void
    {
        $academy = Academy::factory()->create();
        $manager = $this->memberWith($academy, ['groups.view', 'groups.manage']);

        $this->actingAs($manager, 'api')
            ->postJson("/api/academies/{$academy->id}/classrooms", $this->storeBody())
            ->assertStatus(201);
    }

    public function test_owner_can_create_classroom(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/classrooms", $this->storeBody())
            ->assertStatus(201);
    }

    public function test_unapproved_manager_is_rejected(): void
    {
        $academy = Academy::factory()->create();
        $pending = $this->memberWith($academy, ['groups.view', 'groups.manage'], status: 1);

        $this->actingAs($pending, 'api')
            ->getJson("/api/academies/{$academy->id}/classrooms")
            ->assertStatus(403);

        $this->actingAs($pending, 'api')
            ->postJson("/api/academies/{$academy->id}/classrooms", $this->storeBody())
            ->assertStatus(403);
    }
}
