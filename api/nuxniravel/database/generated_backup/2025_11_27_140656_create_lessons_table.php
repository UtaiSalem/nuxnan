<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('course_id')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('content')->nullable();
            $table->text('video_url')->nullable();
            $table->text('youtube_url')->nullable();
            $table->integer('duration')->nullable();
            $table->integer('min_read')->nullable();
            $table->integer('view_count')->nullable();
            $table->integer('like_count')->nullable();
            $table->integer('dislike_count')->nullable();
            $table->integer('comment_count')->nullable();
            $table->integer('share_count')->nullable();
            $table->integer('download_count')->nullable();
            $table->longText('assigned_groups')->nullable();
            $table->string('status')->nullable();
            $table->integer('order')->nullable();
            $table->integer('point_tuition_fee')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lessons');
    }
};
