<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('guardians')) {
            return;
        }

        $duplicates = DB::table('guardians')
            ->whereNotNull('user_id')
            ->select('academy_id', 'user_id')
            ->groupBy('academy_id', 'user_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($duplicates > 0) {
            throw new RuntimeException("Found {$duplicates} duplicated (academy_id, user_id) pairs in guardians table.");
        }

        $hasIndex = false;
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $dbName = Schema::getConnection()->getDatabaseName();
            $indexCount = DB::table('information_schema.statistics')
                ->where('table_schema', $dbName)
                ->where('table_name', 'guardians')
                ->where('index_name', 'guardians_academy_id_user_id_unique')
                ->count();
            $hasIndex = $indexCount > 0;
        } else {
            $indexes = DB::select("PRAGMA index_list('guardians')");
            foreach ($indexes as $index) {
                if ($index->name === 'guardians_academy_id_user_id_unique') {
                    $hasIndex = true;
                    break;
                }
            }
        }

        if (! $hasIndex) {
            Schema::table('guardians', function (Blueprint $table) {
                $table->unique(['academy_id', 'user_id'], 'guardians_academy_id_user_id_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->dropUnique('guardians_academy_id_user_id_unique');
        });
    }
};
