<?php

use Symfony\Component\Finder\Finder;

require __DIR__ . '/vendor/autoload.php';

$baseDir = __DIR__ . '/app/Http/Controllers/Api';

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));

foreach ($files as $file) {
    if ($file->isDir()) {
        continue;
    }

    if ($file->getExtension() !== 'php') {
        continue;
    }

    $filePath = $file->getRealPath();
    $content = file_get_contents($filePath);
    $originalContent = $content;

    // Pattern 1: Inertia::render with array data
    // return // TODO: Convert to JSON -> Inertia::render('Component', [
    // becomes
    // return response()->json([
    
    $pattern1 = '/return\s+\/\/\s+TODO:\s+Convert\s+to\s+JSON\s+->\s+Inertia::render\s*\(\s*[\'"][^\'"]+[\'"]\s*,\s*\[/';
    $replacement1 = 'return response()->json([';
    
    $content = preg_replace($pattern1, $replacement1, $content);

    // Pattern 2: Inertia::render without array data (just component name)
    // return // TODO: Convert to JSON -> Inertia::render('Component');
    // becomes
    // return response()->json(['success' => true]);
    
    $pattern2 = '/return\s+\/\/\s+TODO:\s+Convert\s+to\s+JSON\s+->\s+Inertia::render\s*\(\s*[\'"][^\'"]+[\'"]\s*\)\s*;/';
    $replacement2 = "return response()->json(['success' => true]);";
    
    $content = preg_replace($pattern2, $replacement2, $content);

    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "Updated: " . $file->getFilename() . "\n";
    }
}

echo "Done.\n";
