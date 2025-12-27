<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('home_visit_images', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('home_visit_id')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_name')->nullable();
            $table->string('image_type')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('uploaded_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('home_visit_images');
    }
};
