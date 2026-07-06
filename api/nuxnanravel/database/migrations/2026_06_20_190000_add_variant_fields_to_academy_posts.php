<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academy_posts', function (Blueprint $table) {
            if (! Schema::hasColumn('academy_posts', 'post_type')) {
                $table->string('post_type', 32)->default('regular')->after('content');
                $table->index('post_type');
            }
            if (! Schema::hasColumn('academy_posts', 'target_audience')) {
                $table->json('target_audience')->nullable()->after('post_type');
            }
            if (! Schema::hasColumn('academy_posts', 'reward_points')) {
                $table->unsignedSmallInteger('reward_points')->default(0)->after('target_audience');
            }
            if (! Schema::hasColumn('academy_posts', 'embed_data')) {
                $table->json('embed_data')->nullable()->after('reward_points');
            }
            if (! Schema::hasColumn('academy_posts', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('embed_data');
                $table->index(['academy_id', 'is_pinned']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('academy_posts', function (Blueprint $table) {
            $table->dropColumn(['post_type', 'target_audience', 'reward_points', 'embed_data', 'is_pinned']);
        });
    }
};
