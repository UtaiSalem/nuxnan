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
        Schema::create('course_post_comments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('course_post_id')->index('course_post_comments_course_post_id_foreign');
            $table->unsignedBigInteger('user_id')->index('course_post_comments_user_id_foreign');
            $table->unsignedBigInteger('parent_post_comment_id')->nullable();
            $table->text('content')->nullable();
            $table->integer('likes')->default(0);
            $table->integer('dislikes')->default(0);
            $table->integer('replies')->default(0);
            $table->string('sentiment')->nullable();
            $table->longText('hashtags')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_post_comments');
    }
};
