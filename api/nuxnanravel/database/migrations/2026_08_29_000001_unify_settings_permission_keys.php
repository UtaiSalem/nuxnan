<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * รวมคีย์สิทธิ์ของหน้าตั้งค่าโรงเรียนให้เหลือชุดเดียว
 *
 * ก่อนหน้านี้มีสองชุดทับกัน: `academy.settings.view`/`academy.settings.edit` (ของเก่า)
 * กับ `settings.view`/`settings.manage` (ของใหม่ที่ sidebar/หน้า/API ใช้จริง)
 * แถว `academy_roles` ของ director/admin ในฐานจริงมีแค่ชุดเก่า จึงเห็นเมนูแต่บันทึกไม่ได้
 *
 * up()   — แถวไหนมีคีย์เก่า ให้เติมคีย์ใหม่ที่เทียบเท่าเข้าไป แล้วถอดคีย์เก่าออก
 * down() — คืนคีย์เก่าให้แถวที่ถือคีย์ใหม่อยู่ เพื่อให้สิทธิ์กลับมาทำงานกับโค้ดเวอร์ชันก่อนหน้า
 *          (ไม่ถอดคีย์ใหม่ออก เพราะบาง role เช่น finance_staff ถือ `settings.view`
 *           มาก่อน migration นี้อยู่แล้ว แยกไม่ออกว่าใครได้มาจากขั้นตอนไหน
 *           — เป็น rollback แบบ "คืนสิทธิ์ให้ใช้งานได้" ไม่ใช่การย้อน byte ต่อ byte)
 */
return new class extends Migration
{
    private const LEGACY_TO_CANONICAL = [
        'academy.settings.view' => 'settings.view',
        'academy.settings.edit' => 'settings.manage',
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('academy_roles')->orderBy('id')->eachById(
                function (object $role): void {
                    $permissions = json_decode($role->permissions ?? '[]', true);
                    $permissions = is_array($permissions) ? $permissions : [];

                    if (in_array('*', $permissions, true)) {
                        return;
                    }

                    $legacyKeys = array_keys(self::LEGACY_TO_CANONICAL);

                    if (! array_intersect($permissions, $legacyKeys)) {
                        return;
                    }

                    foreach (self::LEGACY_TO_CANONICAL as $legacy => $canonical) {
                        if (in_array($legacy, $permissions, true)) {
                            $permissions[] = $canonical;
                        }
                    }

                    $permissions = array_values(array_unique(array_filter(
                        $permissions,
                        fn (mixed $permission): bool => ! in_array((string) $permission, $legacyKeys, true)
                    )));

                    DB::table('academy_roles')->where('id', $role->id)->update([
                        'permissions' => json_encode($permissions, JSON_THROW_ON_ERROR),
                    ]);
                }
            );
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::table('academy_roles')->orderBy('id')->eachById(
                function (object $role): void {
                    $permissions = json_decode($role->permissions ?? '[]', true);
                    $permissions = is_array($permissions) ? $permissions : [];

                    if (in_array('*', $permissions, true)) {
                        return;
                    }

                    $restored = $permissions;

                    foreach (self::LEGACY_TO_CANONICAL as $legacy => $canonical) {
                        if (in_array($canonical, $permissions, true)) {
                            $restored[] = $legacy;
                        }
                    }

                    $restored = array_values(array_unique($restored));

                    if ($restored === $permissions) {
                        return;
                    }

                    DB::table('academy_roles')->where('id', $role->id)->update([
                        'permissions' => json_encode($restored, JSON_THROW_ON_ERROR),
                    ]);
                }
            );
        });
    }
};
