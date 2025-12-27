<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->bigInteger('course_id')->nullable();
            $table->string('questionable_type')->nullable();
            $table->bigInteger('questionable_id')->nullable();
            $table->text('text')->nullable();
            $table->string('type')->nullable();
            $table->integer('correct_answers')->nullable();
            $table->integer('correct_option_id')->nullable();
            $table->text('explanation')->nullable();
            $table->string('difficulty_level')->nullable();
            $table->integer('time_limit')->nullable();
            $table->integer('points')->nullable();
            $table->integer('pp_fine')->nullable();
            $table->integer('position')->nullable();
            $table->string('tags')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('questions');
    }
};
