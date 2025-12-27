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
        Schema::create('share_dislikes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('share_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->timestamps();
            
            // Prevent duplicate dislikes
            $table->unique(['share_id', 'user_id']);
            
            // Foreign keys
            $table->foreign('share_id')->references('id')->on('shares')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('share_dislikes');
    }
};
