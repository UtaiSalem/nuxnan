<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('course_attendances', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('instructor_id')->nullable();
            $table->bigInteger('course_id')->nullable();
            $table->bigInteger('group_id')->nullable();
            $table->date('date')->nullable();
            $table->dateTime('start_at')->nullable();
            $table->dateTime('finish_at')->nullable();
            $table->integer('late_time')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('course_attendances');
    }
};
