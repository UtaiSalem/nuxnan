<?php

use Symfony\Component\Finder\Finder;

require __DIR__ . '/vendor/autoload.php';

// 1. Build a map of ControllerName => FullNamespace
$controllerDir = __DIR__ . '/app/Http/Controllers/Api';
$controllerMap = [];

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($controllerDir));

foreach ($files as $file) {
    if ($file->isDir() || $file->getExtension() !== 'php') {
        continue;
    }

    $className = $file->getBasename('.php');
    $filePath = $file->getRealPath();
    
    // Extract namespace from file content
    $content = file_get_contents($filePath);
    if (preg_match('/namespace\s+(.+?);/', $content, $matches)) {
        $namespace = $matches[1];
        $fullClassName = $namespace . '\\' . $className;
        
        // Handle duplicates (if any, we might need manual intervention or heuristics)
        if (isset($controllerMap[$className])) {
            echo "Warning: Duplicate controller found: $className\n";
            echo "  Existing: " . $controllerMap[$className] . "\n";
            echo "  New: " . $fullClassName . "\n";
            // For now, let's keep the first one or maybe try to match based on folder?
            // If we are processing 'earn/donate.php', we probably want 'Earn\DonateController'.
        } else {
            $controllerMap[$className] = $fullClassName;
        }
        
        // Also store by sub-namespace for better matching if needed?
        // e.g. 'Earn\DonateController' => ...
    }
}

// 2. Update Route Files
$routesDir = __DIR__ . '/routes';
$routeFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($routesDir));

foreach ($routeFiles as $file) {
    if ($file->isDir() || $file->getExtension() !== 'php') {
        continue;
    }
    
    // Skip api.php if we want, or process it too.
    // Skip console.php, channels.php if they don't have controllers.
    
    $filePath = $file->getRealPath();
    $content = file_get_contents($filePath);
    $originalContent = $content;
    
    // Find all 'use App\Http\Controllers\X;'
    // Regex to match the class name
    $pattern = '/use\s+App\\\\Http\\\\Controllers\\\\([a-zA-Z0-9_]+);/';
    
    $content = preg_replace_callback($pattern, function ($matches) use ($controllerMap) {
        $className = $matches[1];
        if (isset($controllerMap[$className])) {
            return "use " . $controllerMap[$className] . ";";
        } else {
            echo "Warning: Controller not found in map: $className\n";
            return $matches[0]; // Keep original if not found
        }
    }, $content);
    
    // Also handle cases where it might be 'use App\Http\Controllers\Sub\X;' (less likely based on error)
    
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "Updated routes file: " . $file->getFilename() . "\n";
    }
}

echo "Done fixing imports.\n";
