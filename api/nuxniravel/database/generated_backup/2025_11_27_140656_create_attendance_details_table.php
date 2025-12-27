<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendance_details', function (Blueprint $table) {
            $table->id();
            $table->string('attendanceable_type')->nullable();
            $table->bigInteger('attendanceable_id')->nullable();
            $table->bigInteger('course_attendance_id')->nullable();
            $table->bigInteger('course_id')->nullable();
            $table->bigInteger('group_id')->nullable();
            $table->bigInteger('course_member_id')->nullable();
            $table->boolean('status')->nullable();
            $table->string('time_in')->nullable();
            $table->string('time_out')->nullable();
            $table->text('comments')->nullable();
            $table->integer('late_threshold')->nullable();
            $table->string('excused_absence_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance_details');
    }
};
