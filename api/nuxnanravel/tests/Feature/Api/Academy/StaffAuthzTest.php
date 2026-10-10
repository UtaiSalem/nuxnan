<?php

namespace Tests\Feature\Api\Academy;

use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Position;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * เมนู #14 บุคลากร — ST-S6
 * ยืนยัน authz แยก view/manage (ST-S1) + tenant isolation + การซ่อม schema-drift (ST-S2)
 *
 * Q1: ชื่อ/รูปจากบัญชี user → first_name/last_name เป็น nullable (create ไม่ต้องส่งชื่อ)
 * Q3: read = staff.view · write = staff.manage · เปิดหน้าให้ staff.view
 * Q5: employment_type full_time|part_time|contract|temporary · status active|on_leave|suspended|resigned|terminated
 */
class StaffAuthzTest extends TestCase
{
    use RefreshDatabase;

    private function academyOwnedBy(): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        return [$academy, $owner];
    }

    /** สมาชิกที่อนุมัติของโรงเรียน พร้อม permission ที่ระบุ (ว่าง = สมาชิกธรรมดา) */
    private function memberOf(Academy $academy, array $permissions = []): User
    {
        $user = User::factory()->create();
        $role = AcademyRole::create([
            'academy_id' => $academy->id,
            'name' => 'role-'.uniqid(),
            'display_name_th' => 'บทบาททดสอบ',
            'permissions' => $permissions,
        ]);
        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $role->id,
            'status' => 2,
        ]);

        return $user;
    }

    private function positionIn(Academy $academy): Position
    {
        return Position::create([
            'academy_id' => $academy->id,
            'name' => 'ครูผู้สอน',
            'is_active' => true,
        ]);
    }

    private function departmentIn(Academy $academy, string $type = 'department'): AcademyGroup
    {
        return AcademyGroup::create([
            'academy_id' => $academy->id,
            'name' => 'ฝ่ายวิชาการ',
            'type' => $type,
        ]);
    }

    private function staffIn(Academy $academy, ?Position $position = null): StaffProfile
    {
        $position = $position ?? $this->positionIn($academy);

        return StaffProfile::create([
            'academy_id' => $academy->id,
            'user_id' => User::factory()->create()->id,
            'position_id' => $position->id,
            'employee_id' => 'EMP'.date('Y').'0001',
            'employment_type' => 'full_time',
            'status' => 'active',
            'hire_date' => now()->toDateString(),
        ]);
    }

    // ---- create (manage) ----

    public function test_academy_owner_can_create_staff(): void
    {
        [$academy, $owner] = $this->academyOwnedBy();
        $position = $this->positionIn($academy);
        $newMember = $this->memberOf($academy);

        // ไม่ส่ง first_name/last_name — ต้องสร้างได้ (Q1 / ซ่อม B3)
        $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/staff", [
                'user_id' => $newMember->id,
                'position_id' => $position->id,
                'employment_type' => 'full_time',
                'hire_date' => now()->toDateString(),
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('staff_profiles', [
            'academy_id' => $academy->id,
            'user_id' => $newMember->id,
            'status' => 'active',
            'employment_type' => 'full_time',
        ]);
    }

    public function test_member_with_staff_manage_can_create_staff(): void
    {
        [$academy] = $this->academyOwnedBy();
        $position = $this->positionIn($academy);
        $hr = $this->memberOf($academy, ['staff.manage']);
        $newMember = $this->memberOf($academy);

        $this->actingAs($hr, 'api')
            ->postJson("/api/academies/{$academy->id}/staff", [
                'user_id' => $newMember->id,
                'position_id' => $position->id,
                'employment_type' => 'contract',
                'hire_date' => now()->toDateString(),
            ])
            ->assertStatus(201);
    }

    public function test_member_with_only_staff_view_cannot_create_staff(): void
    {
        [$academy] = $this->academyOwnedBy();
        $position = $this->positionIn($academy);
        $viewer = $this->memberOf($academy, ['staff.view']);
        $newMember = $this->memberOf($academy);

        $this->actingAs($viewer, 'api')
            ->postJson("/api/academies/{$academy->id}/staff", [
                'user_id' => $newMember->id,
                'position_id' => $position->id,
                'employment_type' => 'full_time',
                'hire_date' => now()->toDateString(),
            ])
            ->assertStatus(403);

        $this->assertDatabaseMissing('staff_profiles', ['user_id' => $newMember->id]);
    }

    // ---- view ----

    public function test_member_with_staff_view_can_list_staff(): void
    {
        [$academy] = $this->academyOwnedBy();
        $this->staffIn($academy);
        $viewer = $this->memberOf($academy, ['staff.view']);

        $this->actingAs($viewer, 'api')
            ->getJson("/api/academies/{$academy->id}/staff")
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_plain_member_without_staff_view_cannot_list_staff(): void
    {
        [$academy] = $this->academyOwnedBy();
        $member = $this->memberOf($academy); // ไม่มี staff.view

        $this->actingAs($member, 'api')
            ->getJson("/api/academies/{$academy->id}/staff")
            ->assertStatus(403);
    }

    public function test_non_member_cannot_list_staff(): void
    {
        [$academy] = $this->academyOwnedBy();
        $outsider = User::factory()->create();

        $this->actingAs($outsider, 'api')
            ->getJson("/api/academies/{$academy->id}/staff")
            ->assertStatus(403);
    }

    // ---- update / delete (manage, ไม่ใช่ view) ----

    public function test_admin_can_update_staff(): void
    {
        [$academy, $owner] = $this->academyOwnedBy();
        $staff = $this->staffIn($academy);

        $this->actingAs($owner, 'api')
            ->patchJson("/api/academies/{$academy->id}/staff/{$staff->id}", [
                'employment_type' => 'part_time',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('staff_profiles', ['id' => $staff->id, 'employment_type' => 'part_time']);
    }

    public function test_member_with_only_staff_view_cannot_update_staff(): void
    {
        [$academy] = $this->academyOwnedBy();
        $staff = $this->staffIn($academy);
        $viewer = $this->memberOf($academy, ['staff.view']);

        $this->actingAs($viewer, 'api')
            ->patchJson("/api/academies/{$academy->id}/staff/{$staff->id}", [
                'employment_type' => 'temporary',
            ])
            ->assertStatus(403);
    }

    public function test_member_with_only_staff_view_cannot_delete_staff(): void
    {
        [$academy] = $this->academyOwnedBy();
        $staff = $this->staffIn($academy);
        $viewer = $this->memberOf($academy, ['staff.view']);

        $this->actingAs($viewer, 'api')
            ->deleteJson("/api/academies/{$academy->id}/staff/{$staff->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('staff_profiles', ['id' => $staff->id, 'deleted_at' => null]);
    }

    // ---- status change (ซ่อม B4: resigned ตั้ง resignation_date ไม่ใช่ termination_*) ----

    public function test_admin_can_mark_staff_resigned_and_sets_resignation_date(): void
    {
        [$academy, $owner] = $this->academyOwnedBy();
        $staff = $this->staffIn($academy);

        $this->actingAs($owner, 'api')
            ->patchJson("/api/academies/{$academy->id}/staff/{$staff->id}/status", [
                'status' => 'resigned',
                'reason' => 'ย้ายโรงเรียน',
            ])
            ->assertStatus(200);

        $staff->refresh();
        $this->assertSame('resigned', $staff->status);
        $this->assertNotNull($staff->resignation_date);
        $this->assertSame('ย้ายโรงเรียน', $staff->resignation_reason);
    }

    // ---- positions CRUD (manage) ----

    public function test_member_with_only_staff_view_cannot_create_position(): void
    {
        [$academy] = $this->academyOwnedBy();
        $viewer = $this->memberOf($academy, ['staff.view']);

        $this->actingAs($viewer, 'api')
            ->postJson("/api/academies/{$academy->id}/staff/positions", ['name' => 'ห้ามสร้าง'])
            ->assertStatus(403);
    }

    public function test_admin_can_create_position(): void
    {
        [$academy, $owner] = $this->academyOwnedBy();

        $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/staff/positions", ['name' => 'ภารโรง'])
            ->assertStatus(201);

        $this->assertDatabaseHas('positions', ['academy_id' => $academy->id, 'name' => 'ภารโรง']);
    }

    // ---- department = academy_groups(type=department) (ST-S7) ----

    public function test_can_create_staff_with_academy_group_department(): void
    {
        [$academy, $owner] = $this->academyOwnedBy();
        $position = $this->positionIn($academy);
        $department = $this->departmentIn($academy);
        $newMember = $this->memberOf($academy);

        $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/staff", [
                'user_id' => $newMember->id,
                'position_id' => $position->id,
                'department_id' => $department->id,
                'employment_type' => 'full_time',
                'hire_date' => now()->toDateString(),
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('staff_profiles', [
            'user_id' => $newMember->id,
            'department_id' => $department->id,
        ]);
    }

    public function test_rejects_department_id_that_is_not_a_department_group(): void
    {
        [$academy, $owner] = $this->academyOwnedBy();
        $position = $this->positionIn($academy);
        $notDepartment = $this->departmentIn($academy, 'club'); // คนละชนิด
        $newMember = $this->memberOf($academy);

        $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/staff", [
                'user_id' => $newMember->id,
                'position_id' => $position->id,
                'department_id' => $notDepartment->id,
                'employment_type' => 'full_time',
                'hire_date' => now()->toDateString(),
            ])
            ->assertStatus(422);
    }

    public function test_staff_departments_endpoint_lists_only_department_groups(): void
    {
        [$academy] = $this->academyOwnedBy();
        $this->departmentIn($academy);
        $this->departmentIn($academy, 'club');
        $viewer = $this->memberOf($academy, ['staff.view']);

        $this->actingAs($viewer, 'api')
            ->getJson("/api/academies/{$academy->id}/staff/departments")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    // ---- tenant isolation ----

    public function test_admin_of_another_academy_cannot_list_staff(): void
    {
        [$academyA] = $this->academyOwnedBy();
        $this->staffIn($academyA);
        [, $ownerB] = $this->academyOwnedBy();

        $this->actingAs($ownerB, 'api')
            ->getJson("/api/academies/{$academyA->id}/staff")
            ->assertStatus(403);
    }

    public function test_cross_academy_staff_binding_is_not_found(): void
    {
        [$academyA] = $this->academyOwnedBy();
        $staffInA = $this->staffIn($academyA);
        [$academyB, $ownerB] = $this->academyOwnedBy();

        // ownerB เป็น admin ของ B (ผ่าน middleware ของ B) แต่ staff อยู่ใน A → authorizeStaff คืน 404
        $this->actingAs($ownerB, 'api')
            ->patchJson("/api/academies/{$academyB->id}/staff/{$staffInA->id}", [
                'employment_type' => 'temporary',
            ])
            ->assertStatus(404);

        $this->assertDatabaseHas('staff_profiles', ['id' => $staffInA->id, 'employment_type' => 'full_time']);
    }
}
