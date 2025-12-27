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
        Schema::table('student_home_visits', function (Blueprint $table) {
            $table->unsignedBigInteger('zone_id')->nullable()->after('student_id')->comment('โซนการเยี่ยมบ้าน');
            $table->foreign('zone_id')->references('id')->on('home_visit_zones')->onDelete('set null');
            $table->index('zone_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_home_visits', function (Blueprint $table) {
            $table->dropForeign(['zone_id']);
            $table->dropIndex(['zone_id']);
            $table->dropColumn('zone_id');
        });
    }
};
