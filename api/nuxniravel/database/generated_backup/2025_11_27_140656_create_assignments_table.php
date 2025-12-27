<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->string('assignmentable_type')->nullable();
            $table->bigInteger('assignmentable_id')->nullable();
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->integer('points')->nullable();
            $table->integer('increase_points')->nullable();
            $table->integer('decrease_points')->nullable();
            $table->string('assignment_type')->nullable();
            $table->string('submission_method')->nullable();
            $table->integer('max_file_size')->nullable();
            $table->boolean('is_group_assignment')->nullable();
            $table->longText('target_groups')->nullable();
            $table->text('grading_rubric')->nullable();
            $table->integer('graded_score')->nullable();
            $table->text('feedback')->nullable();
            $table->boolean('status')->nullable();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('assignments');
    }
};
