<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')
                ->constrained('school_attendances')->cascadeOnDelete();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['present', 'absent', 'late', 'leave'])->default('absent');
            $table->enum('check_in_method', ['qr', 'manual'])->default('manual');
            $table->timestamp('checked_in_at')->nullable();
            $table->string('remark')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['attendance_id', 'student_id']);
            $table->index(['academy_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_attendance_records');
    }
};
