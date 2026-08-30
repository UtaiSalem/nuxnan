<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SET-S2 — เก็บถาวรโรงเรียน (archive) แทนการลบจริง
 *
 * เพิ่ม `archived_at` บน `academies` เป็นสถานะ "เก็บถาวร" ที่กู้คืนได้
 * ตั้งใจ **ไม่ใช้ SoftDeletes** เพราะ global scope ของมันจะตัดความสัมพันธ์ `academy`
 * ของอีก 95 โมเดลที่ belongsTo(Academy::class) ทิ้งไปด้วย (คอร์ส/นักเรียน/บัตร/ผลการเรียน)
 * การซ่อนจึงทำเฉพาะจุดที่ "แสดงรายการโรงเรียน" ด้วย scopeNotArchived() แทน
 *
 * down() ตัดคอลัมน์ทิ้ง — ค่า archived_at ที่เคยตั้งไว้จะหายไป ถือเป็นเรื่องปกติของการ rollback
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academies')) {
            return;
        }

        if (Schema::hasColumn('academies', 'archived_at')) {
            return;
        }

        Schema::table('academies', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('name_slug')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('academies') || ! Schema::hasColumn('academies', 'archived_at')) {
            return;
        }

        Schema::table('academies', function (Blueprint $table) {
            $table->dropIndex(['archived_at']);
            $table->dropColumn('archived_at');
        });
    }
};
