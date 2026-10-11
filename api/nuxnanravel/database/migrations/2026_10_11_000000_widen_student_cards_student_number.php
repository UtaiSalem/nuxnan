<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ขยาย student_cards.student_number varchar(8) → varchar(20)
 *
 * เหตุผล: รหัสนักเรียนแต่ละโรงเรียนยาวไม่เท่ากัน (บางแห่งถึง 10 หลัก) ของเดิม 8 ตัว
 * ทำให้ store()/import() ที่รับรหัสยาวกว่านั้นชน "Data too long" (1406)
 * ตั้งให้เท่ากับ students.student_id ที่เป็น varchar(20) อยู่แล้ว (ต้นทางของค่า)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_cards', function (Blueprint $table) {
            $table->string('student_number', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        // กันข้อมูลหาย: ถ้ามีรหัสยาวเกิน 8 ตัวอยู่แล้ว การย้อนกลับจะตัดทิ้ง จึงปฏิเสธแทน
        $hasTooLong = DB::table('student_cards')
            ->whereNotNull('student_number')
            ->whereRaw('CHAR_LENGTH(student_number) > 8')
            ->exists();

        if ($hasTooLong) {
            throw new \RuntimeException(
                'ย้อนกลับไม่ได้: มี student_cards.student_number ยาวเกิน 8 ตัวอยู่ในตาราง — จัดการข้อมูลก่อนจึงจะย้อน schema ได้'
            );
        }

        Schema::table('student_cards', function (Blueprint $table) {
            $table->string('student_number', 8)->nullable()->change();
        });
    }
};
