<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('behavior_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->nullOnDelete();
            $table->integer('positive_points')->default(0);
            $table->integer('negative_points')->default(0);
            $table->integer('net_score')->default(0);
            $table->unsignedInteger('record_count')->default(0);
            $table->timestamp('last_incident_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'academy_id', 'academic_year_id', 'semester_id'], 'behavior_scores_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('behavior_scores');
    }
};
