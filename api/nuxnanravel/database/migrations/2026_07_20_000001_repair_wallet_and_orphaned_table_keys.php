<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }
        $schema = DB::getDatabaseName();
        $hasIndex = static function (string $table, string $index) use ($schema): bool {
            return DB::table('information_schema.statistics')->where('table_schema', $schema)->where('table_name', $table)->where('index_name', $index)->exists();
        };
        $hasForeign = static function (string $table, string $name, string $column, string $refTable, string $delete) use ($schema): bool {
            return DB::table('information_schema.key_column_usage as kcu')
                ->join('information_schema.referential_constraints as rc', function ($join) {
                    $join->on('rc.constraint_schema', '=', 'kcu.constraint_schema')->on('rc.table_name', '=', 'kcu.table_name')->on('rc.constraint_name', '=', 'kcu.constraint_name');
                })
                ->where('kcu.constraint_schema', $schema)->where('kcu.table_name', $table)->where('kcu.constraint_name', $name)->where('kcu.column_name', $column)->where('kcu.referenced_table_name', $refTable)->where('rc.delete_rule', $delete)->exists();
        };
        $repairs = [
            ['user_stats_recalculation_logs', [['user_stats_recalculation_logs_user_id_created_at_index', ['user_id', 'created_at'], false], ['user_stats_recalculation_logs_run_id_index', ['run_id'], false]], [['user_stats_recalculation_logs_user_id_foreign', 'user_id', 'users', 'CASCADE']]],
            ['videos', [['videos_user_id_created_at_index', ['user_id', 'created_at'], false], ['videos_privacy_settings_index', ['privacy_settings'], false]], [['videos_user_id_foreign', 'user_id', 'users', 'CASCADE']]],
            ['visitor_counters', [], []],
            ['wallet_transactions', [['wallet_transactions_user_id_index', ['user_id'], false], ['wallet_transactions_transaction_type_index', ['transaction_type'], false], ['wallet_transactions_created_at_index', ['created_at'], false], ['wallet_transactions_reference_number_index', ['reference_number'], false], ['wallet_transactions_reviewed_by_index', ['reviewed_by'], false], ['wallet_transactions_idempotency_key_unique', ['idempotency_key'], true], ['wallet_transactions_transaction_type_status_index', ['transaction_type', 'status'], false], ['wallet_transactions_user_id_transaction_type_status_index', ['user_id', 'transaction_type', 'status'], false]], [['wallet_transactions_user_id_foreign', 'user_id', 'users', 'CASCADE'], ['wallet_transactions_reviewed_by_foreign', 'reviewed_by', 'users', 'SET NULL']]],
            ['wallet_deposit_requests', [['wallet_deposit_requests_user_id_index', ['user_id'], false], ['wallet_deposit_requests_reviewed_by_index', ['reviewed_by'], false], ['wallet_deposit_requests_wallet_transaction_id_index', ['wallet_transaction_id'], false], ['wallet_deposit_requests_status_index', ['status'], false], ['wallet_deposit_requests_created_at_index', ['created_at'], false]], [['wallet_deposit_requests_user_id_foreign', 'user_id', 'users', 'CASCADE'], ['wallet_deposit_requests_reviewed_by_foreign', 'reviewed_by', 'users', 'SET NULL'], ['wallet_deposit_requests_wallet_transaction_id_foreign', 'wallet_transaction_id', 'wallet_transactions', 'SET NULL']]],
            ['xp_events', [['academy_id_occurred_at_index', ['academy_id', 'occurred_at'], false], ['classroom_group_id_occurred_at_index', ['classroom_group_id', 'occurred_at'], false], ['source_index', ['source'], false]], [['xp_events_academy_id_foreign', 'academy_id', 'academies', 'CASCADE'], ['xp_events_user_id_foreign', 'user_id', 'users', 'SET NULL'], ['xp_events_classroom_group_id_foreign', 'classroom_group_id', 'academy_groups', 'SET NULL']]],
        ];
        $errors = [];
        $nullableOrphans = [];
        $tableExists = static function (string $table) use ($schema): bool {
            return DB::table('information_schema.tables')->where('table_schema', $schema)->where('table_name', $table)->exists();
        };
        foreach ($repairs as [$table, $indexes, $foreigns]) {
            if (! $tableExists($table)) {
                $errors[] = "{$table}: table is missing";

                continue;
            }
            if (! $hasIndex($table, 'PRIMARY')) {
                $nullIds = DB::table($table)->whereNull('id')->count();
                $duplicates = DB::table($table)->select('id')->whereNotNull('id')->groupBy('id')->havingRaw('COUNT(*) > 1')->get();
                if ($nullIds > 0) {
                    $errors[] = "{$table}.id: {$nullIds} NULL ids";
                }
                if ($duplicates->isNotEmpty()) {
                    $errors[] = "{$table}.id: {$duplicates->count()} duplicate ids (samples: ".implode(', ', $duplicates->take(20)->pluck('id')->all()).')';
                }
            }
            foreach ($indexes as [$name, $columns, $unique]) {
                if ($unique && ! $hasIndex($table, $name)) {
                    $duplicates = DB::table($table)->select('id', 'idempotency_key')->whereNotNull('idempotency_key')->groupBy('idempotency_key')->havingRaw('COUNT(*) > 1')->get();
                    if ($duplicates->isNotEmpty()) {
                        $errors[] = "{$table}.idempotency_key: {$duplicates->count()} duplicate values (samples: ".implode(', ', $duplicates->take(20)->pluck('idempotency_key')->all()).')';
                    }
                }
            }
            foreach ($foreigns as [$name, $column, $refTable, $delete]) {
                if ($hasForeign($table, $name, $column, $refTable, $delete)) {
                    continue;
                }
                $orphans = DB::table($table)->leftJoin($refTable, "{$table}.{$column}", '=', "{$refTable}.id")->whereNotNull("{$table}.{$column}")->whereNull("{$refTable}.id");
                $count = $orphans->count();
                if ($count === 0) {
                    continue;
                }
                if ($delete === 'SET NULL') {
                    $nullableOrphans[] = [$table, $column, $refTable, $count];

                    continue;
                }
                $samples = $orphans->limit(20)->pluck("{$table}.{$column}")->all();
                $errors[] = "{$table}.{$column} -> {$refTable}.id: {$count} orphaned values (samples: ".implode(', ', $samples).')';
            }
        }
        if ($errors !== []) {
            throw new RuntimeException("Wallet key repair preflight failed:\n- ".implode("\n- ", $errors));
        }
        foreach ($nullableOrphans as [$table, $column, $refTable, $count]) {
            Log::warning('Wallet key repair nulling orphaned foreign keys', compact('table', 'column', 'count'));
            DB::table($table)->whereNotNull($column)->whereNotExists(function ($query) use ($table, $column, $refTable) {
                $query->select(DB::raw(1))->from($refTable)->whereColumn("{$refTable}.id", "{$table}.{$column}");
            })->update([$column => null]);
        }
        $repair = static function (string $table, array $indexes, array $foreigns) use ($schema, $hasIndex, $hasForeign): void {
            if (! $hasIndex($table, 'PRIMARY')) {
                DB::statement("ALTER TABLE `{$table}` ADD PRIMARY KEY (`id`)");
            }
            $id = DB::table('information_schema.columns')->where('table_schema', $schema)->where('table_name', $table)->where('column_name', 'id')->first();
            if ($id && strtolower($id->EXTRA ?? '') !== 'auto_increment') {
                DB::statement("ALTER TABLE `{$table}` MODIFY `id` {$id->COLUMN_TYPE} NOT NULL AUTO_INCREMENT");
            }
            foreach ($indexes as [$name, $columns, $unique]) {
                if (! $hasIndex($table, $name)) {
                    DB::statement('ALTER TABLE `'.$table.'` ADD '.($unique ? 'UNIQUE ' : '').'INDEX `'.$name.'` (`'.implode('`,`', $columns).'`)');
                }
            }
            foreach ($foreigns as [$name, $column, $refTable, $delete]) {
                if (! $hasForeign($table, $name, $column, $refTable, $delete)) {
                    DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` FOREIGN KEY (`{$column}`) REFERENCES `{$refTable}` (`id`) ON DELETE {$delete}");
                }
            }
        };
        foreach ($repairs as [$table, $indexes, $foreigns]) {
            $repair($table, $indexes, $foreigns);
        }
    }

    public function down(): void {}
};
