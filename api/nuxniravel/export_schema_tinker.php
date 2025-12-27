<?php
$tables = DB::select('SHOW TABLES');
$tableNames = [];
foreach ($tables as $t) {
    $vars = get_object_vars($t);
    $tableNames[] = array_values($vars)[0];
}

$schema = [];
foreach ($tableNames as $table) {
    if ($table === 'migrations') continue;
    try {
        $columns = Schema::getColumnListing($table);
        $tableSchema = [];
        foreach ($columns as $column) {
            $type = Schema::getColumnType($table, $column);
            $tableSchema[$column] = $type;
        }
        $schema[$table] = $tableSchema;
    } catch (\Exception $e) {
    }
}
echo "JSON_START\n";
echo json_encode($schema, JSON_PRETTY_PRINT);
echo "\nJSON_END";
