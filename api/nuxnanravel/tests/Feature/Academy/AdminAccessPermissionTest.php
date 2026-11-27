<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyPermission;
use App\Models\AcademyRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessPermissionTest extends TestCase
{
    use RefreshDatabase;

    private const STAFF_ROLES = ['director', 'admin', 'teacher', 'registrar', 'staff', 'card_admin', 'finance_staff'];

    private function migration(): object
    {
        return require database_path('migrations/2026_11_23_000001_grant_admin_access_permission.php');
    }

    private function role(string $name, array $permissions, ?Academy $academy = null): AcademyRole
    {
        return AcademyRole::updateOrCreate([
            'academy_id' => $academy?->id,
            'name' => $name,
        ], [
            'display_name_th' => 'Test '.$name,
            'permissions' => $permissions,
            'is_system' => $academy === null,
        ]);
    }

    private function academyWithMember(string $roleName, array $permissions): array
    {
        $user = User::factory()->create();
        $academy = Academy::factory()->create();
        $role = $this->role($roleName, $permissions, $academy);
        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $role->id,
            'status' => 2,
        ]);

        return [$academy, $user];
    }

    public function test_catalog_and_system_defaults_grant_only_the_intended_permissions(): void
    {
        $this->assertSame(['admin', 'store'], array_slice(array_keys(AcademyPermission::PERMISSIONS), -2));
        $this->assertSame(['admin.access'], array_column(AcademyPermission::PERMISSIONS['admin'], 'name'));
        $this->assertSame(['store.manage'], array_column(AcademyPermission::PERMISSIONS['store'], 'name'));
        $this->assertNotContains('admin.access', AcademyPermission::departmentDelegableKeys());
        $this->assertNotContains('store.manage', AcademyPermission::departmentDelegableKeys());

        foreach (AcademyRole::SYSTEM_ROLES as $name => $role) {
            $this->assertSame(
                in_array($name, self::STAFF_ROLES, true),
                in_array('admin.access', $role['permissions'], true),
                $name
            );
            $this->assertNotContains('store.manage', $role['permissions'], $name);
        }
    }

    public function test_up_grants_all_seven_system_staff_roles_and_leaves_other_roles_unchanged(): void
    {
        $roles = [];
        foreach (array_keys(AcademyRole::SYSTEM_ROLES) as $name) {
            $roles[$name] = $this->role($name, $name === 'owner' ? ['*'] : ['academy.view']);
        }

        $this->migration()->up();

        foreach ($roles as $name => $role) {
            $expected = $name === 'owner' ? ['*'] : ['academy.view'];
            if (in_array($name, self::STAFF_ROLES, true)) {
                $expected[] = 'admin.access';
            }
            $this->assertSame($expected, $role->fresh()->permissions, $name);
        }
    }

    public function test_up_also_grants_a_school_specific_teacher_role(): void
    {
        $academy = Academy::factory()->create();
        $teacher = $this->role('teacher', ['courses.view', 'students.view'], $academy);

        $this->migration()->up();

        $this->assertSame(
            ['courses.view', 'students.view', 'admin.access'],
            $teacher->fresh()->permissions
        );
    }

    public function test_up_is_idempotent_and_down_removes_only_admin_access_from_matching_roles(): void
    {
        $original = ['academy.view', 'students.view', 'courses.edit.own', 'students.view'];
        $roles = [];
        foreach (self::STAFF_ROLES as $name) {
            $roles[] = $this->role($name, $original);
        }
        $academy = Academy::factory()->create();
        $roles[] = $this->role('teacher', $original, $academy);
        $custom = $this->role('custom_admin_access', ['academy.view', 'admin.access'], $academy);
        $alreadyGranted = $this->role('staff', ['academy.view', 'admin.access'], $academy);
        $alreadyGrantedBefore = $alreadyGranted->fresh()->getRawOriginal();

        $migration = $this->migration();
        $migration->up();
        $afterFirstUp = [];
        foreach ($roles as $role) {
            $this->assertSame([...$original, 'admin.access'], $role->fresh()->permissions);
            $afterFirstUp[$role->id] = $role->fresh()->getRawOriginal();
        }
        $this->assertSame($alreadyGrantedBefore, $alreadyGranted->fresh()->getRawOriginal());

        $this->travel(1)->minutes();
        try {
            $migration->up();
            foreach ($roles as $role) {
                $this->assertSame($afterFirstUp[$role->id], $role->fresh()->getRawOriginal());
            }
            $this->assertSame($alreadyGrantedBefore, $alreadyGranted->fresh()->getRawOriginal());

            $migration->down();
            foreach ($roles as $role) {
                $this->assertSame($original, $role->fresh()->permissions);
            }
            $this->assertSame(['academy.view'], $alreadyGranted->fresh()->permissions);
            $this->assertSame(['academy.view', 'admin.access'], $custom->fresh()->permissions);
        } finally {
            $this->travelBack();
        }
    }

    public function test_migration_skips_matching_roles_with_wildcard_permissions(): void
    {
        $role = $this->role('admin', ['academy.view', '*']);
        $before = $role->fresh()->getRawOriginal();
        $migration = $this->migration();

        $migration->up();
        $this->assertSame($before, $role->fresh()->getRawOriginal());

        $migration->down();
        $this->assertSame($before, $role->fresh()->getRawOriginal());
    }

    public function test_super_admin_without_ownership_or_membership_receives_wildcard_admin_role(): void
    {
        $academy = Academy::factory()->create();
        $adminRole = $this->role('admin', ['academy.view', 'admin.access']);
        Role::firstOrCreate(['name' => 'SUPER_ADMIN']);
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('SUPER_ADMIN');
        $this->assertNotEquals($academy->user_id, $superAdmin->id);
        $this->assertDatabaseMissing('academy_members', [
            'academy_id' => $academy->id,
            'user_id' => $superAdmin->id,
        ]);
        $this->assertDatabaseMissing('academy_admins', [
            'academy_id' => $academy->id,
            'user_id' => $superAdmin->id,
        ]);

        $this->actingAs($superAdmin, 'api')
            ->getJson("/api/academies/{$academy->id}/my-role")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('role.id', $adminRole->id)
            ->assertJsonPath('permissions', ['*'])
            ->assertJsonPath('is_owner', false)
            ->assertJsonPath('is_admin', true)
            ->assertJsonPath('is_super_admin', true);
    }

    public function test_teacher_with_admin_access_can_enter_admin(): void
    {
        [$academy, $teacher] = $this->academyWithMember('teacher', ['academy.view', 'admin.access']);

        $this->actingAs($teacher, 'api')
            ->getJson('/api/academies/'.rawurlencode($academy->name))
            ->assertOk()
            ->assertJsonPath('canEnterAdmin', true)
            ->assertJsonPath('isAcademyAdmin', false)
            ->assertJsonMissingPath('academy.canEnterAdmin');
    }

    public function test_student_cannot_enter_admin(): void
    {
        [$academy, $student] = $this->academyWithMember('student', AcademyRole::SYSTEM_ROLES['student']['permissions']);

        $this->actingAs($student, 'api')
            ->getJson('/api/academies/'.rawurlencode($academy->name))
            ->assertOk()
            ->assertJsonPath('canEnterAdmin', false);
    }

    public function test_outsider_cannot_enter_admin(): void
    {
        $academy = Academy::factory()->create();
        $outsider = User::factory()->create();

        $this->actingAs($outsider, 'api')
            ->getJson('/api/academies/'.rawurlencode($academy->name))
            ->assertOk()
            ->assertJsonPath('canEnterAdmin', false);
    }

    public function test_owner_can_enter_admin_without_membership(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner, 'api')
            ->getJson('/api/academies/'.rawurlencode($academy->name))
            ->assertOk()
            ->assertJsonPath('canEnterAdmin', true)
            ->assertJsonPath('isAcademyAdmin', true);
    }
}
