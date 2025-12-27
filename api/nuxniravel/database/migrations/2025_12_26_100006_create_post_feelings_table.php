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
        // Master table for available feelings
        Schema::create('feelings', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // happy, sad, excited, etc.
            $table->string('name_th')->nullable(); // Thai translation
            $table->string('icon')->nullable(); // Emoji or icon code
            $table->string('icon_url')->nullable(); // Custom icon URL
            $table->string('category')->default('feeling'); // feeling, activity
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Master table for activity types
        Schema::create('activities_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // watching, listening, reading, playing, etc.
            $table->string('name_th')->nullable();
            $table->string('icon')->nullable();
            $table->string('icon_url')->nullable();
            $table->string('preposition')->default(''); // "to", "a", etc.
            $table->string('preposition_th')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities_types');
        Schema::dropIfExists('feelings');
    }
};
