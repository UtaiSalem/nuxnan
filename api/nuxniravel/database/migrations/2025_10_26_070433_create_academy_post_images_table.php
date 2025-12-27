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
        Schema::create('academy_post_images', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('academy_post_id')->index('academy_post_images_academy_post_id_foreign');
            $table->string('filename');
            $table->text('caption')->nullable();
            $table->integer('order')->default(0);
            $table->integer('likes')->default(0);
            $table->integer('dislikes')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('shares')->default(0);
            $table->integer('views')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academy_post_images');
    }
};
