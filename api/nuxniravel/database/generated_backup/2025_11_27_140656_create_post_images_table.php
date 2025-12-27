<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('post_images', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('post_id')->nullable();
            $table->string('filename')->nullable();
            $table->text('caption')->nullable();
            $table->integer('order')->nullable();
            $table->integer('likes')->nullable();
            $table->integer('dislikes')->nullable();
            $table->integer('comments')->nullable();
            $table->integer('shares')->nullable();
            $table->integer('views')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('post_images');
    }
};
