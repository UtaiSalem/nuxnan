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
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('total_points_earned')->default(0)->after('pp');
            $table->bigInteger('total_points_spent')->default(0)->after('total_points_earned');
            $table->integer('level')->default(1)->after('total_points_spent');
            $table->bigInteger('xp_for_next_level')->default(100)->after('level');
            $table->bigInteger('current_xp')->default(0)->after('xp_for_next_level');
            
            // Indexes
            $table->index('level');
            $table->index('total_points_earned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['total_points_earned', 'total_points_spent', 'level', 'xp_for_next_level', 'current_xp']);
        });
    }
};
