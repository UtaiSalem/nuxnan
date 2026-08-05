<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Imported production dumps already have legacy_row_ids and no legacy id column.
        if (! Schema::hasColumn('student_guardian_links', 'legacy_student_guardian_id')) {
            return;
        }

        Schema::table('student_guardian_links', function (Blueprint $table) {
            $table->dropUnique('student_guardian_links_legacy_student_guardian_id_unique');
            $table->dropColumn('legacy_student_guardian_id');
            $table->json('legacy_row_ids')->nullable()->after('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('student_guardian_links', function (Blueprint $table) {
            $table->dropColumn('legacy_row_ids');
            $table->unsignedBigInteger('legacy_student_guardian_id')->nullable()->unique()->after('verified_at');
        });
    }
};
