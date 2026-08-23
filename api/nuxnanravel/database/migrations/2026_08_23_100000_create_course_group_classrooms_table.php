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
        Schema::create('course_group_classrooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_group_id');
            $table->unsignedBigInteger('classroom_id');
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('course_group_id', 'cgc_course_group_fk')
                ->references('id')
                ->on('course_groups')
                ->cascadeOnDelete();

            $table->foreign('classroom_id', 'cgc_classroom_fk')
                ->references('id')
                ->on('classrooms')
                ->restrictOnDelete();

            $table->unique(['course_group_id', 'classroom_id'], 'cgc_group_classroom_unique');
            $table->index('classroom_id', 'cgc_classroom_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_group_classrooms');
    }
};
