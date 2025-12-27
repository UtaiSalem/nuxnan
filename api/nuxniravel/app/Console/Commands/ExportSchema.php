<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExportSchema extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schema:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export database schema to JSON';

    /**
     * Execute the console command.
     */
    public function handle()
    {
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
                $this->error("Error processing table $table: " . $e->getMessage());
            }
        }

        file_put_contents('schema.json', json_encode($schema, JSON_PRETTY_PRINT));
        $this->info('Schema exported to schema.json');
    }
}
