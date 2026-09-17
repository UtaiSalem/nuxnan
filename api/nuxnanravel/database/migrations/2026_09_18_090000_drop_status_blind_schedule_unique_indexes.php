<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * G22 — ลบ unique index ที่ไม่รู้จัก `status` ออกจาก `class_schedules`
 *
 * `unique_teacher_schedule` / `unique_classroom_schedule` กันได้แค่ "เวลาเริ่มตรงกันเป๊ะ"
 * ซึ่งเป็นเซตย่อยของการชนจริง (08:30–09:20 กับ 09:00–09:50 ชนกันแต่ index ไม่รู้เรื่อง)
 * และเพราะไม่สนใจ `status` คาบที่ถูกยกเลิกจึงยังจองเวลาเริ่มนั้นไว้
 * ⇒ สร้างคาบใหม่แทนคาบที่ยกเลิกแล้วจะได้ 500 (SQLSTATE 23000) ทั้งที่ควรสำเร็จ
 *
 * ตัวกันจริงคือสูตรช่วงเวลาแบบ half-open ใน `ClassSchedule::overlappingQuery()` (SC-S5)
 * ซึ่ง "รู้จัก status" และครอบคลุมการชนทุกรูปแบบ
 *
 * แทนที่ด้วย index ธรรมดาชุดคอลัมน์เดิม เพื่อให้แผนคิวรีของการตรวจชนยังเหมือนเดิม
 */
return new class extends Migration
{
    private const DROPPED_UNIQUES = [
        'unique_teacher_schedule' => ['teacher_id', 'semester_id', 'day_of_week', 'start_time'],
        'unique_classroom_schedule' => ['classroom_id', 'semester_id', 'day_of_week', 'start_time'],
    ];

    private const REPLACEMENT_INDEXES = [
        'sched_teacher_slot_idx' => ['teacher_id', 'semester_id', 'day_of_week', 'start_time'],
        'sched_classroom_slot_idx' => ['classroom_id', 'semester_id', 'day_of_week', 'start_time'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('class_schedules')) {
            return;
        }

        foreach (array_keys(self::DROPPED_UNIQUES) as $name) {
            $this->dropIndexIfExists('class_schedules', $name);
        }

        foreach (self::REPLACEMENT_INDEXES as $name => $columns) {
            if ($this->hasIndex('class_schedules', $name)) {
                continue;
            }

            Schema::table('class_schedules', function (Blueprint $table) use ($name, $columns) {
                $table->index($columns, $name);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('class_schedules')) {
            return;
        }

        // ข้อมูลที่เกิดขึ้นหลัง up() อาจมีคู่ที่ unique เดิมห้ามไว้ (เช่น คาบที่ยกเลิก + คาบใหม่เวลาเดิม)
        // ถ้ามี ให้หยุดพร้อมบอกจำนวนให้ชัด ดีกว่าลบข้อมูลของโรงเรียนทิ้งเองเงียบ ๆ
        foreach (self::DROPPED_UNIQUES as $name => $columns) {
            $conflicts = DB::table('class_schedules')
                ->select($columns)
                ->selectRaw('COUNT(*) as total')
                ->groupBy($columns)
                ->havingRaw('COUNT(*) > 1')
                ->get();

            if ($conflicts->isNotEmpty()) {
                throw new RuntimeException(
                    'ย้อน migration นี้ไม่ได้: มี '.$conflicts->count().' กลุ่มใน class_schedules ที่ขัดกับ '
                    .$name.' ('.implode(', ', $columns).') — ต้องจัดการแถวซ้ำก่อนจึงจะคืน unique index เดิมได้'
                );
            }
        }

        foreach (array_keys(self::REPLACEMENT_INDEXES) as $name) {
            $this->dropIndexIfExists('class_schedules', $name);
        }

        foreach (self::DROPPED_UNIQUES as $name => $columns) {
            if ($this->hasIndex('class_schedules', $name)) {
                continue;
            }

            Schema::table('class_schedules', function (Blueprint $table) use ($name, $columns) {
                $table->unique($columns, $name);
            });
        }
    }

    /**
     * ดู index ตามชื่อแบบไม่ผูกกับชนิดฐานข้อมูล (Schema::getIndexes มีทั้ง MySQL และ SQLite)
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $names = array_map(fn ($index) => $index['name'], Schema::getIndexes($table));

        return in_array($indexName, $names, true);
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! $this->hasIndex($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropIndex($indexName);
        });
    }
};
