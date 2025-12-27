<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('course_settings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('course_id')->nullable();
            $table->boolean('auto_accept_members')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('course_settings');
    }
};
