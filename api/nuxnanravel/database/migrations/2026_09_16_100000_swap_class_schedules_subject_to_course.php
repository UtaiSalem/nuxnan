<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // (a) Drop foreign key if exists (MySQL only logic since dev DB lacks FKs)
        if ($driver === 'mysql') {
            $fk = DB::selectOne(
                'select CONSTRAINT_NAME from information_schema.KEY_COLUMN_USAGE
                 where TABLE_SCHEMA = database() and TABLE_NAME = ? and COLUMN_NAME = ?
                   and REFERENCED_TABLE_NAME is not null',
                ['class_schedules', 'subject_id']
            );
            if ($fk) {
                Schema::table('class_schedules', function (Blueprint $table) use ($fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                });
            }

            // (b) Drop index if exists
            $index = DB::selectOne(
                'select INDEX_NAME from information_schema.STATISTICS
                 where TABLE_SCHEMA = database() and TABLE_NAME = ? and INDEX_NAME = ?',
                ['class_schedules', 'class_schedules_subject_id_semester_id_index']
            );
            if ($index) {
                Schema::table('class_schedules', function (Blueprint $table) {
                    $table->dropIndex('class_schedules_subject_id_semester_id_index');
                });
            }
        } elseif ($driver === 'sqlite') {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->dropForeign(['subject_id']);
                $table->dropIndex('class_schedules_subject_id_semester_id_index');
            });
        }

        // (c) Add new columns (NO foreign keys for these columns right now)
        // Reason: Dev DB does not have FKs. Using FKs here will lead to inconsistent schema state.
        // Also schedule_periods will be rebuilt in SC-S6.
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id')->nullable()->after('subject_id');
            $table->string('title', 255)->nullable()->after('course_id');
            $table->string('entry_type', 20)->default('course')->after('title');
            $table->unsignedBigInteger('period_id')->nullable()->after('entry_type');
        });

        // (d) Backfill data
        if ($driver === 'sqlite') {
            DB::statement('
                UPDATE class_schedules 
                SET title = COALESCE(
                    (SELECT name_th FROM subjects WHERE subjects.id = class_schedules.subject_id), 
                    (SELECT subject_code FROM subjects WHERE subjects.id = class_schedules.subject_id)
                )
                WHERE subject_id IS NOT NULL
            ');
        } else {
            DB::statement('
                UPDATE class_schedules cs
                LEFT JOIN subjects s ON cs.subject_id = s.id
                SET cs.title = COALESCE(s.name_th, s.subject_code)
                WHERE cs.subject_id IS NOT NULL
            ');
        }

        // (e) Add index
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->index(['course_id', 'semester_id']);
        });

        // (f) Drop subject_id column
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropColumn('subject_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore subject_id column and index. Note: the original values are unrecoverable
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('subject_id')->nullable()->after('classroom_id');
        });

        Schema::table('class_schedules', function (Blueprint $table) {
            $table->index(['subject_id', 'semester_id']);
            $table->dropIndex(['course_id', 'semester_id']);
            $table->dropColumn(['course_id', 'title', 'entry_type', 'period_id']);
        });

        // Data loss note: The original values of subject_id are unrecoverable.
        // Data was moved to the title column but cannot be reliably mapped back to a subject_id.
    }
};
