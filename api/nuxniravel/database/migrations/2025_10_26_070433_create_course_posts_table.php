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
        Schema::create('course_posts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('course_posts_user_id_foreign');
            $table->unsignedBigInteger('academy_id')->nullable()->index('course_posts_academy_id_foreign');
            $table->unsignedBigInteger('course_id')->nullable()->index('course_posts_course_id_foreign');
            $table->unsignedBigInteger('group_id')->nullable()->index('course_posts_group_id_foreign');
            $table->text('content')->nullable();
            $table->string('status')->default('1');
            $table->integer('likes')->default(0);
            $table->integer('dislikes')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('shares')->default(0);
            $table->integer('views')->default(0);
            $table->longText('hashtags')->nullable();
            $table->string('privacy_settings')->default('3');
            $table->string('location')->nullable();
            $table->longText('tags')->nullable();
            $table->string('sentiment')->nullable();
            $table->double('engagement_rate')->default(0);
            $table->string('post_type')->nullable();
            $table->string('source_platform')->nullable();
            $table->timestamp('interacted_at')->nullable();
            $table->longText('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_posts');
    }
};
