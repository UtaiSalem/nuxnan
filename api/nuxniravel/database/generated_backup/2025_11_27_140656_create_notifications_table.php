<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->text('content')->nullable();
            $table->boolean('read_status')->nullable();
            $table->string('action_url')->nullable();
            $table->string('type')->nullable();
            $table->bigInteger('sender_id')->nullable();
            $table->bigInteger('related_id')->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};
