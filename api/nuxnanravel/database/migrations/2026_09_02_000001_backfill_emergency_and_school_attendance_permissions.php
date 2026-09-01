<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * G18 — ทำให้คีย์สิทธิ์ที่ endpoint ใหม่ใช้จริง มีอยู่ในแถว role ที่ออกให้ไปแล้ว
 *
 * `emergency.view` / `emergency.manage` เป็นคีย์ใหม่ (ประกาศฉุกเฉินเคยไม่มีคีย์เลย
 * — คอนโทรลเลอร์ตรวจด้วย Academy::isAdmin() ตรง ๆ)
 * `school_attendance.manage` มีอยู่ในระบบแล้วแต่ครูไม่เคยได้รับ ทั้งที่เป็นคนเช็กชื่อจริง
 *
 * แถวที่ permissions เป็น ['*'] (owner) ข้ามไป — มันครอบทุกคีย์อยู่แล้ว
 */
return new class extends Migration
{
    private const ROLE_PERMISSIONS = [
        'director' => ['emergency.view', 'emergency.manage'],
        'admin' => ['emergency.view', 'emergency.manage'],
        'teacher' => ['school_attendance.manage'],
    ];

    private const CATALOGUE = [
        ['name' => 'emergency.view', 'display_name' => 'ดูรายงานการตอบรับประกาศฉุกเฉิน: ใครปลอดภัย/ใครขอความช่วยเหลือ', 'group' => 'emergency'],
        ['name' => 'emergency.manage', 'display_name' => 'จัดการประกาศฉุกเฉิน: ประกาศ/แก้ไข/ยุติ/ลบ', 'group' => 'emergency'],
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            $now = now();

            foreach (self::CATALOGUE as $permission) {
                if (! DB::table('academy_permissions')->where('name', $permission['name'])->exists()) {
                    DB::table('academy_permissions')->insert($permission + [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            foreach (self::ROLE_PERMISSIONS as $name => $permissions) {
                DB::table('academy_roles')->where('name', $name)->orderBy('id')->eachById(
                    function (object $role) use ($permissions): void {
                        $current = json_decode($role->permissions ?? '[]', true);
                        $current = is_array($current) ? $current : [];

                        if (in_array('*', $current, true)) {
                            return;
                        }

                        DB::table('academy_roles')->where('id', $role->id)->update([
                            'permissions' => json_encode(array_values(array_unique([
                                ...$current,
                                ...$permissions,
                            ])), JSON_THROW_ON_ERROR),
                        ]);
                    }
                );
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            foreach (self::ROLE_PERMISSIONS as $name => $permissions) {
                DB::table('academy_roles')->where('name', $name)->orderBy('id')->eachById(
                    function (object $role) use ($permissions): void {
                        $current = json_decode($role->permissions ?? '[]', true);
                        $current = is_array($current) ? $current : [];

                        if (in_array('*', $current, true)) {
                            return;
                        }

                        DB::table('academy_roles')->where('id', $role->id)->update([
                            'permissions' => json_encode(
                                array_values(array_diff($current, $permissions)),
                                JSON_THROW_ON_ERROR
                            ),
                        ]);
                    }
                );
            }

            DB::table('academy_permissions')
                ->whereIn('name', array_column(self::CATALOGUE, 'name'))
                ->delete();
        });
    }
};
