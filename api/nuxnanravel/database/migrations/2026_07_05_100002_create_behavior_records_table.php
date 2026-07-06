<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('behavior_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('behavior_categories')->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('behavior_sessions')->nullOnDelete();
            $table->enum('type', ['positive', 'negative']);
            $table->unsignedInteger('points')->default(1);
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('incident_date');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->enum('status', ['recorded', 'pending_approval', 'approved', 'rejected'])->default('recorded');
            $table->json('evidence')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->nullOnDelete();
            $table->timestamps();

            $table->index(['academy_id', 'student_id', 'incident_date']);
            $table->index(['academy_id', 'status']);
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('behavior_records');
    }
};
