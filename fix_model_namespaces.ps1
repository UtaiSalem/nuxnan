$controllersPath = "c:\wamp64\www\nuxni\api\nuxniravel\app\Http\Controllers"

# Get all PHP files
$files = Get-ChildItem -Path $controllersPath -Filter "*.php" -Recurse

foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw
    $modified = $false
    
    # Replace App\Models\Play\ with App\Models\
    if ($content -match 'App\\Models\\Play\\') {
        $content = $content -replace 'App\\Models\\Play\\', 'App\Models\'
        $modified = $true
    }
    
    # Replace App\Models\Learn\Course\Info\ with App\Models\
    if ($content -match 'App\\Models\\Learn\\Course\\Info\\') {
        $content = $content -replace 'App\\Models\\Learn\\Course\\Info\\', 'App\Models\'
        $modified = $true
    }
    
    # Replace App\Models\Learn\Course\Lesson\ with App\Models\
    if ($content -match 'App\\Models\\Learn\\Course\\Lesson\\') {
        $content = $content -replace 'App\\Models\\Learn\\Course\\Lesson\\', 'App\Models\'
        $modified = $true
    }
    
    # Replace App\Models\Learn\Course\posts\ with App\Models\
    if ($content -match 'App\\Models\\Learn\\Course\\posts\\') {
        $content = $content -replace 'App\\Models\\Learn\\Course\\posts\\', 'App\Models\'
        $modified = $true
    }
    
    # Replace App\Models\Learn\Course\info\ with App\Models\
    if ($content -match 'App\\Models\\Learn\\Course\\info\\') {
        $content = $content -replace 'App\\Models\\Learn\\Course\\info\\', 'App\Models\'
        $modified = $true
    }
    
    # Replace App\Models\Learn\Course\assignments\ with App\Models\
    if ($content -match 'App\\Models\\Learn\\Course\\assignments\\') {
        $content = $content -replace 'App\\Models\\Learn\\Course\\assignments\\', 'App\Models\'
        $modified = $true
    }
    
    # Replace App\Models\Learn\Course\members\ with App\Models\
    if ($content -match 'App\\Models\\Learn\\Course\\members\\') {
        $content = $content -replace 'App\\Models\\Learn\\Course\\members\\', 'App\Models\'
        $modified = $true
    }
    
    # Replace App\Models\Learn\Course\questions\ with App\Models\
    if ($content -match 'App\\Models\\Learn\\Course\\questions\\') {
        $content = $content -replace 'App\\Models\\Learn\\Course\\questions\\', 'App\Models\'
        $modified = $true
    }
    
    # Replace App\Models\Learn\Course\quizzes\ with App\Models\
    if ($content -match 'App\\Models\\Learn\\Course\\quizzes\\') {
        $content = $content -replace 'App\\Models\\Learn\\Course\\quizzes\\', 'App\Models\'
        $modified = $true
    }
    
    # Replace App\Models\Learn\Course\lessons\ with App\Models\
    if ($content -match 'App\\Models\\Learn\\Course\\lessons\\') {
        $content = $content -replace 'App\\Models\\Learn\\Course\\lessons\\', 'App\Models\'
        $modified = $true
    }
    
    # Replace App\Models\Learn\Course\attendances\ with App\Models\
    if ($content -match 'App\\Models\\Learn\\Course\\attendances\\') {
        $content = $content -replace 'App\\Models\\Learn\\Course\\attendances\\', 'App\Models\'
        $modified = $true
    }
    
    # Replace App\Models\Learn\Course\groups\ with App\Models\
    if ($content -match 'App\\Models\\Learn\\Course\\groups\\') {
        $content = $content -replace 'App\\Models\\Learn\\Course\\groups\\', 'App\Models\'
        $modified = $true
    }
    
    # Replace App\Models\Learn\Course\ with App\Models\
    if ($content -match 'App\\Models\\Learn\\Course\\') {
        $content = $content -replace 'App\\Models\\Learn\\Course\\', 'App\Models\'
        $modified = $true
    }
    
    # Replace App\Models\Learn\Academy\ with App\Models\
    if ($content -match 'App\\Models\\Learn\\Academy\\') {
        $content = $content -replace 'App\\Models\\Learn\\Academy\\', 'App\Models\'
        $modified = $true
    }
    
    # Replace App\Models\Earn\ with App\Models\
    if ($content -match 'App\\Models\\Earn\\') {
        $content = $content -replace 'App\\Models\\Earn\\', 'App\Models\'
        $modified = $true
    }
    
    # Replace App\Models\Shared\ with App\Models\
    if ($content -match 'App\\Models\\Shared\\') {
        $content = $content -replace 'App\\Models\\Shared\\', 'App\Models\'
        $modified = $true
    }
    
    if ($modified) {
        Set-Content $file.FullName $content -NoNewline
        Write-Output "Fixed: $($file.FullName)"
    }
}

Write-Output "Done!"
