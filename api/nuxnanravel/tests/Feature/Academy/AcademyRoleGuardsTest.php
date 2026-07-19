<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyRoleGuardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (AcademyRole::SYSTEM_ROLES as $name => $data) {
            AcademyRole::create(array_merge($data, [
                'academy_id' => null,
                'name' => $name,
                'is_system' => true,
                'is_active' => true,
            ]));
        }
    }

    private function role(Academy $academy, array $permissions = ['members.roles.manage']): AcademyRole
    {
        return AcademyRole::create([
            'academy_id' => $academy->id,
            'name' => fake()->unique()->lexify('custom_????'),
            'display_name_th' => 'บทบาททดสอบ',
            'display_name_en' => 'Test role',
            'permissions' => $permissions,
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 99,
            'color' => 'gray',
            'icon' => 'test',
        ]);
    }

    private function member(Academy $academy, User $user, AcademyRole $role): AcademyMember
    {
        return AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $role->id,
            'role' => $role->name,
            'status' => 2,
        ]);
    }

    public function test_delete_role_reassigns_members_to_student(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $role = $this->role($academy);
        $members = collect(range(1, 3))->map(fn () => $this->member($academy, User::factory()->create(), $role));

        $this->actingAs($owner, 'api')->deleteJson("/api/academies/{$academy->id}/roles/{$role->id}")
            ->assertOk()->assertJsonPath('reassigned_count', 3);

        $this->assertDatabaseMissing('academy_roles', ['id' => $role->id]);
        foreach ($members as $member) {
            $this->assertDatabaseHas('academy_members', ['id' => $member->id, 'academy_role_id' => AcademyRole::where('name', 'student')->whereNull('academy_id')->value('id'), 'role' => 'student']);
        }
    }

    public function test_delete_system_role_still_blocked(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $admin = AcademyRole::where('name', 'admin')->whereNull('academy_id')->firstOrFail();

        $this->actingAs($owner, 'api')->deleteJson("/api/academies/{$academy->id}/roles/{$admin->id}")->assertForbidden();
    }

    public function test_delete_role_only_reassigns_active_members(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $role = $this->role($academy);
        $active = $this->member($academy, User::factory()->create(), $role);
        $pending = AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => User::factory()->create()->id,
            'academy_role_id' => $role->id,
            'role' => $role->name,
            'status' => 1,
        ]);

        $this->actingAs($owner, 'api')->deleteJson("/api/academies/{$academy->id}/roles/{$role->id}")
            ->assertOk()->assertJsonPath('reassigned_count', 1);

        $studentRoleId = AcademyRole::where('name', 'student')->whereNull('academy_id')->value('id');
        $this->assertDatabaseHas('academy_members', ['id' => $active->id, 'academy_role_id' => $studentRoleId, 'role' => 'student']);
        // Non-active members keep their (now-null) role reference and are not reassigned
        $this->assertDatabaseMissing('academy_members', ['id' => $pending->id, 'academy_role_id' => $studentRoleId]);
    }

    public function test_delete_role_creates_student_role_when_missing(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $role = $this->role($academy);
        AcademyRole::where('name', 'student')->whereNull('academy_id')->delete();

        $this->actingAs($owner, 'api')->deleteJson("/api/academies/{$academy->id}/roles/{$role->id}")
            ->assertOk();

        $this->assertDatabaseHas('academy_roles', ['name' => 'student', 'academy_id' => null]);
    }

    public function test_admin_cannot_edit_own_role_to_remove_roles_manage_permission(): void
    {
        [$academy, $admin, $role] = $this->adminSetup();

        $this->actingAs($admin, 'api')->putJson("/api/academies/{$academy->id}/roles/{$role->id}", ['permissions' => []])
            ->assertForbidden()->assertJsonPath('message', 'ไม่สามารถแก้ไขสิทธิ์ของบทบาทตัวเองจนสูญเสียสิทธิ์จัดการบทบาท');
    }

    public function test_admin_cannot_delete_own_role(): void
    {
        [$academy, $admin, $role] = $this->adminSetup();

        $this->actingAs($admin, 'api')->deleteJson("/api/academies/{$academy->id}/roles/{$role->id}")
            ->assertForbidden()->assertJsonPath('message', 'ไม่สามารถลบบทบาทของตัวเอง');
    }

    public function test_owner_bypasses_self_lockout_guard(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $role = $this->role($academy);
        $this->member($academy, $owner, $role);

        $this->actingAs($owner, 'api')->putJson("/api/academies/{$academy->id}/roles/{$role->id}", ['permissions' => []])->assertOk();
        $this->actingAs($owner, 'api')->deleteJson("/api/academies/{$academy->id}/roles/{$role->id}")->assertOk();
    }

    private function adminSetup(): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $admin = User::factory()->create();
        $role = $this->role($academy);
        $this->member($academy, $admin, $role);

        return [$academy, $admin, $role];
    }

    public function test_manager_cannot_assign_role_to_self(): void
    {
        [$academy, $admin, $role] = $this->adminSetup();
        $adminMember = AcademyMember::where('academy_id', $academy->id)->where('user_id', $admin->id)->firstOrFail();
        $target = $this->role($academy, ['*']);

        $this->actingAs($admin, 'api')->postJson("/api/academies/{$academy->id}/members/{$adminMember->id}/role", ['role_id' => $target->id])
            ->assertForbidden()->assertJsonPath('message', 'ไม่สามารถกำหนดบทบาทให้ตัวเองได้');

        $this->assertDatabaseHas('academy_members', ['id' => $adminMember->id, 'academy_role_id' => $role->id]);
    }

    public function test_bulk_assign_role_skips_self(): void
    {
        [$academy, $admin, $role] = $this->adminSetup();
        $adminMember = AcademyMember::where('academy_id', $academy->id)->where('user_id', $admin->id)->firstOrFail();
        $other = $this->member($academy, User::factory()->create(), $role);
        $target = $this->role($academy, ['*']);

        $this->actingAs($admin, 'api')->postJson("/api/academies/{$academy->id}/members/bulk-role", [
            'member_ids' => [$adminMember->id, $other->id],
            'role_id' => $target->id,
        ])->assertOk()->assertJsonPath('updated_count', 1);

        $this->assertDatabaseHas('academy_members', ['id' => $adminMember->id, 'academy_role_id' => $role->id]);
        $this->assertDatabaseHas('academy_members', ['id' => $other->id, 'academy_role_id' => $target->id]);
    }
}
