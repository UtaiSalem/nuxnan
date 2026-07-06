<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Storage;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$users = User::where('profile_photo_path', 'LIKE', '%localhost%')->get();

echo 'Found '.$users->count()." users with 'localhost' in profile_photo_path.\n";

foreach ($users as $user) {
    $originalPath = $user->profile_photo_path;

    // Check if it's a localhost URL
    if (strpos($originalPath, 'localhost') !== false) {
        // Extract relative path
        // Pattern: http://localhost:8000/storage/avatars/... -> avatars/...
        // Pattern: http://localhost:8000/storage/profile-photos/... -> profile-photos/...

        $relativePath = preg_replace('#^https?://[^/]+/storage/#', '', $originalPath);

        echo "Fixing User ID {$user->id}: \n";
        echo "  Old: {$originalPath}\n";
        echo "  New: {$relativePath}\n";

        // Verify if file exists (optional, but good for safety)
        // Note: Storage::disk('public')->exists($relativePath)

        if (Storage::disk('public')->exists($relativePath)) {
            $user->profile_photo_path = $relativePath;
            $user->saveQuietly();
            echo "  [OK] Updated.\n";
        } else {
            echo "  [WARNING] File not found in storage: {$relativePath}. Skipping DB update.\n";
            // Optional: Force update anyway if we trust the path structure?
            // Better safe: strictly if file exists.
            // Should we try to download it from the localhost URL? No, we can't access users localhost.
            // Assume the file IS on disk, just the DB path is full URL.

            // Double check: maybe the file is there.
        }
    }
}

echo "Done.\n";
