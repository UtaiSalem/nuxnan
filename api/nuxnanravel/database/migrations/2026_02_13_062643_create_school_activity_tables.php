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
        // 1. Extend school_events table (if needed, but for now we assume it has basic fields)
        // If we need to add is_recurring, recurrence_pattern, we can check if they exist or add them here.
        // Based on analysis, SchoolEvent model has these fields in fillable, so they likely exist.
        // We might want to ensure they are indexable or have defaults.

        // 2. Activity Enrollments: Tracks student registration for a semester/term
        Schema::create('activity_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('event_id')->constrained('school_events')->onDelete('cascade'); // Link to the main Activity/Event
            $table->string('status')->default('active'); // active, dropped, completed, failed
            $table->string('semester', 20)->nullable();
            $table->string('academic_year', 20)->nullable();
            $table->integer('attendance_count')->default(0);
            $table->integer('absence_count')->default(0);
            $table->decimal('grade', 5, 2)->nullable(); // Numeric grade or score
            $table->string('evaluation_result')->nullable(); // pass, fail
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'event_id', 'semester', 'academic_year'], 'enrollment_unique');
        });

        // 3. Activity Sessions: Individual class/meeting instances
        Schema::create('activity_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('school_events')->onDelete('cascade');
            $table->string('title')->nullable(); // Topic of the day
            $table->text('description')->nullable();
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime')->nullable();
            $table->string('status')->default('scheduled'); // scheduled, completed, cancelled
            $table->string('location')->nullable();
            $table->boolean('is_makeup_class')->default(false); // Creating a makeup class
            $table->timestamps();
            $table->softDeletes();
        });

        // 4. Activity Attendances: Student presence per session
        Schema::create('activity_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('activity_sessions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Student
            $table->foreignId('enrollment_id')->constrained('activity_enrollments')->onDelete('cascade');
            $table->string('status')->default('present'); // present, absent, late, leave, activity_leave
            $table->dateTime('check_in_time')->nullable();
            $table->string('check_in_method')->nullable(); // qr_code, manual, face_id
            $table->text('remarks')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users'); // Teacher who checked
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['session_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_attendances');
        Schema::dropIfExists('activity_sessions');
        Schema::dropIfExists('activity_enrollments');
    }
};
