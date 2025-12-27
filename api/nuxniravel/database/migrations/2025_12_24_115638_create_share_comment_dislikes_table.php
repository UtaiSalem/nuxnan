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
        Schema::create('share_comment_dislikes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('share_comment_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('share_comment_id')->references('id')->on('share_comments')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Prevent duplicate dislikes
            $table->unique(['share_comment_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('share_comment_dislikes');
    }
};
