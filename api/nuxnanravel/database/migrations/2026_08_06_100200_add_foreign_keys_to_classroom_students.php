<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $targets = [
            'classroom_id' => ['classrooms', 'cs_classroom_fk'],
            'student_id' => ['students', 'cs_student_fk'],
            'academy_id' => ['academies', 'cs_academy_fk'],
            'academic_year_id' => ['academic_years', 'cs_ayear_fk'],
        ];

        foreach ($targets as $column => [$tableName, $name]) {
            if (! Schema::hasTable($tableName)) {
                Log::warning('Skipping missing classroom_students FK target', ['table' => $tableName, 'column' => $column]);

                continue;
            }
            $invalid = DB::table('classroom_students as cs')->leftJoin($tableName.' as target', 'target.id', '=', 'cs.'.$column)->whereNull('target.id')->whereNotNull('cs.'.$column)->count();
            if ($invalid > 0) {
                Log::warning('Deleting rows violating classroom_students FK', ['column' => $column, 'count' => $invalid]);
                DB::table('classroom_students')->whereIn('id', function ($q) use ($tableName, $column) {
                    $q->from('classroom_students as cs')->leftJoin($tableName.' as target', 'target.id', '=', 'cs.'.$column)->whereNull('target.id')->whereNotNull('cs.'.$column)->select('cs.id');
                })->delete();
            }
        }

        Schema::table('classroom_students', function (Blueprint $table) use ($targets) {
            foreach ($targets as $column => [$tableName, $name]) {
                if (Schema::hasTable($tableName)) {
                    $foreign = $table->foreign($column, $name)->references('id')->on($tableName);
                    $column === 'classroom_id' ? $foreign->restrictOnDelete() : $foreign->cascadeOnDelete();
                }
            }
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql' || ! Schema::hasTable('classroom_students')) {
            return;
        }

        $names = ['cs_classroom_fk', 'cs_student_fk', 'cs_academy_fk', 'cs_ayear_fk'];
        $existing = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', 'classroom_students')
            ->whereIn('CONSTRAINT_NAME', $names)
            ->pluck('CONSTRAINT_NAME')
            ->all();

        Schema::table('classroom_students', function (Blueprint $table) use ($existing) {
            foreach ($existing as $name) {
                $table->dropForeign($name);
            }
        });
    }
};
