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
        Schema::table('post_comments', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_comment_id')->nullable()->after('post_id');
            $table->foreign('parent_comment_id')->references('id')->on('post_comments')->onDelete('cascade');
            $table->index('parent_comment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_comments', function (Blueprint $table) {
            $table->dropForeign(['parent_comment_id']);
            $table->dropColumn('parent_comment_id');
        });
    }
};
