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
        Schema::create('typing_words', function (Blueprint $table) {
            $table->id();
            $table->string('category', 50)->default('general');
            $table->enum('language', ['th', 'en', 'ar'])->default('en');
            $table->enum('difficulty', ['beginner', 'easy', 'normal', 'hard', 'expert']);
            $table->string('text');
            $table->string('pronunciation')->nullable();
            $table->string('meaning', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['language', 'difficulty'], 'idx_words_lang_diff');
            $table->index('category', 'idx_category');
            $table->index('is_active', 'idx_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('typing_words');
    }
};
