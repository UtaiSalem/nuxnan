<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

$sourceDb = 'plearnd_plearnd_db';

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

echo "Tables in $sourceDb:\n";
$tables = DB::connection('source_db')->select('SHOW TABLES');
$foundRoles = false;
$foundUserRoles = false;

foreach ($tables as $table) {
    $tableName = $table->{"Tables_in_$sourceDb"};
    if (str_contains($tableName, 'role')) {
        echo "- $tableName\n";
        if ($tableName === 'roles') $foundRoles = true;
        if ($tableName === 'user_roles') $foundUserRoles = true;
    }
}

if ($foundRoles) {
    echo "\nColumns in 'roles' table:\n";
    $columns = DB::connection('source_db')->select("SHOW COLUMNS FROM roles");
    foreach ($columns as $col) {
        echo "  - {$col->Field} ({$col->Type})\n";
    }
}

if ($foundUserRoles) {
    echo "\nColumns in 'user_roles' table:\n";
    $columns = DB::connection('source_db')->select("SHOW COLUMNS FROM user_roles");
    foreach ($columns as $col) {
        echo "  - {$col->Field} ({$col->Type})\n";
    }
}
