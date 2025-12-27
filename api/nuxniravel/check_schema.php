<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

try {
    echo "Users table columns:\n";
    echo "===================\n";
    
    $columns = Schema::getColumnListing('users');
    foreach ($columns as $column) {
        echo "- $column\n";
    }
    
    echo "\n\nColumn details:\n";
    echo "===============\n";
    
    $columnDetails = DB::select("DESCRIBE users");
    foreach ($columnDetails as $detail) {
        echo sprintf("%-25s %-20s %s\n", $detail->Field, $detail->Type, $detail->Null);
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
