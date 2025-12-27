<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('student_health_info', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('student_id')->nullable();
            $table->decimal('height_cm')->nullable();
            $table->decimal('weight_kg')->nullable();
            $table->string('blood_type')->nullable();
            $table->string('rh_factor')->nullable();
            $table->text('allergies')->nullable();
            $table->text('chronic_diseases')->nullable();
            $table->text('medications')->nullable();
            $table->date('last_checkup_date')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_health_info');
    }
};
