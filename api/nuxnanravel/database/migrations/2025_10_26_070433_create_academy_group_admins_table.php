<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('academy_group_admins')) {
            return;
        }
        Schema::create('academy_group_admins', function (Blueprint $table) {
            $table->bigIncrements('id');
            // FK academy_group_id → academy_groups ย้ายไป repair migration
            // 2026_02_02_000000_repair_early_cross_table_foreign_keys: ชื่อไฟล์ create_academy_group_admins
            // sort ก่อน create_academy_groups (070433 เดียวกัน · '_' < 's') → migrate จากศูนย์ตายที่ 1824 (G25)
            $table->unsignedBigInteger('academy_group_id');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('role')->default('admin');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academy_group_admins');
    }
};
