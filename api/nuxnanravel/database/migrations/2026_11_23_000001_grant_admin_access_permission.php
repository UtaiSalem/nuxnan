<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * SA-2a — มอบสิทธิ์เข้าหน้าจัดการโรงเรียนให้บทบาทบุคลากร
 *
 * ครอบคลุมทั้งบทบาทระบบและบทบาทที่โรงเรียนก๊อปไป (ไม่กรอง academy_id IS NULL)
 * เพราะประตูเดิมยอมให้บทบาทชื่อ teacher เข้าได้ รวมถึงแถวของแต่ละโรงเรียนด้วย
 * หากเติมเฉพาะบทบาทระบบ ครูที่ใช้บทบาทก๊อปจะเข้าแอดมินไม่ได้หลังเปลี่ยนประตูใน SA-1
 */
return new class extends Migration
{
    private const ROLE_NAMES = ['director', 'admin', 'teacher', 'registrar', 'staff', 'card_admin', 'finance_staff'];

    private const KEY = 'admin.access';

    public function up(): void
    {
        if (DB::table('academy_roles')->count() === 0) {
            return;
        }

        DB::transaction(function (): void {
            DB::table('academy_roles')
                ->whereIn('name', self::ROLE_NAMES)
                ->orderBy('id')
                ->eachById(function (object $role): void {
                    $permissions = json_decode($role->permissions ?? '[]', true);
                    $permissions = is_array($permissions) ? $permissions : [];

                    if (in_array('*', $permissions, true) || in_array(self::KEY, $permissions, true)) {
                        return;
                    }

                    DB::table('academy_roles')->where('id', $role->id)->update([
                        'permissions' => json_encode(array_values([
                            ...$permissions,
                            self::KEY,
                        ]), JSON_THROW_ON_ERROR),
                        'updated_at' => now(),
                    ]);
                });
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::table('academy_roles')
                ->whereIn('name', self::ROLE_NAMES)
                ->orderBy('id')
                ->eachById(function (object $role): void {
                    $permissions = json_decode($role->permissions ?? '[]', true);
                    $permissions = is_array($permissions) ? $permissions : [];

                    if (in_array('*', $permissions, true) || ! in_array(self::KEY, $permissions, true)) {
                        return;
                    }

                    DB::table('academy_roles')->where('id', $role->id)->update([
                        'permissions' => json_encode(array_values(array_filter(
                            $permissions,
                            fn (mixed $permission): bool => $permission !== self::KEY
                        )), JSON_THROW_ON_ERROR),
                        'updated_at' => now(),
                    ]);
                });
        });
    }
};
