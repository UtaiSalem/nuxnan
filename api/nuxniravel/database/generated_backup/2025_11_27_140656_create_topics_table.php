<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->bigInteger('academy_id')->nullable();
            $table->bigInteger('course_id')->nullable();
            $table->bigInteger('lesson_id')->nullable();
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->text('youtube_url')->nullable();
            $table->integer('min_read')->nullable();
            $table->string('status')->nullable();
            $table->integer('likes')->nullable();
            $table->integer('dislikes')->nullable();
            $table->integer('comments')->nullable();
            $table->integer('shares')->nullable();
            $table->integer('views')->nullable();
            $table->string('hashtags')->nullable();
            $table->string('privacy_settings')->nullable();
            $table->string('location')->nullable();
            $table->string('url')->nullable();
            $table->string('tags')->nullable();
            $table->string('sentiment')->nullable();
            $table->double('engagement_rate')->nullable();
            $table->string('source_platform')->nullable();
            $table->timestamp('interacted_at')->nullable();
            $table->longText('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('topics');
    }
};
