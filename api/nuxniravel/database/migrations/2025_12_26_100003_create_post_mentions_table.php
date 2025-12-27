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
        Schema::create('post_mentions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('user_id'); // The user being mentioned
            $table->unsignedBigInteger('mentioned_by'); // The user who mentioned
            $table->string('username'); // The @username used in the post
            $table->integer('position')->default(0); // Position in content where mention starts
            $table->boolean('is_notified')->default(false);
            $table->timestamps();

            $table->foreign('post_id')
                  ->references('id')
                  ->on('posts')
                  ->onDelete('cascade');
            
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
            
            $table->foreign('mentioned_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
            
            $table->unique(['post_id', 'user_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_mentions');
    }
};
