<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$course = App\Models\Course::find(1);
if ($course) {
    echo json_encode([
        'id' => $course->id,
        'name' => $course->name,
        'slug' => $course->slug
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    echo "No course found";
}
