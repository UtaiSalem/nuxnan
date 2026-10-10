<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * เมนู #14 บุคลากร (ST-S2) — แฟ้มบุคลากรผูกบัญชีสมาชิก ชื่อ/รูปใช้จาก user account
 * (Q1 ของเจ้าของโปรเจค) จึงให้ first_name/last_name เป็น nullable ได้
 * เดิมเป็น NOT NULL ทำให้ store() ที่ไม่เก็บชื่อแยกล้มด้วย DB error ตลอด
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->string('first_name')->nullable()->change();
            $table->string('last_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        // backfill ค่าว่างก่อน เพื่อให้ย้อนกลับเป็น NOT NULL ได้โดยไม่ชนแถวที่เป็น null
        DB::table('staff_profiles')->whereNull('first_name')->update(['first_name' => '']);
        DB::table('staff_profiles')->whereNull('last_name')->update(['last_name' => '']);

        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->string('first_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
        });
    }
};
