<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('student_guardians', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('student_id')->nullable();
            $table->string('student_code')->nullable();
            $table->string('guardian_type')->nullable();
            $table->string('citizen_id')->nullable();
            $table->string('title_prefix')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('occupation')->nullable();
            $table->string('workplace')->nullable();
            $table->decimal('monthly_income')->nullable();
            $table->string('relationship')->nullable();
            $table->string('status')->nullable();
            $table->string('nationality')->nullable();
            $table->boolean('is_primary_contact')->nullable();
            $table->boolean('is_emergency_contact')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_guardians');
    }
};
