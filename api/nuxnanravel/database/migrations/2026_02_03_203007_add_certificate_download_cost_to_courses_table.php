<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * เพิ่มคอลัมน์สำหรับกำหนดราคาดาวน์โหลดใบประกาศนียบัตร
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // ค่าดาวน์โหลดใบประกาศ (หน่วยเป็นบาท) - null = ใช้ค่า default 10 บาท
            $table->decimal('certificate_download_cost', 10, 2)->nullable()->default(null)
                ->comment('ค่าดาวน์โหลดใบประกาศ (บาท), null = default 10 บาท');

            // สามารถดาวน์โหลดฟรีได้หรือไม่
            $table->boolean('certificate_free_download')->default(false)
                ->comment('true = ดาวน์โหลดฟรี');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['certificate_download_cost', 'certificate_free_download']);
        });
    }
};
