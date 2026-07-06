<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'self' to unlock_method enum
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE exam_eligibility_overrides MODIFY COLUMN unlock_method ENUM('points', 'reading', 'admin', 'appeal', 'self') NOT NULL COMMENT 'วิธีที่ใช้ปลดล็อค'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'self' from unlock_method enum
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE exam_eligibility_overrides MODIFY COLUMN unlock_method ENUM('points', 'reading', 'admin', 'appeal') NOT NULL COMMENT 'วิธีที่ใช้ปลดล็อค'");
        }
    }
};
