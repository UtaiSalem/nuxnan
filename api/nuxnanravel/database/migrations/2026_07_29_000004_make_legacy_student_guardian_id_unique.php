<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Imported production dumps are already past 000005, which drops this column
        // entirely. Nothing to tighten if it is gone.
        if (! Schema::hasColumn('student_guardian_links', 'legacy_student_guardian_id')) {
            return;
        }

        Schema::table('student_guardian_links', function (Blueprint $table) {
            $table->dropIndex(['legacy_student_guardian_id']);
            $table->unique('legacy_student_guardian_id');
        });
    }

    public function down(): void
    {
        Schema::table('student_guardian_links', function (Blueprint $table) {
            $table->dropUnique(['legacy_student_guardian_id']);
            $table->index('legacy_student_guardian_id');
        });
    }
};
