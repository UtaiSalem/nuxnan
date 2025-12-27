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
        Schema::create('questions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('questions_user_id_foreign');
            $table->unsignedBigInteger('course_id');
            $table->string('questionable_type');
            $table->unsignedBigInteger('questionable_id');
            $table->text('text');
            $table->string('type')->nullable();
            $table->integer('correct_answers')->nullable();
            $table->unsignedInteger('correct_option_id')->nullable();
            $table->text('explanation')->nullable();
            $table->enum('difficulty_level', ['easy', 'medium', 'hard'])->nullable();
            $table->integer('time_limit')->nullable();
            $table->integer('points');
            $table->integer('pp_fine')->default(0);
            $table->integer('position')->nullable();
            $table->string('tags')->nullable();
            $table->timestamps();

            $table->index(['questionable_type', 'questionable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
