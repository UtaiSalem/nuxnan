<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = array_map('current', DB::select('SHOW TABLES'));
$schema = [];

foreach ($tables as $table) {
    // Skip migrations table
    if ($table === 'migrations') continue;

    $columns = Schema::getColumnListing($table);
    $tableSchema = [];
    foreach ($columns as $column) {
        $type = Schema::getColumnType($table, $column);
        $tableSchema[$column] = $type;
    }
    $schema[$table] = $tableSchema;
}

echo json_encode($schema, JSON_PRETTY_PRINT);
