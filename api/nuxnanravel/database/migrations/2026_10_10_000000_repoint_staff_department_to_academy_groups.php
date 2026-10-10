<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * เมนู #14 บุคลากร (ST-S7) — ชี้ "ฝ่าย" ของ staff/position ไปที่ academy_groups (type=department)
 * ซึ่งเป็นฝ่ายจริงที่เมนู #9 จัดการอยู่ แทนตาราง orphan `departments` (ไม่มี migration สร้าง · ว่าง · G25)
 *
 * วิธี low-risk ตามที่เจ้าของเคาะ (ตัวเลือก B): **ถอด FK ที่ชี้ `departments` ออก** คงคอลัมน์ department_id ไว้
 * เป็น logical reference ไปยัง academy_groups แล้วบังคับความถูกต้องที่ชั้นแอป (validate type=department)
 * — ไม่เพิ่ม FK ใหม่ เพื่อเลี่ยงความเสี่ยงบน schema ที่ drift
 *
 * staff_profiles.department_id ยังไม่เคยมีข้อมูลจริง (โมดูลไม่เคยทำงาน) จึงไม่ต้อง migrate ข้อมูลเก่า
 */
return new class extends Migration
{
    private array $targets = ['staff_profiles', 'positions'];

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return; // FK ที่ชี้ departments มีเฉพาะบน MySQL จริง · sqlite/อื่น ๆ ไม่ต้องทำ
        }

        foreach ($this->targets as $table) {
            foreach ($this->departmentForeignKeys($table) as $constraint) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! Schema::hasTable('departments')) {
            return;
        }

        // คืน FK เดิมแบบ best-effort (เฉพาะถ้าตาราง orphan ยังอยู่ และยังไม่มี FK อยู่แล้ว)
        foreach ($this->targets as $table) {
            if (Schema::hasColumn($table, 'department_id') && $this->departmentForeignKeys($table) === []) {
                DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$table}_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL");
            }
        }
    }

    /** ชื่อ FK constraint บน column department_id ที่อ้างตาราง departments (ของตารางที่ระบุ) */
    private function departmentForeignKeys(string $table): array
    {
        $rows = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME = ?',
            [$table, 'department_id', 'departments']
        );

        return array_map(fn ($r) => $r->CONSTRAINT_NAME, $rows);
    }
};
