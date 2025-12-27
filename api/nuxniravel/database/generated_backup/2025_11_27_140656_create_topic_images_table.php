<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('topic_images', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('topic_id')->nullable();
            $table->string('filename')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('topic_images');
    }
};
