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
        Schema::create('lessons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('course_id')->index('lessons_course_id_foreign');
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('content')->nullable();
            $table->text('video_url')->nullable();
            $table->text('youtube_url')->nullable();
            $table->integer('duration')->nullable();
            $table->integer('min_read')->nullable()->default(1);
            $table->integer('view_count')->default(0);
            $table->integer('like_count')->nullable()->default(0);
            $table->integer('dislike_count')->default(0);
            $table->integer('comment_count')->default(0);
            $table->integer('share_count')->default(0);
            $table->integer('download_count')->default(0);
            $table->longText('assigned_groups')->nullable();
            $table->enum('status', ['0', '1'])->default('1');
            $table->unsignedInteger('order')->nullable();
            $table->unsignedInteger('point_tuition_fee')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
