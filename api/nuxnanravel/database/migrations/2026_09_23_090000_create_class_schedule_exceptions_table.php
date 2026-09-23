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
        Schema::create('class_schedule_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_schedule_id')->constrained('class_schedules')->cascadeOnDelete();
            $table->date('date');
            $table->string('type', 20);
            $table->foreignId('substitute_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('room', 50)->nullable();
            $table->string('reason', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['class_schedule_id', 'date'], 'cse_schedule_date_unique');
            $table->index(['academy_id', 'date'], 'cse_academy_date_idx');
            $table->index(['substitute_teacher_id', 'date'], 'cse_substitute_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_schedule_exceptions');
    }
};
