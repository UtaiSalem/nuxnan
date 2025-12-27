<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('course_group_members', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('course_id')->nullable();
            $table->bigInteger('group_id')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->string('status')->nullable();
            $table->integer('last_accessed_tab')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('course_group_members');
    }
};
