<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('assignment_answer_images', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('assignment_answer_id')->nullable();
            $table->string('filename')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('assignment_answer_images');
    }
};
