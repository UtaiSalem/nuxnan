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
        Schema::table('polls', function (Blueprint $table) {
            $table->integer('points_pool')->default(0)->after('image_url');
            $table->integer('points_per_vote')->default(0)->after('points_pool');
            $table->integer('points_distributed')->default(0)->after('points_per_vote');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('polls', function (Blueprint $table) {
            $table->dropColumn(['points_pool', 'points_per_vote', 'points_distributed']);
        });
    }
};
