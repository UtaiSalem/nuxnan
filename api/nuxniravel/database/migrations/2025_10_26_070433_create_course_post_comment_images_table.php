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
        Schema::create('course_post_comment_images', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('course_post_id')->index('course_post_comment_images_course_post_id_foreign');
            $table->unsignedBigInteger('post_comment_id')->index('course_post_comment_images_post_comment_id_foreign');
            $table->string('filename');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_post_comment_images');
    }
};
