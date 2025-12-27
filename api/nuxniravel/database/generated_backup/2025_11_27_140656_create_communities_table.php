<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('communities', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('creater_id')->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('creator_id')->nullable();
            $table->timestamp('creation_date')->nullable();
            $table->string('privacy_settings')->nullable();
            $table->string('category')->nullable();
            $table->bigInteger('member_count')->nullable();
            $table->text('rules')->nullable();
            $table->string('community_picture')->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('communities');
    }
};
