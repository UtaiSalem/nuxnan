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
        Schema::table('courses', function (Blueprint $table) {
            $table->enum('unlock_reading_mode', ['minutes', 'all_lessons', 'count', 'specific'])
                ->default('minutes')
                ->after('unlock_reading_minutes');
            $table->unsignedInteger('unlock_reading_lesson_count')->nullable()
                ->after('unlock_reading_mode');
            $table->json('unlock_reading_lesson_ids')->nullable()
                ->after('unlock_reading_lesson_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn([
                'unlock_reading_mode',
                'unlock_reading_lesson_count',
                'unlock_reading_lesson_ids',
            ]);
        });
    }
};
