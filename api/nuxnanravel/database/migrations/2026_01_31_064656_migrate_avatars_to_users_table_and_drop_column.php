<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Copy data from user_profiles.profile_picture to users.profile_photo_path
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                UPDATE users u
                JOIN user_profiles p ON u.id = p.user_id
                SET u.profile_photo_path = p.profile_picture
                WHERE (u.profile_photo_path IS NULL OR u.profile_photo_path = '')
                AND p.profile_picture IS NOT NULL
                AND p.profile_picture != ''
            ");
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::statement("
                UPDATE users 
                SET profile_photo_path = (
                    SELECT profile_picture 
                    FROM user_profiles 
                    WHERE user_profiles.user_id = users.id
                )
                WHERE (profile_photo_path IS NULL OR profile_photo_path = '')
                AND EXISTS (
                    SELECT 1 
                    FROM user_profiles 
                    WHERE user_profiles.user_id = users.id 
                    AND profile_picture IS NOT NULL 
                    AND profile_picture != ''
                )
            ");
        }

        // 2. Drop the column
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn('profile_picture');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('profile_picture')->nullable()->after('interests');
        });
    }
};
