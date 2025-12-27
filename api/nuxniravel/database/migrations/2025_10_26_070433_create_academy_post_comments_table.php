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
        Schema::create('academy_post_comments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('academy_post_id')->index('academy_post_comments_academy_post_id_foreign');
            $table->unsignedBigInteger('user_id')->index('academy_post_comments_user_id_foreign');
            $table->text('content');
            $table->integer('likes')->default(0);
            $table->integer('dislikes')->default(0);
            $table->integer('replies')->default(0);
            $table->unsignedBigInteger('parent_comment_id')->nullable()->index('academy_post_comments_parent_comment_id_foreign');
            $table->string('sentiment')->nullable();
            $table->longText('hashtags')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academy_post_comments');
    }
};
