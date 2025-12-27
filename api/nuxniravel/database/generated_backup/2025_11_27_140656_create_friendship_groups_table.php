<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('friendship_groups', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('friendship_id')->nullable();
            $table->string('friend_type')->nullable();
            $table->bigInteger('friend_id')->nullable();
            $table->integer('group_id')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('friendship_groups');
    }
};
