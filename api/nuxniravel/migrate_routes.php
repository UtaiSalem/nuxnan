<?php

use Illuminate\Support\Str;

$sourceDir = 'c:/wamp64/www/plearnd/routes';
$targetDir = 'c:/wamp64/www/nuxni/api/nuxniravel/routes';

$filesToMigrate = [
    'earn/donate.php',
    'earn/advert.php',
    'play/post.php',
    'play/game.php',
    'learn/academy.php',
    'learn/course.php',
    'apis/v2/course.php',
    'learn/student.php',
    'homevisit/homevisit.php',
    'studentcard/studentcard.php',
    'apis/academies.php',
];

foreach ($filesToMigrate as $file) {
    $sourcePath = "$sourceDir/$file";
    $targetPath = "$targetDir/$file";

    if (!file_exists($sourcePath)) {
        echo "Skipping missing file: $file\n";
        continue;
    }

    $content = file_get_contents($sourcePath);

    // Update Namespaces
    // Simple regex to add 'Api\' prefix to controllers if they are not already
    // This is tricky because we need to know the full namespace.
    // But we can assume most are imported or used fully qualified.
    
    // Strategy: 
    // 1. Replace "use App\Http\Controllers\" with "use App\Http\Controllers\Api\"
    // 2. But wait, the folder structure changed too (Play/Learn/Earn).
    //    We need to map old controllers to new namespaces.
    
    // Let's do a best-effort mapping based on the file path or known locations.
    // Or, since we already updated the controllers, we can just try to fix the imports.
    
    // Actually, the easiest way is to just copy them and then let the user (or me) fix the imports manually 
    // because the mapping is complex (Play/Learn/Earn).
    // BUT, I can try to automate adding "Api" prefix.
    
    // Let's just copy them for now and I will fix the imports in the main api.php or in the files.
    
    $dir = dirname($targetPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    
    // Remove Inertia::render
    $content = preg_replace('/Inertia::render\s*\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,\s*(\[[^\]]+\]))?\s*\)/', "response()->json(['success' => true, 'component' => '$1'])", $content);
    
    file_put_contents($targetPath, $content);
    echo "Copied: $file\n";
}

echo "Done copying route files.\n";
