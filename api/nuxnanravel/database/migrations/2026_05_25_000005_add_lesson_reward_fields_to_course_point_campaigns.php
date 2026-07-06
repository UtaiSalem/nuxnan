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
        Schema::table('course_point_campaigns', function (Blueprint $table) {
            $table->enum('campaign_type', ['manual_claim', 'lesson_completion'])
                ->default('manual_claim')
                ->after('course_id');

            $table->unsignedBigInteger('lesson_id')
                ->nullable()
                ->after('campaign_type');

            $table->foreign('lesson_id')
                ->references('id')
                ->on('lessons')
                ->nullOnDelete();

            $table->index(['lesson_id', 'status', 'campaign_type'], 'idx_campaign_lesson_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_point_campaigns', function (Blueprint $table) {
            $table->dropForeign(['lesson_id']);
            $table->dropIndex('idx_campaign_lesson_status');
            $table->dropColumn(['campaign_type', 'lesson_id']);
        });
    }
};
