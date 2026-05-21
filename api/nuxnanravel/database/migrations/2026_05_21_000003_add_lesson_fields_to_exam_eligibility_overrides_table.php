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
        Schema::table('exam_eligibility_overrides', function (Blueprint $table) {
            $table->json('reading_completed_lesson_ids')->nullable()->after('reading_proof');
            $table->json('reading_required_lesson_ids')->nullable()->after('reading_completed_lesson_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_eligibility_overrides', function (Blueprint $table) {
            $table->dropColumn([
                'reading_completed_lesson_ids',
                'reading_required_lesson_ids',
            ]);
        });
    }
};
