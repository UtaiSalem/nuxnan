<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('sender_id')->nullable();
            $table->bigInteger('receiver_id')->nullable();
            $table->text('content')->nullable();
            $table->boolean('read_status')->nullable();
            $table->boolean('deleted_by_sender')->nullable();
            $table->boolean('deleted_by_receiver')->nullable();
            $table->string('attachment')->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('messages');
    }
};
