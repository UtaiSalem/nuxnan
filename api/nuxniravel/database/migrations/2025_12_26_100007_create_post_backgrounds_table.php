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
        // Master table for post backgrounds/themes
        Schema::create('post_backgrounds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('color'); // color, gradient, image, pattern
            $table->string('background_color')->nullable();
            $table->string('background_gradient')->nullable(); // CSS gradient
            $table->string('background_image')->nullable(); // Image URL
            $table->string('text_color')->default('#FFFFFF');
            $table->string('text_alignment')->default('center');
            $table->string('font_family')->nullable();
            $table->string('category')->default('general'); // general, celebration, holiday, etc.
            $table->boolean('is_active')->default(true);
            $table->boolean('is_premium')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_backgrounds');
    }
};
