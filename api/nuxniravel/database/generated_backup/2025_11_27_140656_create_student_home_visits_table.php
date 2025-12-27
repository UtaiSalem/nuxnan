<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('student_home_visits', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('student_id')->nullable();
            $table->bigInteger('zone_id')->nullable();
            $table->date('visit_date')->nullable();
            $table->string('visit_time')->nullable();
            $table->text('observations')->nullable();
            $table->text('notes')->nullable();
            $table->string('visit_status')->nullable();
            $table->string('visitor_name')->nullable();
            $table->string('visitor_position')->nullable();
            $table->text('recommendations')->nullable();
            $table->text('follow_up')->nullable();
            $table->date('next_visit')->nullable();
            $table->bigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_home_visits');
    }
};
