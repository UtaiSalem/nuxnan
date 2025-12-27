<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('friendships', function (Blueprint $table) {
            $table->id();
            $table->string('sender_type')->nullable();
            $table->bigInteger('sender_id')->nullable();
            $table->string('recipient_type')->nullable();
            $table->bigInteger('recipient_id')->nullable();
            $table->string('status')->nullable();
            $table->bigInteger('action_user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('friendships');
    }
};
