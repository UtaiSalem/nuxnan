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
        Schema::create('topics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('topics_user_id_foreign');
            $table->unsignedBigInteger('academy_id')->nullable();
            $table->unsignedBigInteger('course_id')->index('topics_course_id_foreign');
            $table->unsignedBigInteger('lesson_id')->index('topics_lesson_id_foreign');
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->text('youtube_url')->nullable();
            $table->integer('min_read')->default(0);
            $table->string('status')->default('published');
            $table->integer('likes')->default(0);
            $table->integer('dislikes')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('shares')->default(0);
            $table->integer('views')->default(0);
            $table->string('hashtags')->nullable();
            $table->string('privacy_settings')->default('public');
            $table->string('location')->nullable();
            $table->string('url')->nullable();
            $table->string('tags')->nullable();
            $table->string('sentiment')->nullable();
            $table->double('engagement_rate')->default(0);
            $table->string('source_platform')->nullable();
            $table->timestamp('interacted_at')->nullable();
            $table->longText('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
