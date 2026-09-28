<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repair `course_purchases.academy_id`, which drifted out of the dev/prod MySQL schema.
 *
 * Migration 2026_06_11_054831_add_academy_id_to_course_purchases_table adds this column
 * (nullable, FK to academies, nullOnDelete) and is marked "ran", but the column is absent
 * on databases whose `course_purchases` table was later rebuilt from a dump that predates
 * it. Because that original migration has no `hasColumn` guard, it never re-adds the column.
 * The marketplace purchase path queries `where('academy_id', ...)` and writes `academy_id`,
 * so the missing column makes every academy-scoped purchase die with
 * `1054 Unknown column 'academy_id'` (returned to the client as a 400).
 *
 * Idempotent: adds the column and FK only when absent, matching the original definition.
 * `down()` is a deliberate no-op — the column belongs to the add-academy_id migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('course_purchases', 'academy_id')) {
            Schema::table('course_purchases', function (Blueprint $table) {
                $table->unsignedBigInteger('academy_id')->nullable()->after('source_course_id');
            });
        }

        $hasFk = collect(DB::select(
            'SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME = ?',
            ['course_purchases', 'academy_id', 'academies']
        ))->isNotEmpty();

        if (! $hasFk) {
            Schema::table('course_purchases', function (Blueprint $table) {
                $table->foreign('academy_id')->references('id')->on('academies')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        // No-op: academy_id belongs to 2026_06_11_054831_add_academy_id_to_course_purchases_table.
    }
};
