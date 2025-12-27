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
        Schema::create('question_options', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('optionable_type');
            $table->unsignedBigInteger('optionable_id');
            $table->text('text')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->text('explanation')->nullable();
            $table->integer('position')->nullable();
            $table->enum('status', ['active', 'archived', 'deleted'])->default('active');
            $table->timestamps();

            $table->index(['optionable_type', 'optionable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_options');
    }
};
