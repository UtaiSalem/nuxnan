<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('assignment_answers', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->bigInteger('assignment_id')->nullable();
            $table->text('content')->nullable();
            $table->integer('points')->nullable();
            $table->dateTime('submission_date')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('status')->nullable();
            $table->boolean('late_submission')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('assignment_answers');
    }
};
