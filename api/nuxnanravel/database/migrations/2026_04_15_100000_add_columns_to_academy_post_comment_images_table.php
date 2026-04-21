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
        Schema::table('academy_post_comment_images', function (Blueprint $table) {
            if (!Schema::hasColumn('academy_post_comment_images', 'academy_post_comment_id')) {
                $table->unsignedBigInteger('academy_post_comment_id')->nullable();
                $table->foreign('academy_post_comment_id')
                    ->references('id')
                    ->on('academy_post_comments')
                    ->onDelete('cascade');
            }
            if (!Schema::hasColumn('academy_post_comment_images', 'filename')) {
                $table->string('filename')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academy_post_comment_images', function (Blueprint $table) {
            if (Schema::hasColumn('academy_post_comment_images', 'academy_post_comment_id')) {
                $table->dropForeign(['academy_post_comment_id']);
                $table->dropColumn('academy_post_comment_id');
            }
            if (Schema::hasColumn('academy_post_comment_images', 'filename')) {
                $table->dropColumn('filename');
            }
        });
    }
};
