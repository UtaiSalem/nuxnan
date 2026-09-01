<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyAdmin;
use App\Models\AcademyGroup;
use App\Models\AcademyGroupMember;
use App\Models\AcademyGroupPermission;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\AcademySetting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademySettingsPermissionPathsTest extends TestCase
{
    use RefreshDatabase;

    protected $owner;

    protected $academy;

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

        $this->owner = User::factory()->create();

        $this->academy = Academy::factory()->create([
            'user_id' => $this->owner->id,
            'name' => 'Original Academy Name',
        ]);

        AcademySetting::create([
            'academy_id' => $this->academy->id,
            'privacy' => 'public',
            'join_mode' => 'open',
        ]);
    }

    private function createRole(array $permissions = []): AcademyRole
    {
        return AcademyRole::create([
            'academy_id' => $this->academy->id,
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
    }

    public function test_member_whose_role_has_settings_manage_can_update()
    {
        $role = $this->createRole(['settings.manage']);
        $user = User::factory()->create();
        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $role->id,
            'role' => $role->name,
            'status' => 2,
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$this->academy->id}/settings", [
                'name' => $this->academy->name,
                'slogan' => 'New Slogan for Testing T1',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('academies', [
            'id' => $this->academy->id,
            'slogan' => 'New Slogan for Testing T1',
        ]);
    }

    public function test_member_with_only_settings_view_cannot_update()
    {
        $role = $this->createRole(['settings.view']);
        $user = User::factory()->create();
        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $role->id,
            'role' => $role->name,
            'status' => 2,
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$this->academy->id}/settings", [
                'name' => $this->academy->name,
            ]);

        $response->assertStatus(403);
        // ต้องตกที่ด่านสิทธิ์ ไม่ใช่ด่านสมาชิกภาพ — ไม่งั้นเทสต์นี้เขียวได้ด้วยเหตุผลผิด ๆ
        $this->assertEquals('Insufficient permissions', $response->json('message'));
    }

    public function test_member_with_settings_manage_but_status_not_approved_is_rejected()
    {
        $role = $this->createRole(['settings.manage']);
        $user = User::factory()->create();
        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $role->id,
            'role' => $role->name,
            'status' => 1,
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$this->academy->id}/settings", [
                'name' => $this->academy->name,
            ]);

        $response->assertStatus(403);
        $this->assertEquals('Not a member of this academy', $response->json('message'));
    }

    public function test_super_admin_who_is_not_a_member_can_update()
    {
        Role::firstOrCreate(['name' => 'SUPER_ADMIN']);
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('SUPER_ADMIN');

        $response = $this->actingAs($superAdmin, 'api')
            ->postJson("/api/academies/{$this->academy->id}/settings", [
                'name' => $this->academy->name,
                'slogan' => 'Super Admin Slogan',
            ]);

        $response->assertStatus(200);
    }

    public function test_academy_admin_row_grants_access_without_membership()
    {
        $admin = User::factory()->create();
        AcademyAdmin::create([
            'academy_id' => $this->academy->id,
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin, 'api')
            ->postJson("/api/academies/{$this->academy->id}/settings", [
                'name' => $this->academy->name,
                'slogan' => 'Academy Admin Slogan',
            ]);

        $response->assertStatus(200);
    }

    public function test_department_group_permission_cannot_grant_settings_manage()
    {
        $role = $this->createRole([]);
        $user = User::factory()->create();
        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $role->id,
            'role' => $role->name,
            'status' => 2,
        ]);

        $group = AcademyGroup::create([
            'academy_id' => $this->academy->id,
            'name' => 'Department Group',
            'type' => 'department',
            'sort_order' => 1,
        ]);

        AcademyGroupMember::create([
            'academy_group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => 2,
        ]);

        AcademyGroupPermission::create([
            'academy_group_id' => $group->id,
            'permission_key' => 'settings.manage',
            'enabled' => true,
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$this->academy->id}/settings", [
                'name' => $this->academy->name,
            ]);

        $response->assertStatus(403);
        // ตกที่ด่านสิทธิ์ (เป็นสมาชิก APPROVED แล้ว) ⇒ กฎ non-delegable คือตัวที่กัน ไม่ใช่ด่านสมาชิกภาพ
        $this->assertEquals('Insufficient permissions', $response->json('message'));
    }

    public function test_department_group_permission_does_grant_a_delegable_key()
    {
        $role = $this->createRole([]);
        $user = User::factory()->create();
        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $role->id,
            'role' => $role->name,
            'status' => 2,
        ]);

        $group = AcademyGroup::create([
            'academy_id' => $this->academy->id,
            'name' => 'Department Group',
            'type' => 'department',
            'sort_order' => 1,
        ]);

        AcademyGroupMember::create([
            'academy_group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => 2,
        ]);

        AcademyGroupPermission::create([
            'academy_group_id' => $group->id,
            'permission_key' => 'students.view',
            'enabled' => true,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/audit-logs/entity?entity_type=Classroom&entity_id=99999");

        $response->assertStatus(404);
    }

    public function test_non_member_with_no_role_is_rejected_with_membership_message()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$this->academy->id}/settings", [
                'name' => $this->academy->name,
            ]);

        $response->assertStatus(403);
        $this->assertEquals('Not a member of this academy', $response->json('message'));
    }
}
