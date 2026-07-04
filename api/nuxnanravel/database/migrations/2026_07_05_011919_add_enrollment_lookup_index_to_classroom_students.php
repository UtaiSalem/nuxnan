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
        Schema::table('classroom_students', function (Blueprint $table) {
            $table->index(
                ['student_id', 'academic_year_id', 'status'],
                'cs_student_year_status_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classroom_students', function (Blueprint $table) {
            $table->dropIndex('cs_student_year_status_idx');
        });
    }
};
