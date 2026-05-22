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
        Schema::create('level_definitions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('level')->unique();
            $table->string('name', 64);
            $table->string('name_th', 64)->nullable();
            $table->unsignedInteger('xp_required');
            $table->string('icon')->nullable();
            $table->string('color', 16)->nullable();
            $table->string('badge_url')->nullable();
            $table->json('perks')->nullable();
            $table->timestamps();

            $table->index('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('level_definitions');
    }
};
