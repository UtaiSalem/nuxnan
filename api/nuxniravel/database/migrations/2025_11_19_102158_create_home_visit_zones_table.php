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
        Schema::create('home_visit_zones', function (Blueprint $table) {
            $table->id();
            $table->string('zone_name')->comment('ชื่อโซน');
            $table->text('description')->nullable()->comment('รายละเอียดโซน');
            $table->string('zone_code')->unique()->comment('รหัสโซน');
            $table->string('color')->nullable()->comment('สีประจำโซน');
            $table->boolean('is_active')->default(true)->comment('สถานะใช้งาน');
            $table->integer('display_order')->default(0)->comment('ลำดับการแสดง');
            $table->timestamps();
            
            $table->index(['is_active', 'display_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_visit_zones');
    }
};
