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
        Schema::table('course_remediation_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('course_remediation_sessions', 'quiz_id')) {
                $table->unsignedBigInteger('quiz_id')
                      ->nullable()
                      ->after('course_id');
            }
            
            // Add index instead of foreign key due to MyISAM engine in course_quizzes
            $table->index('quiz_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_remediation_sessions', function (Blueprint $table) {
            $table->dropIndex(['quiz_id']);
            // We don't drop the column if it was already there, but for clean migration we can
            // $table->dropColumn('quiz_id');
        });
    }
};
