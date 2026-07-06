<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Updates legacy profile_photo_path values to remove '/storage/' or 'storage/' prefix.
     * The new system expects paths like 'avatars/xx/xx/filename.jpg' or 'profile-photos/filename.jpg'
     * without the storage prefix, as the Storage facade handles the URL generation.
     */
    public function up(): void
    {
        // Get users with profile photos using DB facade to avoid model-specific traits like SoftDeletes
        $users = DB::table('users')->whereNotNull('profile_photo_path')->get();
        $count = 0;

        foreach ($users as $user) {
            $currentPath = $user->profile_photo_path;

            // Skip social login URLs
            if (filter_var($currentPath, FILTER_VALIDATE_URL)) {
                continue;
            }

            // Determine Hashed Path
            // md5(1) = c4ca... -> avatars/c4/ca/filename.jpg
            $hash = md5($user->id);
            $folder1 = substr($hash, 0, 2);
            $folder2 = substr($hash, 2, 2);

            // Check if ALREADY in correct sharded format
            if (preg_match("#^avatars/{$folder1}/{$folder2}/#", $currentPath)) {
                continue;
            }

            // Clean up the path: remove any leading slash, 'storage/', or '/storage/' prefix
            // This finds the "real" relative path in storage/app/public
            $cleanPath = preg_replace('#^/?(storage/)?#', '', $currentPath);

            // Check if source file exists
            if (! Storage::disk('public')->exists($cleanPath)) {
                Log::warning("File not found for user {$user->id}: {$cleanPath}");

                continue; // File missing, skip or handle? Skipping for safety.
            }

            // Determine extension
            $extension = pathinfo($cleanPath, PATHINFO_EXTENSION);
            if (! $extension) {
                $extension = 'jpg';
            }

            // Generate new filename and path
            $newFilename = $user->id.'_'.time().'_'.uniqid().'.'.$extension;
            $newPath = "avatars/{$folder1}/{$folder2}/{$newFilename}";

            try {
                // Move file
                Storage::disk('public')->move($cleanPath, $newPath);

                // Update Database with new relative path
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['profile_photo_path' => $newPath]);

                $count++;
            } catch (Exception $e) {
                Log::error("Failed to migrate avatar for user {$user->id}: ".$e->getMessage());
            }
        }

        Log::info("Migrated {$count} users to sharded avatar structure.");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a data cleanup migration.
        // Reversing would require knowing which records were changed,
        // which is not tracked. Generally, this is a one-way cleanup.
        // If needed, you could prepend 'storage/' back, but it's discouraged.
    }
};
