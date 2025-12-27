<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->string('optionable_type')->nullable();
            $table->bigInteger('optionable_id')->nullable();
            $table->text('text')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->text('explanation')->nullable();
            $table->integer('position')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('question_options');
    }
};
