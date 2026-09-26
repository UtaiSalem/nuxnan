<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ซ่อม foreign key ของ academy_donate_claims แบบ idempotent (ต่อจาก index-repair 000002).
 *
 * create migration 2026_07_26_000002 มี `if (Schema::hasTable) return;` → env ที่ตารางถูกสร้างค้าง
 * partial (ไม่มี FK) จะซ่อมเองไม่ได้. migration นี้เติม FK เฉพาะคอลัมน์ที่ยังไม่มี constraint
 * โดยคง onDelete ให้ตรงกับ create migration.
 *
 * 🔴 ข้อควรระวัง (prod): การเติม FK จะ **ล้มเหลว** ถ้ามี orphan rows (ค่าไม่ null แต่ไม่มี parent)
 * ก่อน deploy บน prod ให้รัน orphan-check ก่อน (ดู .agents/worklog.md 2026-09-27) แล้วเคลียร์
 * บน dev ตรวจแล้ว: ตารางว่าง (0 rows) → 0 orphan · migration นี้เป็น no-op บน dev (FK ครบอยู่แล้ว)
 */
return new class extends Migration
{
    // [column, parent_table, on_delete] · nullable → nullOnDelete
    private const FOREIGN_KEYS = [
        ['academy_id', 'academies', 'cascade'],
        ['claimer_id', 'users', 'cascade'],
        ['suggester_id', 'users', 'null'],
        ['claimer_transaction_id', 'points_transactions', 'cascade'],
        ['suggester_transaction_id', 'points_transactions', 'null'],
        ['school_transaction_id', 'academy_point_transactions', 'cascade'],
        ['platform_transaction_id', 'points_transactions', 'cascade'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('academy_donate_claims')) {
            return;
        }

        // คอลัมน์ที่มี FK อยู่แล้ว (จาก information_schema)
        $existing = collect(DB::select(
            'SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            ['academy_donate_claims']
        ))->pluck('COLUMN_NAME')->all();

        Schema::table('academy_donate_claims', function (Blueprint $table) use ($existing) {
            foreach (self::FOREIGN_KEYS as [$column, $parent, $onDelete]) {
                if (in_array($column, $existing, true)) {
                    continue;
                }
                $fk = $table->foreign($column)->references('id')->on($parent);
                $onDelete === 'null' ? $fk->nullOnDelete() : $fk->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        // no-op โดยตั้งใจ: FK เหล่านี้เป็นสคีมาฐานของ create migration (2026_07_26_000002)
        // migration นี้เป็นแค่ตัว "ซ่อม" ให้มีในกรณีตารางค้าง partial — rollback ไม่ควรลบ constraint ฐานทิ้ง
    }
};
