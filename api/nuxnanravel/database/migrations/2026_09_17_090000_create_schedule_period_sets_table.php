<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SC-S6 — ชุดโครงคาบเรียน (period sets)
 *
 * เดิม `schedule_periods` ติด unique(academy_id, period_number) = โรงเรียนละชุดเดียว
 * ซึ่งไม่พอกับของจริง (ประถม/มัธยมคนละโครง · วันศุกร์เลิกเร็ว · วันพุธมีชุมนุม)
 * จึงเพิ่มตาราง `schedule_period_sets` แล้วย้าย unique ไปเป็น (set_id, period_number)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_period_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->string('name', 100);
            // ว่าง/null = ใช้ได้ทุกวัน · มีค่า = ใช้เฉพาะวันเหล่านี้ (1=จันทร์ .. 7=อาทิตย์)
            $table->json('days')->nullable();
            // ว่าง/null = ใช้ได้ทุกระดับชั้น · มีค่า = ใช้เฉพาะระดับชั้นเหล่านี้ (เช่น ["ม.1","ม.2"])
            $table->json('grade_levels')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['academy_id', 'is_active'], 'sps_academy_active_idx');
        });

        if (! Schema::hasColumn('schedule_periods', 'set_id')) {
            Schema::table('schedule_periods', function (Blueprint $table) {
                $table->unsignedBigInteger('set_id')->nullable()->after('academy_id');
            });
        }

        // backfill: โรงเรียนที่มีคาบอยู่ก่อนแล้ว ได้ชุด "โครงคาบหลัก" ชุดเดียวเป็นเจ้าของคาบเดิมทั้งหมด
        $academyIds = DB::table('schedule_periods')->whereNull('set_id')->distinct()->pluck('academy_id');

        foreach ($academyIds as $academyId) {
            $setId = DB::table('schedule_period_sets')->insertGetId([
                'academy_id' => $academyId,
                'name' => 'โครงคาบหลัก',
                'days' => null,
                'grade_levels' => null,
                'is_default' => true,
                'is_active' => true,
                'display_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('schedule_periods')
                ->where('academy_id', $academyId)
                ->whereNull('set_id')
                ->update(['set_id' => $setId]);
        }

        $this->dropIndexIfExists('schedule_periods', 'unique_period_per_academy');

        Schema::table('schedule_periods', function (Blueprint $table) {
            $table->unique(['set_id', 'period_number'], 'unique_period_per_set');
            $table->index(['academy_id', 'set_id'], 'sp_academy_set_idx');
        });
    }

    public function down(): void
    {
        // สคีมาเดิมเก็บได้โรงเรียนละชุดเดียว ⇒ ต้องเลือกชุดที่รอด 1 ชุดต่อโรงเรียน
        // (ชุดเริ่มต้นชนะ ถ้าไม่มีก็เอาชุดที่ id ต่ำสุด) แล้วคาบของชุดอื่นต้องถูกลบทิ้ง
        $sets = DB::table('schedule_period_sets')->orderBy('id')->get();

        $survivor = [];
        foreach ($sets as $set) {
            $academyId = (int) $set->academy_id;
            if (! isset($survivor[$academyId])) {
                $survivor[$academyId] = (int) $set->id;
            }
        }
        foreach ($sets as $set) {
            if ($set->is_default) {
                $survivor[(int) $set->academy_id] = (int) $set->id;
            }
        }

        $keptSlots = [];
        $doomed = [];
        foreach (DB::table('schedule_periods')->orderBy('id')->get() as $period) {
            $academyId = (int) $period->academy_id;
            $slot = $academyId.'#'.$period->period_number;
            $isSurvivor = isset($survivor[$academyId]) && (int) $period->set_id === $survivor[$academyId];

            if ($isSurvivor && ! isset($keptSlots[$slot])) {
                $keptSlots[$slot] = true;

                continue;
            }

            $doomed[] = (int) $period->id;
        }

        if (! empty($doomed)) {
            DB::table('class_schedules')->whereIn('period_id', $doomed)->update(['period_id' => null]);
            DB::table('schedule_periods')->whereIn('id', $doomed)->delete();
        }

        // 🔴 ต้องลบ index ที่ชี้ `set_id` ให้หมดก่อน dropColumn ไม่งั้น SQLite พังตอนสร้างสคีมาในเทสต์
        $this->dropIndexIfExists('schedule_periods', 'unique_period_per_set');
        $this->dropIndexIfExists('schedule_periods', 'sp_academy_set_idx');

        if (Schema::hasColumn('schedule_periods', 'set_id')) {
            Schema::table('schedule_periods', function (Blueprint $table) {
                $table->dropColumn('set_id');
            });
        }

        $this->dropIndexIfExists('schedule_periods', 'unique_period_per_academy');

        Schema::table('schedule_periods', function (Blueprint $table) {
            $table->unique(['academy_id', 'period_number'], 'unique_period_per_academy');
        });

        Schema::dropIfExists('schedule_period_sets');
    }

    /**
     * ลบ index ตามชื่อแบบไม่ผูกกับชนิดฐานข้อมูล (Schema::getIndexes มีทั้ง MySQL และ SQLite)
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $names = array_map(fn ($index) => $index['name'], Schema::getIndexes($table));

        if (! in_array($indexName, $names, true)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropIndex($indexName);
        });
    }
};
