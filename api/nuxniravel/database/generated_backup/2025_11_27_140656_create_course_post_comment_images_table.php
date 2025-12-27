<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('course_post_comment_images', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('course_post_id')->nullable();
            $table->bigInteger('post_comment_id')->nullable();
            $table->string('filename')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('course_post_comment_images');
    }
};
