<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('home_visit_images', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('home_visit_id');
            $table->string('image_path');
            $table->string('image_name');
            $table->string('image_type')->default('evidence');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('uploaded_by')->index('home_visit_images_uploaded_by_foreign');
            $table->timestamps();

            $table->index(['home_visit_id', 'image_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_visit_images');
    }
};
