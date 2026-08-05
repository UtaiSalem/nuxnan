<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ซ่อม `academy_donate_claims` ที่ติดมากับ dump แบบไม่สมบูรณ์
 *
 * migration ต้นทาง (2026_07_26_000002) ตั้งชื่อ index ยาวเกิน 64 ตัวอักษร จึงล้มบน
 * MySQL แล้วทิ้งตารางที่มีแต่คอลัมน์กับ PRIMARY ไว้ — ไม่มี index ไม่มี foreign key
 * และ `hasTable` guard ของมันก็ข้ามตารางนี้ตลอดไป
 *
 * ทุกคำสั่งตรวจก่อนว่ามีอยู่แล้วหรือยัง → รันซ้ำได้ และรันบน DB ที่ปกติก็ไม่มีผล
 */
return new class extends Migration
{
    public function up(): void
    {
        // Uses SHOW INDEX / information_schema, which are MySQL-only. On SQLite the
        // table is always created correctly by 2026_07_26_000002, so nothing to repair.
        if (DB::getDriverName() !== 'mysql' || ! Schema::hasTable('academy_donate_claims')) {
            return;
        }

        $indexes = collect(DB::select('SHOW INDEX FROM academy_donate_claims'))->pluck('Key_name')->unique();

        Schema::table('academy_donate_claims', function (Blueprint $table) use ($indexes) {
            if (! $indexes->contains('adc_donate_claimer_at_idx')) {
                $table->index(['academy_donate_id', 'claimer_id', 'claimed_at'], 'adc_donate_claimer_at_idx');
            }
            if (! $indexes->contains('adc_claimer_at_idx')) {
                $table->index(['claimer_id', 'claimed_at'], 'adc_claimer_at_idx');
            }
        });

        $existing = collect(DB::select(
            "SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'academy_donate_claims'
             AND REFERENCED_TABLE_NAME IS NOT NULL"
        ))->pluck('COLUMN_NAME');

        // academy_donate_id stays without a constraint on purpose — the legacy
        // academy_donates table is not FK-compatible in some deployed schemas,
        // which is why the original migration left it out too.
        $foreignKeys = [
            'academy_id' => ['academies', 'cascade'],
            'claimer_id' => ['users', 'cascade'],
            'suggester_id' => ['users', 'null'],
            'claimer_transaction_id' => ['points_transactions', 'cascade'],
            'suggester_transaction_id' => ['points_transactions', 'null'],
            'school_transaction_id' => ['academy_point_transactions', 'cascade'],
            'platform_transaction_id' => ['points_transactions', 'cascade'],
        ];

        Schema::table('academy_donate_claims', function (Blueprint $table) use ($existing, $foreignKeys) {
            foreach ($foreignKeys as $column => [$references, $onDelete]) {
                if ($existing->contains($column)) {
                    continue;
                }
                $foreign = $table->foreign($column)->references('id')->on($references);
                $onDelete === 'cascade' ? $foreign->cascadeOnDelete() : $foreign->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        // Intentionally empty: this only repairs what 2026_07_26_000002 was meant to
        // create, so rolling it back would just reintroduce the broken state.
    }
};
