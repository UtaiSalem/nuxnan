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
        Schema::create('typing_sentences', function (Blueprint $table) {
            $table->id();
            $table->enum('language', ['th', 'en', 'ar'])->default('en');
            $table->enum('difficulty', ['beginner', 'easy', 'normal', 'hard', 'expert']);
            $table->text('text');
            $table->integer('word_count')->default(0);
            $table->string('source', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['language', 'difficulty'], 'idx_lang_diff');
        });

        // Add generated column separately as it might vary by DB engine
        DB::statement('ALTER TABLE typing_sentences ADD char_count INT AS (CHAR_LENGTH(text)) STORED');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('typing_sentences');
    }
};
