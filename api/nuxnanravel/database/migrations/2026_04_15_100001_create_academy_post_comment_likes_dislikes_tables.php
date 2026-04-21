<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('academy_post_comment_likes')) {
            Schema::create('academy_post_comment_likes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('academy_post_comment_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();

                $table->foreign('academy_post_comment_id')
                    ->references('id')
                    ->on('academy_post_comments')
                    ->onDelete('cascade');

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');

                $table->unique(['academy_post_comment_id', 'user_id'], 'comment_like_unique');
            });
        }

        if (!Schema::hasTable('academy_post_comment_dislikes')) {
            Schema::create('academy_post_comment_dislikes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('academy_post_comment_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();

                $table->foreign('academy_post_comment_id')
                    ->references('id')
                    ->on('academy_post_comments')
                    ->onDelete('cascade');

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');

                $table->unique(['academy_post_comment_id', 'user_id'], 'comment_dislike_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_post_comment_likes');
        Schema::dropIfExists('academy_post_comment_dislikes');
    }
};
