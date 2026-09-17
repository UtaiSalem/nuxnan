<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SC-S7 — ทำให้คีย์สิทธิ์ของตารางเรียน "มีความหมายจริง" (D4 / G10)
 *
 * เดิม `schedule.manage` ไม่ได้อยู่ในบทบาทใดเลย (แม้แต่ admin) และนักเรียน/ผู้ปกครอง
 * ไม่มี `schedule.view` ⇒ พอด่าน GET เปลี่ยนมาใช้ `schedule.view` จริง ๆ พวกเขาจะโดนปิดทันที
 * migration นี้จึงต้องมาคู่กับการเปลี่ยนด่านใน routes
 *
 * แก้เฉพาะบทบาทระบบ (is_system = 1) ตามชื่อ · ข้ามบทบาทที่ถือ '*' อยู่แล้ว (owner)
 * และไม่แตะบทบาทที่โรงเรียนสร้างเอง
 */
return new class extends Migration
{
    /** คีย์ที่ต้องมีหลัง up() (เติมแบบ idempotent — ที่มีอยู่แล้วไม่ซ้ำ) */
    private const GRANTS = [
        'director' => ['schedule.view', 'schedule.manage'],
        'admin' => ['schedule.view', 'schedule.manage'],
        'teacher' => ['schedule.view'],
        'registrar' => ['schedule.view'],
        'staff' => ['schedule.view'],
        'student' => ['schedule.view'],
        'parent' => ['schedule.view'],
    ];

    /**
     * เฉพาะคีย์ที่ migration นี้เป็นคนเติมเข้าไปจริง ๆ
     * (teacher/registrar/staff มี `schedule.view` อยู่ก่อนแล้ว ⇒ down() ห้ามถอดของพวกเขา)
     */
    private const REVOKE_ON_DOWN = [
        'director' => ['schedule.manage'],
        'admin' => ['schedule.manage'],
        'student' => ['schedule.view'],
        'parent' => ['schedule.view'],
    ];

    public function up(): void
    {
        $this->applyGrants(self::GRANTS, true);
    }

    public function down(): void
    {
        $this->applyGrants(self::REVOKE_ON_DOWN, false);
    }

    private function applyGrants(array $map, bool $granting): void
    {
        if (! Schema::hasTable('academy_roles')) {
            return;
        }

        foreach ($map as $roleName => $keys) {
            $rows = DB::table('academy_roles')
                ->where('name', $roleName)
                ->where('is_system', 1)
                ->get(['id', 'permissions']);

            foreach ($rows as $row) {
                $permissions = json_decode($row->permissions ?? '[]', true);

                if (! is_array($permissions) || in_array('*', $permissions, true)) {
                    continue;
                }

                $before = $permissions;

                foreach ($keys as $key) {
                    if ($granting) {
                        if (! in_array($key, $permissions, true)) {
                            $permissions[] = $key;
                        }
                    } else {
                        $permissions = array_filter($permissions, fn ($p) => $p !== $key);
                    }
                }

                $permissions = array_values($permissions);

                if ($permissions === $before) {
                    continue;
                }

                DB::table('academy_roles')->where('id', $row->id)->update([
                    'permissions' => json_encode($permissions, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
