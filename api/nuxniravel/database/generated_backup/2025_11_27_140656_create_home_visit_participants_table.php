<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('home_visit_participants', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('home_visit_id')->nullable();
            $table->string('participant_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('home_visit_participants');
    }
};
