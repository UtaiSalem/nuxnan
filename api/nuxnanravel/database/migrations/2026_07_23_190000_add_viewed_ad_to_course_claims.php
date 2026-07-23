<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_point_campaign_claims', function (Blueprint $table) {
            $table->unsignedBigInteger('viewed_ad_id')->nullable();
            $table->foreign('viewed_ad_id')->references('id')->on('adverts')->nullOnDelete();
            $table->index('viewed_ad_id');
        });
    }

    public function down(): void
    {
        Schema::table('course_point_campaign_claims', function (Blueprint $table) {
            $table->dropForeign(['viewed_ad_id']);
            $table->dropIndex(['viewed_ad_id']);
            $table->dropColumn('viewed_ad_id');
        });
    }
};
