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
        Schema::create('student_addresses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('student_id')->index('student_addresses_normalized_student_id_foreign');
            $table->enum('address_type', ['current', 'permanent', 'temporary'])->default('current')->index('student_addresses_normalized_address_type_index');
            $table->string('house_number', 50)->nullable()->comment('บ้านเลขที่');
            $table->string('village_number', 10)->nullable()->comment('หมู่');
            $table->string('village_name', 100)->nullable()->comment('ชื่อหมู่บ้าน');
            $table->string('alley', 100)->nullable()->comment('ซอย');
            $table->string('road', 100)->nullable()->comment('ถนน');
            $table->string('subdistrict', 100)->nullable()->comment('ตำบล/แขวง');
            $table->string('district', 100)->nullable()->comment('อำเภอ/เขต');
            $table->string('province', 100)->nullable()->comment('จังหวัด');
            $table->string('postal_code', 10)->nullable()->comment('รหัสไปรษณีย์');
            $table->boolean('is_current')->default(true)->index('student_addresses_normalized_is_primary_index')->comment('ที่อยู่หลัก');
            $table->timestamps();

            $table->index(['province', 'district'], 'student_addresses_normalized_province_district_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_addresses');
    }
};
