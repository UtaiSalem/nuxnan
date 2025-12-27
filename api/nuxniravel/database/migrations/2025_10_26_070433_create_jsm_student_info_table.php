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
        Schema::create('jsm_student_info', function (Blueprint $table) {
            $table->string('id', 5)->nullable();
            $table->string('citizen_id', 18)->nullable();
            $table->string('student_id', 20)->nullable();
            $table->string('classroom', 9)->nullable();
            $table->string('title_prefix', 12)->nullable();
            $table->string('first_name', 18)->nullable();
            $table->string('last_name', 16)->nullable();
            $table->string('middle_name', 8)->nullable();
            $table->string('en_title_prefix', 12)->nullable();
            $table->string('en_first_name', 14)->nullable();
            $table->string('en_last_name', 17)->nullable();
            $table->string('en_middle_name', 18)->nullable();
            $table->string('date_of_birth', 11)->nullable();
            $table->string('gender', 4)->nullable();
            $table->string('nationality', 10)->nullable();
            $table->string('religion', 13)->nullable();
            $table->string('student_status', 20)->nullable();
            $table->string('record_date', 13)->nullable();
            $table->string('disability_type', 15)->nullable();
            $table->string('house_code', 13)->nullable();
            $table->string('house_number', 15)->nullable();
            $table->string('village_number', 7)->nullable();
            $table->string('alley', 23)->nullable();
            $table->string('road', 18)->nullable();
            $table->string('subdistrict', 15)->nullable();
            $table->string('district', 18)->nullable();
            $table->string('province', 15)->nullable();
            $table->string('postal_code', 12)->nullable();
            $table->string('phone_number', 13)->nullable();
            $table->string('enrollment_date', 15)->nullable();
            $table->string('father_citizen_id', 25)->nullable();
            $table->string('father_title_prefix', 19)->nullable();
            $table->string('father_first_name', 20)->nullable();
            $table->string('father_last_name', 16)->nullable();
            $table->string('father_status', 15)->nullable();
            $table->string('father_nationality', 10)->nullable();
            $table->string('mother_citizen_id', 26)->nullable();
            $table->string('mother_title_prefix', 20)->nullable();
            $table->string('mother_first_name', 19)->nullable();
            $table->string('mother_last_name', 16)->nullable();
            $table->string('mother_status', 15)->nullable();
            $table->string('mother_nationality', 10)->nullable();
            $table->string('guardian_citizen_id', 30)->nullable();
            $table->string('guardian_title_prefix', 12)->nullable();
            $table->string('guardian_full_name', 28)->nullable();
            $table->string('guardian_occupation', 25)->nullable();
            $table->string('guardian_phone_number', 14)->nullable();
            $table->string('relationship', 12)->nullable();
            $table->string('height_cm', 13)->nullable();
            $table->string('weight_kg', 13)->nullable();
            $table->string('previous_school_name', 54)->nullable();
            $table->string('previous_school_province', 19)->nullable();
            $table->string('previous_grade_level', 18)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jsm_student_info');
    }
};
