<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SET-S8 — ลบ `academies.name_slug` ทิ้ง
 *
 * คอลัมน์นี้ถูกสร้างด้วย Str::slug() ซึ่งกับชื่อภาษาไทยคืนสตริงว่าง
 * และกับชื่อผสมจะกลืนส่วนภาษาไทยหายไป (เหลือแต่ตัวอักษรละติน)
 * ไม่มีโค้ดส่วนไหนอ่านค่านี้ไปใช้ — ทั้งแอปนำทางด้วย `academies.name` ซึ่งเป็น UNIQUE index อยู่แล้ว
 *
 * down() เติมคอลัมน์กลับให้ได้ แต่ **คืนค่าเดิมไม่ได้** (ค่าเก่าไม่ถูกเก็บไว้)
 * ซึ่งไม่เป็นปัญหาเพราะค่าเดิมคือค่าที่ผิดอยู่แล้ว
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academies') || ! Schema::hasColumn('academies', 'name_slug')) {
            return;
        }

        // migration 2026_07_10_000001 ประกาศ ->index() ไว้ — ฐาน dev (MySQL) ไม่มี index ตัวนี้จริง
        // เพราะสาขา hasColumn ไม่เคยวิ่ง แต่ฐานที่สร้างใหม่ (รวมถึง sqlite ที่เทสต์ใช้) **มี**
        // ⇒ ต้องหา index ด้วย Schema::getIndexes() ที่ใช้ได้ทุก driver ห้ามผูกกับ SHOW INDEX ของ MySQL
        //   (sqlite โยน "error in index academies_name_slug_index after drop column" ทันที
        //    ถ้า drop คอลัมน์ทิ้งโดยยังมี index ค้างอยู่ — เทสต์ทั้งไฟล์ล้มหมด)
        $indexes = collect(Schema::getIndexes('academies'))
            ->filter(fn ($index) => in_array('name_slug', $index['columns'], true))
            ->pluck('name');

        Schema::table('academies', function (Blueprint $table) use ($indexes) {
            foreach ($indexes as $index) {
                $table->dropIndex($index);
            }
        });

        Schema::table('academies', function (Blueprint $table) {
            $table->dropColumn('name_slug');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('academies') || Schema::hasColumn('academies', 'name_slug')) {
            return;
        }

        Schema::table('academies', function (Blueprint $table) {
            $table->string('name_slug')->nullable()->after('name_en');
        });
    }
};
