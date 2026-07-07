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
        Schema::table('student_import_batches', function (Blueprint $table) {
            $table->string('import_type', 30)->default('new_intake')->after('academic_year_id');
            $table->string('source_format', 10)->default('csv')->after('import_type');

            // Add 'previewed' to possible status list or alter the status column if needed.
            // Since it's enum, we can just allow 'previewed' in code or change the column type to string for flexibility.
            $table->string('status', 30)->change();
        });

        Schema::table('student_import_rows', function (Blueprint $table) {
            $table->string('status', 30)->change();
            $table->string('action', 30)->nullable()->after('status');
            $table->foreignId('matched_student_id')->nullable()->after('student_id')->constrained('students')->nullOnDelete();
            $table->json('diff_data')->nullable()->after('matched_student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_import_rows', function (Blueprint $table) {
            $table->dropForeign(['matched_student_id']);
            $table->dropColumn(['matched_student_id', 'action', 'diff_data']);
        });

        Schema::table('student_import_batches', function (Blueprint $table) {
            $table->dropColumn(['import_type', 'source_format']);
        });
    }
};
