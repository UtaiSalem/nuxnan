<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;

// Define source and destination database names
$sourceDb = 'plearnd_plearnd_db';
$destinationDb = 'nuxni_db';

echo "Starting database copy from '$sourceDb' to '$destinationDb'...\n";

// Configure the source connection dynamically
Config::set('database.connections.source_db', [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => $sourceDb,
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => false,
    'engine' => null,
]);

try {
    // Test connection
    DB::connection('source_db')->getPdo();
    echo "Connected to source database successfully.\n";
} catch (\Exception $e) {
    echo "Failed to connect to source database: " . $e->getMessage() . "\n";
    exit(1);
}

// Disable foreign key checks
Schema::disableForeignKeyConstraints();

// Get all tables from the source database
$tables = DB::connection('source_db')->select('SHOW TABLES');
$tables = array_map(function ($table) use ($sourceDb) {
    return $table->{"Tables_in_$sourceDb"};
}, $tables);

foreach ($tables as $table) {
    // Skip migrations table or others if needed
    if ($table === 'migrations') {
        continue;
    }

    echo "Processing table: $table\n";

    // Check if table exists in destination
    if (!Schema::hasTable($table)) {
        echo "  - Table '$table' does not exist in destination. Skipping...\n";
        continue;
    }

    // Truncate destination table
    DB::table($table)->truncate();
    echo "  - Truncated destination table.\n";

    // Get destination columns
    $destinationColumns = Schema::getColumnListing($table);
    $destinationColumns = array_flip($destinationColumns); // Flip for faster lookup

    // Copy data in chunks
    $count = 0;
    DB::connection('source_db')->table($table)->orderBy(DB::raw('1'))->chunk(1000, function ($rows) use ($table, &$count, $destinationColumns) {
        $data = [];
        foreach ($rows as $row) {
            $rowArray = (array) $row;
            // Filter row data to only include columns that exist in destination
            $filteredRow = array_intersect_key($rowArray, $destinationColumns);
            $data[] = $filteredRow;
        }
        
        if (!empty($data)) {
            DB::table($table)->insert($data);
            $count += count($data);
            echo "  - Copied $count records...\r";
        }
    });
    echo "\n  - Finished copying $count records.\n";
}

// Enable foreign key checks
Schema::enableForeignKeyConstraints();

echo "Database copy completed successfully.\n";
