<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — Add rollover tracking to classroom_students
 *
 * 1. Convert status from ENUM to varchar(32) so new statuses
 *    (promoted, repeating, superseded) can be added without ALTER TABLE.
 * 2. Add rollover_batch_id (uuid) to group rows produced by the same batch.
 * 3. Add created_by_user_id for audit trail.
 * 4. Add composite index (academy_id, academic_year_id, status) for
 *    rollover preview/commit queries.
 *
 * Note: a full UNIQUE (classroom_id, student_id) already exists. Phase 1
 * keeps that constraint — re-enrolling into the same classroom overwrites
 * the existing row. True history preservation across same-classroom
 * re-enrollment is a Phase 2+ change.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // ENUM -> varchar(32). Existing values preserved.
            DB::statement("ALTER TABLE classroom_students MODIFY status VARCHAR(32) NOT NULL DEFAULT 'active'");
        }

        Schema::table('classroom_students', function (Blueprint $table) {
            if (! Schema::hasColumn('classroom_students', 'rollover_batch_id')) {
                $table->char('rollover_batch_id', 36)->nullable()->after('leave_reason');
                $table->index('rollover_batch_id', 'cs_rollover_batch_idx');
            }
            if (! Schema::hasColumn('classroom_students', 'created_by_user_id')) {
                $table->unsignedBigInteger('created_by_user_id')->nullable()->after('rollover_batch_id');
                $table->foreign('created_by_user_id', 'fk_cs_created_by')
                    ->references('id')->on('users')
                    ->onDelete('set null');
            }
        });

        // Composite index for rollover queries scoped by academy+year+status
        if (! $this->indexExists('classroom_students', 'cs_acad_year_status_idx')) {
            Schema::table('classroom_students', function (Blueprint $table) {
                $table->index(['academy_id', 'academic_year_id', 'status'], 'cs_acad_year_status_idx');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            return collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]))->isNotEmpty();
        }
        if ($driver === 'sqlite') {
            return collect(DB::select("SELECT name FROM sqlite_master WHERE type='index' AND name = ?", [$index]))->isNotEmpty();
        }

        return false;
    }

    public function down(): void
    {
        Schema::table('classroom_students', function (Blueprint $table) {
            if (Schema::hasColumn('classroom_students', 'created_by_user_id')) {
                $table->dropForeign('fk_cs_created_by');
                $table->dropColumn('created_by_user_id');
            }
            if (Schema::hasColumn('classroom_students', 'rollover_batch_id')) {
                $table->dropIndex('cs_rollover_batch_idx');
                $table->dropColumn('rollover_batch_id');
            }
        });

        if ($this->indexExists('classroom_students', 'cs_acad_year_status_idx')) {
            Schema::table('classroom_students', function (Blueprint $table) {
                $table->dropIndex('cs_acad_year_status_idx');
            });
        }

        // Revert status back to ENUM (only the original 4 values are guaranteed safe)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE classroom_students MODIFY status ENUM('active','transferred','graduated','dropped') NOT NULL DEFAULT 'active'");
        }
    }
};
