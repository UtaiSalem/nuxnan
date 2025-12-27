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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('email')->unique();
            $table->string('phone_number')->nullable();
            $table->string('password')->nullable(); // Nullable for social login
            
            // Referral System
            $table->string('suggester_code')->nullable();
            $table->string('personal_code')->unique()->nullable();
            $table->string('reference_code')->unique()->nullable();
            $table->integer('no_of_ref')->default(0);
            
            // Wallet & Points
            $table->decimal('pp', 10, 2)->default(0);
            $table->decimal('wallet', 10, 2)->default(0);
            
            // Profile & Verification
            $table->string('profile_photo_path')->nullable();
            $table->boolean('verified')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            
            // Social Login IDs
            $table->string('google_id')->nullable();
            $table->string('facebook_id')->nullable();
            $table->string('twitter_id')->nullable();
            $table->string('linkedin_id')->nullable();
            $table->string('github_id')->nullable();
            
            // Two Factor Authentication
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            
            // Team Support
            $table->foreignId('current_team_id')->nullable();
            
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
