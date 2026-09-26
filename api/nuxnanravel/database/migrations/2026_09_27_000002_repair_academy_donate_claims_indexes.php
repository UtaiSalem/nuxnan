<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ซ่อม index ของ academy_donate_claims แบบ idempotent.
 *
 * ที่มา: create migration 2026_07_26_000002 เดิมใช้ auto index name ที่ยาวเกิน 64 อักษร
 * ของ MySQL → บาง environment ตารางถูกสร้างค้างแบบ partial (มีแค่ PRIMARY ไม่มี composite index)
 * แล้ว `if (Schema::hasTable) return;` ใน create migration ทำให้ซ่อมเองไม่ได้อีก
 * (migration ถูกมาร์ค Ran ไปแล้ว). migration นี้เติม index ที่ขาดเฉพาะตัวที่ยังไม่มี
 *
 * ขอบเขต: **index เท่านั้น** (ปลอดภัย ไม่พึ่งความสะอาดของข้อมูล) — ไม่แตะ FK เพราะการเติม FK
 * จะพังถ้ามี orphan ledger rows ในตารางเงิน ต้องให้เจ้าของเคาะข้อมูลก่อน
 */
return new class extends Migration
{
    private const INDEXES = [
        'adc_donate_claimer_at_idx' => ['academy_donate_id', 'claimer_id', 'claimed_at'],
        'adc_claimer_at_idx' => ['claimer_id', 'claimed_at'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('academy_donate_claims')) {
            return;
        }

        $existing = collect(Schema::getIndexes('academy_donate_claims'))
            ->pluck('name')
            ->all();

        Schema::table('academy_donate_claims', function (Blueprint $table) use ($existing) {
            foreach (self::INDEXES as $name => $columns) {
                if (! in_array($name, $existing, true)) {
                    $table->index($columns, $name);
                }
            }
        });
    }

    public function down(): void
    {
        // no-op โดยตั้งใจ: index เหล่านี้เป็นสคีมาฐานของ create migration (2026_07_26_000002)
        // migration นี้เป็นแค่ตัว "ซ่อม" ให้มีในกรณีตารางค้าง partial — การ rollback ไม่ควรลบสคีมาฐานทิ้ง
    }
};
