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
        Schema::table('point_rules', function (Blueprint $blueprint) {
            $blueprint->integer('xp_amount')->nullable()->after('multiplier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('point_rules', function (Blueprint $blueprint) {
            $blueprint->dropColumn('xp_amount');
        });
    }
};
