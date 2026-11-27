<?php

namespace Tests\Feature\Academy;

use App\Models\AcademyPermission;
use App\Models\AcademyRole;
use Database\Seeders\AcademyRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SystemRolePermissionSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_system_role_permission_exists_in_the_catalog(): void
    {
        $validPermissions = array_column(AcademyPermission::getAllPermissions(), 'name');

        foreach (AcademyRole::SYSTEM_ROLES as $roleName => $roleData) {
            $permissions = $roleData['permissions'] ?? [];

            foreach ($permissions as $permission) {
                if ($permission === '*') {
                    continue;
                }

                $this->assertContains(
                    $permission,
                    $validPermissions,
                    "Role '{$roleName}' has an invalid permission key: '{$permission}'"
                );
            }
        }
    }

    /**
     * migration 2026_10_11 (G4 ทะเบียนนักเรียน) มอบ students.* ให้แถว role ของระบบในฐาน
     * ถ้าค่าคงที่ไม่มีคีย์ชุดนี้ การติดตั้งใหม่ที่ seed จากค่าคงที่จะเสียสิทธิ์ไปเงียบ ๆ
     * — อ่าน ADDED จากตัว migration เอง จะได้ไม่ต้องดูแลสำเนาที่สอง
     */
    public function test_constant_carries_every_student_registry_grant_from_the_migration(): void
    {
        $migration = require database_path('migrations/2026_10_11_000001_grant_student_registry_permissions.php');
        $added = (new \ReflectionClass($migration))->getConstant('ADDED');

        $this->assertIsArray($added);
        $this->assertNotEmpty($added);

        foreach ($added as $roleName => $keys) {
            $this->assertArrayHasKey($roleName, AcademyRole::SYSTEM_ROLES, "Role '{$roleName}' is missing from SYSTEM_ROLES");

            $missing = array_values(array_diff($keys, AcademyRole::SYSTEM_ROLES[$roleName]['permissions'] ?? []));

            $this->assertSame([], $missing, "Role '{$roleName}' lacks keys granted by the 2026-10-11 migration");
        }
    }

    /**
     * ล็อก "สัญญา" ของ AcademyRoleSeeder ว่าต้องเขียนสิทธิ์จากค่าคงที่ลงแถวแบบตรงตัว
     * ครบทุก role — ถ้าใครแก้ seeder ให้ merge / filter / ข้ามแถวที่มีอยู่แล้ว เทสต์นี้จะดัง
     *
     * ⚠️ เทสต์นี้ **จับ drift ระหว่างฐานจริงกับโค้ดไม่ได้** เพราะ seeder เขียนค่าจาก
     * ค่าคงที่ตัวเดียวกับที่เอามาเทียบ (เป็นวงกลม) และฐานของเทสต์เป็น sqlite ที่ว่างเปล่าเสมอ
     * ส่วน drift ของจริงเกิดบนฐานที่ใช้งานมานาน ⇒ ต้องตรวจด้วย `php artisan academy:roles-doctor`
     * บนฐานนั้น ๆ เอง (รันหลัง deploy) เทสต์แทนกันไม่ได้
     */
    public function test_seeder_writes_every_role_verbatim_from_the_constant(): void
    {
        Artisan::call('db:seed', ['--class' => AcademyRoleSeeder::class]);

        foreach (AcademyRole::SYSTEM_ROLES as $roleName => $roleData) {
            $dbRole = AcademyRole::whereNull('academy_id')->where('name', $roleName)->first();

            $this->assertNotNull($dbRole, "Role '{$roleName}' was not created by the seeder.");

            $codePermissions = $roleData['permissions'] ?? [];
            $dbPermissions = $dbRole->permissions ?? [];

            if (! is_array($dbPermissions)) {
                $dbPermissions = [];
            }

            sort($codePermissions);
            sort($dbPermissions);

            $this->assertEquals(
                $codePermissions,
                $dbPermissions,
                "Permissions for role '{$roleName}' in the database do not match AcademyRole::SYSTEM_ROLES"
            );
        }
    }
}
