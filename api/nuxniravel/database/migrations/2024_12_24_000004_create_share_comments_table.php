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
        Schema::create('share_comments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('share_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->text('content');
            $table->unsignedInteger('likes')->default(0);
            $table->unsignedInteger('dislikes')->default(0);
            $table->timestamps();
            
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
        Schema::dropIfExists('share_comments');
    }
};
