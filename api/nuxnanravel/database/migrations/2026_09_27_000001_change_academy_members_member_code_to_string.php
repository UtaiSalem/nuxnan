<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * academy_members.member_code เป็น `int unsigned` แต่ทั้งโค้ดใช้เป็นสตริง:
     * updateIdentity validate `nullable|string|max:50`, ค้นด้วย SUBSTRING/prefix,
     * ประกอบอีเมล `S{code}@...`, เทียบ `!= ''` และ sibling `course_members.member_code`
     * เป็น `varchar(50)` อยู่แล้ว. code ตัวอักษร (เช่น 'OWN') เขียนไม่ได้ → SQLSTATE 1366.
     * ปรับให้เป็น varchar(50) ให้ตรงกับการใช้งานจริง (ค่าตัวเลขเดิมกลายเป็นสตริง ไม่เสียข้อมูล).
     */
    public function up(): void
    {
        Schema::table('academy_members', function (Blueprint $table) {
            $table->string('member_code', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        // varchar→int unsigned: ค่าที่ไม่ใช่ตัวเลขล้วน / ว่าง / เกิน range ของ unsignedInteger
        // จะ error บน MySQL STRICT ตอนย่อชนิด → เคลียร์เป็น null ก่อน (best-effort rollback)
        DB::table('academy_members')
            ->whereNotNull('member_code')
            ->whereRaw("(member_code NOT REGEXP '^[0-9]+$' OR CAST(member_code AS UNSIGNED) > 4294967295)")
            ->update(['member_code' => null]);

        Schema::table('academy_members', function (Blueprint $table) {
            $table->unsignedInteger('member_code')->nullable()->change();
        });
    }
};
