<?php

use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

require __DIR__ . '/vendor/autoload.php';

$baseDir = __DIR__ . '/app/Http/Controllers/Api';
$baseNamespace = 'App\\Http\\Controllers\\Api';

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));

foreach ($files as $file) {
    if ($file->isDir()) {
        continue;
    }

    if ($file->getExtension() !== 'php') {
        continue;
    }

    $filePath = $file->getRealPath();
    $relativePath = substr($filePath, strlen($baseDir) + 1);
    $relativePathDir = dirname($relativePath);
    
    if ($relativePathDir === '.') {
        $namespace = $baseNamespace;
    } else {
        $namespace = $baseNamespace . '\\' . str_replace('/', '\\', $relativePathDir);
    }

    $content = file_get_contents($filePath);

    // 1. Update Namespace
    $pattern = '/namespace\s+App\\\\Http\\\\Controllers.*;/';
    $replacement = "namespace $namespace;";
    
    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, $replacement, $content);
    } else {
        // If no namespace found (unlikely), insert it after <?php
        // $content = preg_replace('/<\?php/', "<?php\n\nnamespace $namespace;", $content, 1);
    }

    // 2. Add 'use App\Http\Controllers\Controller;' if missing and extends Controller
    if (strpos($content, 'extends Controller') !== false && strpos($content, 'use App\Http\Controllers\Controller;') === false) {
        $content = preg_replace('/namespace\s+.*;/', "$0\n\nuse App\Http\Controllers\Controller;", $content);
    }

    // 3. Remove 'use Inertia\Inertia;'
    $content = str_replace("use Inertia\\Inertia;\n", "", $content);
    $content = str_replace("use Inertia\\Inertia;", "", $content);

    // 4. Simple Inertia::render replacement (Best effort)
    // Matches: return Inertia::render('Component', [...]);
    // Replaces with: return response()->json([...]);
    // Note: This assumes the second argument is an array or variable.
    // It won't catch everything perfectly but it's a start.
    
    // Pattern: Inertia::render(arg1, arg2)
    // We want to keep arg2.
    
    // Case A: return Inertia::render('Component', $data);
    $content = preg_replace('/Inertia::render\s*\(\s*[\'"][^\'"]+[\'"]\s*,\s*(\$[a-zA-Z0-9_]+)\s*\)/', 'response()->json($1)', $content);

    // Case B: return Inertia::render('Component', [...]);
    // This is hard with regex because of nested brackets. 
    // Let's just replace "Inertia::render(" with "response()->json(" and hope we can fix the arguments manually or via linting?
    // No, that changes the signature. Inertia::render(comp, props) vs response()->json(data, status).
    
    // Let's try to match the common pattern where props is an array [...]
    // We can't easily match balanced brackets with simple regex.
    // Let's just mark them with a TODO comment if we can't parse it easily.
    
    if (strpos($content, 'Inertia::render') !== false) {
         $content = str_replace('Inertia::render', '// TODO: Convert to JSON -> Inertia::render', $content);
    }

    file_put_contents($filePath, $content);
    echo "Updated: $relativePath -> Namespace: $namespace\n";
}

echo "Done.\n";
