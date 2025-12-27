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
        Schema::create('student_home_visits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('student_id')->nullable();
            $table->date('visit_date')->index();
            $table->time('visit_time')->nullable();
            $table->text('observations')->nullable()->comment('สภาพแวดล้อมและการสังเกตการณ์');
            $table->text('notes')->nullable()->comment('บันทึกเพิ่มเติม');
            $table->enum('visit_status', ['completed', 'pending', 'rescheduled'])->default('completed');
            $table->string('visitor_name');
            $table->string('visitor_position')->nullable();
            $table->text('recommendations')->nullable();
            $table->text('follow_up')->nullable();
            $table->date('next_visit')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->comment('Teacher ID who created');
            $table->timestamps();
            $table->string('id_card', 20)->nullable();

            $table->index(['student_id', 'visit_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_home_visits');
    }
};
