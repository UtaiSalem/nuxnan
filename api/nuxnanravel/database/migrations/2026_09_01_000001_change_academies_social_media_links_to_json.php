<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * SET-S7 / D17: เปลี่ยน social_media_links เป็น json object
     * ตอน migrate ทั้งตารางมีค่านี้เป็น NULL ทั้งหมด จึงไม่มีข้อมูลเก่าต้องแปลง
     */
    public function up(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            $table->json('social_media_links')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            $table->string('social_media_links', 255)->nullable()->change();
        });
    }
};
