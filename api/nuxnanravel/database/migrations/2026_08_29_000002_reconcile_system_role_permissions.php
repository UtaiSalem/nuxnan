<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ปรับสิทธิ์ของ system role ในตาราง `academy_roles` ให้ตรงกับค่าคงที่ในโค้ด
 *
 * ค่าคงที่ถูกใช้ตอนสร้าง role ให้โรงเรียนใหม่เท่านั้น ไม่มีกลไก sync ย้อนกลับ
 * ที่ผ่านมาสิทธิ์ถูกเพิ่มสองทางแยกกัน (บางรอบผ่าน migration บางรอบผ่านค่าคงที่)
 * ทำให้แถวในฐานกับค่าคงที่เคลื่อนออกจากกันคนละทาง เช่น director ในฐานขาด 19 คีย์
 * (roles.manage, groups.manage, staff.manage, grades.manage, events.manage,
 * school_attendance.* ฯลฯ) และไม่มีแถว `card_admin` เลย
 *
 * ลิสต์ด้านล่างถูก "แช่" ไว้ในไฟล์นี้โดยตั้งใจ ไม่อ่านจากค่าคงที่

 * เพราะค่าคงที่จะเปลี่ยนอีกในอนาคต — migration ต้องให้ผลเหมือนเดิมทุกครั้งที่รัน
 *
 * down() ถอดเฉพาะคีย์ในลิสต์ ADDED ออก ซึ่งเป็น inverse ที่ตรงจริงสำหรับฐาน ณ วันที่วัด
 * (คีย์ทุกตัวในลิสต์คือคีย์ที่แถวนั้นยังไม่มีตอนวัด) และลบแถว card_admin ที่ up() สร้าง
 */
return new class extends Migration
{
    /** คีย์ที่โค้ดมีแต่แถวในฐานขาด ณ 2026-08-29 */
    private const ADDED = [
        'director' => ['students.cards.produce', 'behavior.view', 'behavior.record', 'behavior.approve', 'behavior.manage', 'roles.view', 'roles.manage', 'groups.view', 'groups.manage', 'schedule.view', 'grades.view', 'grades.manage', 'staff.view', 'staff.manage', 'events.view', 'events.manage', 'school_attendance.view', 'school_attendance.manage', 'courses.manage'],
        'admin' => ['students.cards.produce', 'behavior.view', 'behavior.record', 'behavior.approve', 'behavior.manage', 'roles.view', 'roles.manage', 'groups.view', 'groups.manage', 'schedule.view', 'grades.view', 'grades.manage', 'staff.view', 'staff.manage', 'events.view', 'events.manage', 'school_attendance.view', 'school_attendance.manage', 'courses.manage'],
        'teacher' => ['students.cards.request', 'behavior.view', 'behavior.record', 'grades.view', 'schedule.view', 'groups.view', 'staff.view', 'events.view', 'school_attendance.view'],
        'registrar' => ['behavior.view', 'groups.view', 'schedule.view', 'staff.view', 'events.view'],
        'staff' => ['behavior.view', 'schedule.view', 'events.view'],
        'finance_staff' => ['events.view', 'settings.view'],
        'student' => ['behavior.view.own'],
        'parent' => ['children.behavior.view'],
    ];

    /** role ที่มีในโค้ดแต่ไม่มีแถวในฐานเลย */
    private const MISSING_ROLE = [
        'name' => 'card_admin',
        'display_name_th' => 'เจ้าหน้าที่จัดทำบัตร',
        'display_name_en' => 'Card Administrator',
        'color' => 'sky',
        'icon' => 'fluent:card-ui-24-filled',
        'sort_order' => 7,
        'permissions' => ['academy.view', 'members.view', 'students.view', 'students.cards.produce'],
    ];

    public function up(): void
    {
        if (DB::table('academy_roles')->whereNull('academy_id')->count() === 0) {
            return;
        }

        DB::transaction(function (): void {
            foreach (self::ADDED as $name => $added) {
                DB::table('academy_roles')
                    ->whereNull('academy_id')
                    ->where('name', $name)
                    ->orderBy('id')
                    ->eachById(function (object $role) use ($added): void {
                        $permissions = json_decode($role->permissions ?? '[]', true);
                        $permissions = is_array($permissions) ? $permissions : [];

                        if (in_array('*', $permissions, true)) {
                            return;
                        }

                        DB::table('academy_roles')->where('id', $role->id)->update([
                            'permissions' => json_encode(array_values(array_unique([
                                ...$permissions,
                                ...$added,
                            ])), JSON_THROW_ON_ERROR),
                            'updated_at' => now(),
                        ]);
                    });
            }

            $exists = DB::table('academy_roles')
                ->whereNull('academy_id')
                ->where('name', self::MISSING_ROLE['name'])
                ->exists();

            if (! $exists) {
                DB::table('academy_roles')->insert([
                    'academy_id' => null,
                    'name' => self::MISSING_ROLE['name'],
                    'display_name_th' => self::MISSING_ROLE['display_name_th'],
                    'display_name_en' => self::MISSING_ROLE['display_name_en'],
                    'permissions' => json_encode(self::MISSING_ROLE['permissions'], JSON_THROW_ON_ERROR),
                    'is_system' => true,
                    'is_active' => true,
                    'sort_order' => self::MISSING_ROLE['sort_order'],
                    'color' => self::MISSING_ROLE['color'],
                    'icon' => self::MISSING_ROLE['icon'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            foreach (self::ADDED as $name => $added) {
                DB::table('academy_roles')
                    ->whereNull('academy_id')
                    ->where('name', $name)
                    ->orderBy('id')
                    ->eachById(function (object $role) use ($added): void {
                        $permissions = json_decode($role->permissions ?? '[]', true);
                        $permissions = is_array($permissions) ? $permissions : [];

                        if (in_array('*', $permissions, true)) {
                            return;
                        }

                        DB::table('academy_roles')->where('id', $role->id)->update([
                            'permissions' => json_encode(array_values(array_filter(
                                $permissions,
                                fn (mixed $permission): bool => ! in_array((string) $permission, $added, true)
                            )), JSON_THROW_ON_ERROR),
                            'updated_at' => now(),
                        ]);
                    });
            }

            DB::table('academy_roles')
                ->whereNull('academy_id')
                ->where('name', self::MISSING_ROLE['name'])
                ->delete();
        });
    }
};
