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
        Schema::create('post_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->string('name'); // Place name (e.g., "Siam Paragon")
            $table->string('address')->nullable(); // Full address
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('place_id')->nullable(); // Google Places ID or similar
            $table->string('place_type')->nullable(); // restaurant, cafe, shopping_mall, etc.
            $table->string('icon_url')->nullable();
            $table->longText('metadata')->nullable(); // Additional JSON data
            $table->timestamps();

            $table->foreign('post_id')
                  ->references('id')
                  ->on('posts')
                  ->onDelete('cascade');
            
            $table->index(['latitude', 'longitude']);
            $table->index('place_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_locations');
    }
};
