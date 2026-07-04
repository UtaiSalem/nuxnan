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
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['student_id']);
            $table->unique(['academy_id', 'student_id'], 'students_academy_student_id_unique');

            $table->dropUnique(['citizen_id']);
            $table->unique(['academy_id', 'citizen_id'], 'students_academy_citizen_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique('students_academy_citizen_id_unique');
            $table->unique(['citizen_id']);

            $table->dropUnique('students_academy_student_id_unique');
            $table->unique(['student_id']);
        });
    }
};
