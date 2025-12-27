<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_id')->nullable();
            $table->string('citizen_id')->nullable();
            $table->string('title_prefix_th')->nullable();
            $table->string('first_name_th')->nullable();
            $table->string('last_name_th')->nullable();
            $table->string('middle_name_th')->nullable();
            $table->string('title_prefix_en')->nullable();
            $table->string('first_name_en')->nullable();
            $table->string('last_name_en')->nullable();
            $table->string('middle_name_en')->nullable();
            $table->string('nickname')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->boolean('gender')->nullable();
            $table->string('nationality')->nullable();
            $table->string('religion')->nullable();
            $table->string('profile_image')->nullable();
            $table->string('status')->nullable();
            $table->date('enrollment_date')->nullable();
            $table->string('class_level')->nullable();
            $table->string('class_section')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('students');
    }
};
