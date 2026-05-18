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
        Schema::table('course_members', function (Blueprint $table) {
            $table->decimal('external_score_points', 8, 2)->default(0)->after('bonus_points');
        });
    }

    public function down(): void
    {
        Schema::table('course_members', function (Blueprint $table) {
            $table->dropColumn('external_score_points');
        });
    }
};
