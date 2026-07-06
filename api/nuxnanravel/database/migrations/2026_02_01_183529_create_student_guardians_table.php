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
        if (! Schema::hasTable('student_guardians')) {
            Schema::create('student_guardians', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id')->index();
                $table->string('full_name')->comment('ชื่อ-สกุล ผู้ปกครอง');
                $table->string('relation')->comment('ความสัมพันธ์ (บิดา, มารดา, ฯลฯ)');
                $table->string('phone_number')->nullable()->comment('เบอร์โทรศัพท์');
                $table->string('occupation')->nullable()->comment('อาชีพ');
                $table->boolean('is_emergency_contact')->default(false)->comment('เป็นบุคคลติดต่อฉุกเฉิน');
                $table->timestamps();

                $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_guardians');
    }
};
