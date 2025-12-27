<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('supports', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->bigInteger('supporter')->nullable();
            $table->integer('amounts')->nullable();
            $table->integer('duration')->nullable();
            $table->integer('total_views')->nullable();
            $table->integer('remaining_views')->nullable();
            $table->string('slip')->nullable();
            $table->string('media_image')->nullable();
            $table->string('media_link')->nullable();
            $table->timestamp('transfer_date')->nullable();
            $table->string('transfer_time')->nullable();
            $table->boolean('status')->nullable();
            $table->boolean('privacy_settings')->nullable();
            $table->integer('exchange_points')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('supports');
    }
};
