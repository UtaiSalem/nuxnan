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
