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
        // Class Schedules - ตารางเรียน
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->foreignId('semester_id')->constrained()->onDelete('cascade');
            $table->foreignId('classroom_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');

            // Schedule timing
            $table->unsignedTinyInteger('day_of_week')->index(); // 1=จันทร์, 2=อังคาร, ..., 7=อาทิตย์
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedTinyInteger('period_number')->nullable(); // คาบที่ (1, 2, 3, ...)

            // Room location (can be different from classroom's default room)
            $table->string('room', 50)->nullable();

            // Status and notes
            $table->enum('status', ['active', 'cancelled', 'temporary'])->default('active');
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['academic_year_id', 'semester_id']);
            $table->index(['classroom_id', 'day_of_week']);
            $table->index(['teacher_id', 'day_of_week']);
            $table->index(['subject_id', 'semester_id']);

            // Unique constraint to prevent double booking
            $table->unique(
                ['classroom_id', 'semester_id', 'day_of_week', 'start_time'],
                'unique_classroom_schedule'
            );
            $table->unique(
                ['teacher_id', 'semester_id', 'day_of_week', 'start_time'],
                'unique_teacher_schedule'
            );
        });

        // Teacher Assignments - การมอบหมายครูผู้สอน
        Schema::create('teacher_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->foreignId('semester_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('classroom_id')->constrained()->onDelete('cascade');

            // Role in this class
            $table->enum('role', ['primary', 'secondary', 'substitute'])->default('primary');
            $table->boolean('is_homeroom')->default(false);

            // Hours
            $table->unsignedSmallInteger('weekly_hours')->nullable();
            $table->unsignedSmallInteger('total_periods')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            // Unique constraint: One primary teacher per subject per classroom per semester
            $table->unique(
                ['semester_id', 'subject_id', 'classroom_id', 'role'],
                'unique_primary_teacher'
            );
        });

        // Schedule Periods - คาบเรียน (Template)
        Schema::create('schedule_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('period_number'); // คาบที่ 1, 2, 3...
            $table->string('name', 50); // ชื่อคาบ เช่น "คาบ 1", "Morning 1"
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('period_type', ['class', 'break', 'lunch', 'activity'])->default('class');
            $table->unsignedTinyInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['academy_id', 'period_number'], 'unique_period_per_academy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_periods');
        Schema::dropIfExists('teacher_assignments');
        Schema::dropIfExists('class_schedules');
    }
};
