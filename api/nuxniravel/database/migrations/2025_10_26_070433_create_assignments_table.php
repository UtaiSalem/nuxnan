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
        Schema::create('assignments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('assignmentable_type');
            $table->unsignedBigInteger('assignmentable_id');
            $table->text('title');
            $table->text('description')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->integer('points')->default(0);
            $table->integer('increase_points')->nullable()->default(0);
            $table->integer('decrease_points')->nullable()->default(0);
            $table->string('assignment_type')->nullable();
            $table->string('submission_method')->nullable();
            $table->integer('max_file_size')->nullable();
            $table->boolean('is_group_assignment')->default(false);
            $table->longText('target_groups')->nullable();
            $table->text('grading_rubric')->nullable();
            $table->integer('graded_score')->nullable();
            $table->text('feedback')->nullable();
            $table->tinyInteger('status')->nullable()->default(1)->comment('[0=>not_started, 1=> \'published\', 2=> \'in_progress\', 3=> \'submitted\', 4=> \'graded]');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['assignmentable_type', 'assignmentable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
