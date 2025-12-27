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
        Schema::create('shares', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->string('shareable_type'); // Post, CoursePost, AcademyPost, etc.
            $table->unsignedBigInteger('shareable_id');
            $table->text('share_comment')->nullable();
            $table->enum('privacy', ['public', 'friends', 'private'])->default('public');
            $table->unsignedInteger('likes')->default(0);
            $table->unsignedInteger('dislikes')->default(0);
            $table->unsignedInteger('comments')->default(0);
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['shareable_type', 'shareable_id']);
            $table->index('created_at');
            
            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shares');
    }
};
