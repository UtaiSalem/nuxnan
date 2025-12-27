<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('interactions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->string('subject_type')->nullable();
            $table->bigInteger('subject_id')->nullable();
            $table->string('relation')->nullable();
            $table->double('relation_value')->nullable();
            $table->string('relation_type')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('interactions');
    }
};
