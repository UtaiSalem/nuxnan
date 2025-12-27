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
        Schema::create('post_link_previews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->text('url');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('image_url')->nullable();
            $table->string('site_name')->nullable();
            $table->string('site_icon')->nullable();
            $table->string('type')->default('website'); // website, article, video, music, etc.
            $table->string('video_url')->nullable(); // For video embeds
            $table->string('video_type')->nullable(); // video/mp4, text/html (iframe)
            $table->integer('video_width')->nullable();
            $table->integer('video_height')->nullable();
            $table->string('author_name')->nullable();
            $table->string('author_url')->nullable();
            $table->string('provider_name')->nullable();
            $table->string('provider_url')->nullable();
            $table->longText('metadata')->nullable(); // Additional JSON data
            $table->timestamps();

            $table->foreign('post_id')
                  ->references('id')
                  ->on('posts')
                  ->onDelete('cascade');
            
            $table->index('post_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_link_previews');
    }
};
