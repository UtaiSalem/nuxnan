<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('student_academic_info', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('student_id')->nullable();
            $table->string('current_grade')->nullable();
            $table->boolean('education_level')->nullable();
            $table->string('current_class')->nullable();
            $table->string('classroom_full')->nullable();
            $table->string('school_name')->nullable();
            $table->text('school_address')->nullable();
            $table->string('school_province')->nullable();
            $table->string('previous_school_name')->nullable();
            $table->string('previous_school_province')->nullable();
            $table->string('previous_grade_level')->nullable();
            $table->string('disability_type')->nullable();
            $table->text('special_needs')->nullable();
            $table->string('academic_year')->nullable();
            $table->boolean('semester')->nullable();
            $table->date('enrollment_date')->nullable();
            $table->date('graduation_date')->nullable();
            $table->string('study_status')->nullable();
            $table->boolean('is_current')->nullable();
            $table->string('transfer_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_academic_info');
    }
};
