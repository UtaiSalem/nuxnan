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
        if (!Schema::hasTable('member_tags')) {
            Schema::create('member_tags', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('academy_id');
                $table->string('name', 50);
                $table->string('slug', 50)->nullable();
                $table->string('color', 20)->default('#6366f1')->comment('Hex color code');
                $table->text('description')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('academy_id')->references('id')->on('academies')->onDelete('cascade');
                
                $table->unique(['academy_id', 'name']);
                $table->unique(['academy_id', 'slug']);
                $table->index(['academy_id', 'is_active']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_tags');
    }
};
