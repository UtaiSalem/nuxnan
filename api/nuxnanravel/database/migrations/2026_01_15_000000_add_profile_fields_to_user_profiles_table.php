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
        Schema::table('user_profiles', function (Blueprint $table) {
            // Personal Information
            $table->string('phone_number')->nullable()->after('metadata');
            $table->text('address')->nullable()->after('phone_number');
            $table->string('city')->nullable()->after('address');
            $table->string('country')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('country');
            
            // Professional Information
            $table->string('job_title')->nullable()->after('postal_code');
            $table->string('company')->nullable()->after('job_title');
            $table->string('industry')->nullable()->after('company');
            $table->text('skills')->nullable()->after('industry');
            $table->string('experience_years')->nullable()->after('skills');
            
            // Privacy Settings
            $table->boolean('show_email')->default(false)->after('experience_years');
            $table->boolean('show_phone')->default(false)->after('show_email');
            $table->boolean('show_birthdate')->default(false)->after('show_phone');
            $table->boolean('show_location')->default(false)->after('show_birthdate');
            $table->boolean('allow_friend_requests')->default(true)->after('show_location');
            $table->boolean('allow_messages')->default(true)->after('allow_friend_requests');
            $table->boolean('show_online_status')->default(true)->after('allow_messages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            // Personal Information
            $table->dropColumn(['phone_number', 'address', 'city', 'country', 'postal_code']);
            
            // Professional Information
            $table->dropColumn(['job_title', 'company', 'industry', 'skills', 'experience_years']);
            
            // Privacy Settings
            $table->dropColumn(['show_email', 'show_phone', 'show_birthdate', 'show_location', 'allow_friend_requests', 'allow_messages', 'show_online_status']);
        });
    }
};
