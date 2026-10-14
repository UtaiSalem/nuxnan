<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * เติม FK ข้ามตารางที่ถูก "ย้ายออก" จาก create migration ยุคแรก (G25).
 *
 * ปัญหา: migration ชุดแรก (2025_06_22_*) สร้างตารางที่มี FK ชี้ตาราง academy_* ซึ่งถูกสร้างทีหลัง
 * (academies/academy_members = 2025_10_26 · academy_roles = 2026_02_01) ⇒ `migrate` จากศูนย์บน MySQL
 * ตายที่ตัวที่ 9 ด้วย `1824 Failed to open the referenced table 'academies'`.
 *
 * วิธีแก้: create migration เหล่านั้นสร้างเฉพาะ "คอลัมน์" (ตัด FK ออก) แล้วมาเติม FK ที่ migration นี้
 * ซึ่งลงวันที่ 2026_02_02 — หลังตาราง parent ทุกตัวถูกสร้างครบ (รวม academy_roles).
 *
 * idempotent: เติมเฉพาะคอลัมน์ที่ยังไม่มี FK (เช็คจาก information_schema) ⇒ DB เดิมที่มี FK ครบอยู่แล้ว
 * migration นี้เป็น no-op · DB ใหม่ (migrate จากศูนย์) จะได้ FK ครบจากตรงนี้.
 *
 * หมายเหตุ: academy_group_members/admins ไม่อยู่ในลิสต์นี้ เพราะ
 * 2026_06_20_181200_recreate_academy_group_members_admins สร้างมันใหม่พร้อม FK อยู่แล้ว (ตอนนั้น
 * academy_groups มีแล้ว) — เติมซ้ำที่นี่จะโดน drop ทิ้งภายหลัง.
 */
return new class extends Migration
{
    // [table, column, parent_table, on_delete] · 'null' → nullOnDelete, 'cascade' → cascadeOnDelete
    private const REPAIRS = [
        ['academy_invite_links', 'academy_id', 'academies', 'cascade'],
        ['academy_invite_links', 'academy_role_id', 'academy_roles', 'null'],
        ['member_activity_logs', 'academy_id', 'academies', 'cascade'],
        ['member_activity_logs', 'academy_member_id', 'academy_members', 'null'],
        ['member_tags', 'academy_id', 'academies', 'cascade'],
        ['academy_member_tag', 'academy_member_id', 'academy_members', 'cascade'],
    ];

    public function up(): void
    {
        foreach (self::REPAIRS as [$tableName, $column, $parent, $onDelete]) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, $column)) {
                continue;
            }
            if (! Schema::hasTable($parent)) {
                continue; // parent ยังไม่มี (ไม่ควรเกิดเพราะลงวันที่ทีหลัง) — กันไว้
            }
            if ($this->hasForeignKey($tableName, $column)) {
                continue; // มี FK อยู่แล้ว (DB เดิม) → ข้าม
            }

            Schema::table($tableName, function (Blueprint $table) use ($column, $parent, $onDelete) {
                $fk = $table->foreign($column)->references('id')->on($parent);
                $onDelete === 'null' ? $fk->nullOnDelete() : $fk->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        // no-op โดยตั้งใจ: FK เหล่านี้เป็นสคีมาฐานของ create migration ยุคแรก
        // (แค่ถูกย้ายจุดสร้างมาที่นี่) — rollback ไม่ควรลบทิ้ง.
    }

    private function hasForeignKey(string $table, string $column): bool
    {
        $rows = DB::select(
            'SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1',
            [$table, $column]
        );

        return count($rows) > 0;
    }
};
