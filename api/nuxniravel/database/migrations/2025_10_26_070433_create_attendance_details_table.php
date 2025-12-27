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
        Schema::create('attendance_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('attendanceable_type');
            $table->unsignedBigInteger('attendanceable_id');
            $table->unsignedBigInteger('course_attendance_id')->index('attendance_details_course_attendance_id_foreign');
            $table->unsignedBigInteger('course_id')->index('attendance_details_course_id_foreign');
            $table->unsignedBigInteger('group_id')->index('attendance_details_group_id_foreign');
            $table->unsignedBigInteger('course_member_id')->index('attendance_details_course_member_id_foreign');
            $table->tinyInteger('status');
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->text('comments')->nullable();
            $table->unsignedInteger('late_threshold')->nullable();
            $table->string('excused_absence_reason')->nullable();
            $table->timestamps();

            $table->index(['attendanceable_type', 'attendanceable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_details');
    }
};
