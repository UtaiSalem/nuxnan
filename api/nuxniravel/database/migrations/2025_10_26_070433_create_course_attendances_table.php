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
        Schema::create('course_attendances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('instructor_id');
            $table->unsignedBigInteger('course_id')->index('course_attendances_course_id_foreign');
            $table->unsignedBigInteger('group_id')->index('course_attendances_group_id_foreign');
            $table->date('date');
            $table->dateTime('start_at')->nullable()->useCurrent();
            $table->dateTime('finish_at')->nullable()->useCurrent();
            $table->integer('late_time')->nullable()->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_attendances');
    }
};
