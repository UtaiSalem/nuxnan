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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->text('bio')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('gender')->nullable();
            $table->string('location')->nullable();
            $table->string('website')->nullable();
            $table->text('interests')->nullable();
            $table->string('profile_picture')->nullable(); // nuxni uses avatar in users table, but keep for compatibility
            $table->string('cover_image_url')->nullable(); // nuxni name
            $table->string('cover_image')->nullable(); // plearnd name, maybe alias or keep both? Let's keep both for now or map.
            $table->longText('social_media_links')->nullable();
            $table->string('privacy_settings')->default('public');
            $table->unsignedBigInteger('followers')->default(0);
            $table->unsignedBigInteger('following')->default(0);
            $table->unsignedBigInteger('friends')->default(0);
            $table->timestamp('join_date')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
