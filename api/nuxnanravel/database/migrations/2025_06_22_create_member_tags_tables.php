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
        // Create member_tags table
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

        // Create pivot table for member-tag relationships
        if (!Schema::hasTable('academy_member_tag')) {
            Schema::create('academy_member_tag', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('academy_member_id');
                $table->unsignedBigInteger('member_tag_id');
                $table->unsignedBigInteger('assigned_by')->nullable();
                $table->timestamps();

                $table->foreign('academy_member_id')->references('id')->on('academy_members')->onDelete('cascade');
                $table->foreign('member_tag_id')->references('id')->on('member_tags')->onDelete('cascade');
                $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');

                $table->unique(['academy_member_id', 'member_tag_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academy_member_tag');
        Schema::dropIfExists('member_tags');
    }
};
