<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('donate_recipients', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('donate_id')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->integer('privacy_settings')->nullable();
            $table->integer('status')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('donate_recipients');
    }
};
