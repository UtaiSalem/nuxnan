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
        Schema::create('question_images', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('imageable_type');
            $table->unsignedBigInteger('imageable_id');
            $table->string('filename');
            $table->timestamps();

            $table->index(['imageable_type', 'imageable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_images');
    }
};
