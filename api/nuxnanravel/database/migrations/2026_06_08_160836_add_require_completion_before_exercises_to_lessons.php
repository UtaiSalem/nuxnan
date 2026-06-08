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
        Schema::table('lessons', function (Blueprint $blueprint) {
            $blueprint->boolean('require_completion_before_exercises')
                ->default(false)
                ->after('access_type')
                ->comment('Student must complete reading the lesson before doing exercises');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $blueprint) {
            $blueprint->dropColumn('require_completion_before_exercises');
        });
    }
};
