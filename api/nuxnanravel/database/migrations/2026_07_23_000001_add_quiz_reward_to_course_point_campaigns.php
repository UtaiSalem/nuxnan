<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_point_campaigns', function (Blueprint $table) {
            $table->string('campaign_type', 32)->default('manual_claim')->change();
            $table->unsignedBigInteger('quiz_id')->nullable()->after('lesson_id');
            $table->foreign('quiz_id')->references('id')->on('course_quizzes')->nullOnDelete();
            $table->index(['quiz_id', 'status', 'campaign_type'], 'idx_campaign_quiz_status');
        });
    }

    public function down(): void
    {
        Schema::table('course_point_campaigns', function (Blueprint $table) {
            $table->dropForeign(['quiz_id']);
            $table->dropIndex('idx_campaign_quiz_status');
            $table->dropColumn('quiz_id');
        });

        Schema::table('course_point_campaigns', function (Blueprint $table) {
            $table->enum('campaign_type', ['manual_claim', 'lesson_completion'])->default('manual_claim')->change();
        });
    }
};
