<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('home_visit_zones', function (Blueprint $table) {
            $table->id();
            $table->string('zone_name')->nullable();
            $table->text('description')->nullable();
            $table->string('zone_code')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_active')->nullable();
            $table->integer('display_order')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('home_visit_zones');
    }
};
