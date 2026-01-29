<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Storage;

echo "Checking Avatar Paths in Database...\n";

$users = User::whereNotNull('profile_photo_path')->get();

$total = $users->count();
$inRoot = 0;
$inSharded = 0;
$orphaned_records = 0;
$urls = 0;

$examplesRoot = [];
$examplesSharded = [];

foreach ($users as $user) {
    $path = $user->profile_photo_path;
    
    if (filter_var($path, FILTER_VALIDATE_URL)) {
        $urls++;
        continue;
    }

    if (!Storage::disk('public')->exists($path)) {
        $orphaned_records++;
        // echo "Missing file for user {$user->id}: $path\n";
    }

    // Check if path has slashes indicating subdirectory
    // avatars/xx/xx/file.jpg has 3 slashes (including start if any)
    // avatars/file.jpg has 1 slash

    // Normalize
    $cleanPath = str_replace('\\', '/', $path);
    $parts = explode('/', $cleanPath);

    // Filter out empty parts
    $parts = array_filter($parts);
    
    // "avatars", "h1", "h2", "filename" = 4 parts
    if (count($parts) >= 4) {
        $inSharded++;
        if (count($examplesSharded) < 3) $examplesSharded[] = $path;
    } else {
        $inRoot++;
        if (count($examplesRoot) < 5) $examplesRoot[] = $path;
    }
}

echo "Total Users with Avatars: $total\n";
echo "External URLs: $urls\n";
echo "In Sharded Structure (Correct): $inSharded\n";
echo "In Root/Legacy Structure (Incorrect): $inRoot\n";
echo "Records pointing to missing files: $orphaned_records\n\n";

    if ($inRoot > 0) {
        echo "Examples of Incorrect Paths:\n";
        foreach ($examplesRoot as $ex) {
            echo "- $ex\n";
        }
    }

    echo "\nChecking 'avatars/' root directory for orphaned files...\n";
    $files = Storage::disk('public')->files('avatars');
    $rootFiles = [];
    foreach ($files as $f) {
        // Exclude directories (though files() shouldn't return them)
        if (is_file(Storage::disk('public')->path($f))) {
            $rootFiles[] = $f;
        }
    }
    
    echo "Found " . count($rootFiles) . " files in 'avatars/' root.\n";
    
    $linkedCount = 0;
    $orphanCount = 0;
    
    // Check if any of these files are in the DB paths (ignoring the 'avatars/' prefix match)
    // DB Path: avatars/filename.jpg
    // File Path: avatars/filename.jpg
    
    // Get all valid paths from DB for quick lookup
    $dbPaths = $users->pluck('profile_photo_path')->toArray();
    
    foreach ($rootFiles as $file) {
        // $file is "avatars/filename.jpg"
        if (in_array($file, $dbPaths)) {
            $linkedCount++;
        } else {
            $orphanCount++;
        }
    }
    
    echo "Linked to DB (Active): $linkedCount\n";
    echo "Not linked (Orphans): $orphanCount\n";
