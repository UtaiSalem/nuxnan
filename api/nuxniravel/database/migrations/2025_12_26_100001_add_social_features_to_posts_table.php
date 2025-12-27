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
        Schema::table('posts', function (Blueprint $table) {
            // Feeling/Activity Status (e.g., "feeling happy", "watching a movie")
            $table->string('feeling')->nullable()->after('location');
            $table->string('feeling_icon')->nullable()->after('feeling');
            $table->string('activity_type')->nullable()->after('feeling_icon'); // watching, listening, reading, playing, etc.
            $table->string('activity_text')->nullable()->after('activity_type');
            
            // Background/Theme for text-only posts
            $table->string('background_color')->nullable()->after('activity_text');
            $table->string('background_gradient')->nullable()->after('background_color');
            $table->string('background_image')->nullable()->after('background_gradient');
            $table->string('text_color')->nullable()->after('background_image');
            $table->string('font_size')->default('medium')->after('text_color'); // small, medium, large, xlarge
            
            // Link Preview / Open Graph
            $table->longText('link_preview')->nullable()->after('font_size'); // JSON: {url, title, description, image, site_name}
            
            // Scheduling
            $table->timestamp('scheduled_at')->nullable()->after('link_preview');
            $table->boolean('is_scheduled')->default(false)->after('scheduled_at');
            $table->boolean('is_published')->default(true)->after('is_scheduled');
            
            // Additional Features
            $table->boolean('is_pinned')->default(false)->after('is_published');
            $table->boolean('comments_disabled')->default(false)->after('is_pinned');
            $table->boolean('is_edited')->default(false)->after('comments_disabled');
            $table->timestamp('edited_at')->nullable()->after('is_edited');
            
            // Poll Reference
            $table->unsignedBigInteger('poll_id')->nullable()->after('edited_at');
            
            // For live location sharing
            $table->boolean('is_live_location')->default(false)->after('poll_id');
            $table->timestamp('live_location_expires_at')->nullable()->after('is_live_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn([
                'feeling',
                'feeling_icon',
                'activity_type',
                'activity_text',
                'background_color',
                'background_gradient',
                'background_image',
                'text_color',
                'font_size',
                'link_preview',
                'scheduled_at',
                'is_scheduled',
                'is_published',
                'is_pinned',
                'comments_disabled',
                'is_edited',
                'edited_at',
                'poll_id',
                'is_live_location',
                'live_location_expires_at',
            ]);
        });
    }
};
