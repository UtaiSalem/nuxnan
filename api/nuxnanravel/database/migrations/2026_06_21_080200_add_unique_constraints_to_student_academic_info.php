<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — student_academic_info integrity
 *
 * 1. Unique (student_id, academic_year) — กัน duplicate ประวัติปีเดียวกัน
 * 2. Unique (student_id) WHERE is_current=1 — กัน 2 current rows
 *    ใช้ generated column (CASE WHEN is_current=1 THEN student_id END) + unique index ธรรมดา
 *    เพราะ MariaDB ไม่รองรับ inline expression index syntax ของ MySQL 8
 *    (CREATE UNIQUE INDEX ... ((expr))) — generated column ใช้ได้ทั้ง MySQL 5.7.6+/8 และ MariaDB 10.2+
 *
 * Pre-condition (จาก preflight §9):
 * - 0 duplicate (student_id, academic_year)
 * - 0 student with >1 is_current=true rows
 * → constraints apply ได้โดยไม่ต้อง repair
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! $this->indexExists('student_academic_info', 'uq_sai_student_year')) {
            Schema::table('student_academic_info', function (Blueprint $table) {
                $table->unique(['student_id', 'academic_year'], 'uq_sai_student_year');
            });
        }

        if (DB::getDriverName() === 'mysql') {
            if (! Schema::hasColumn('student_academic_info', 'current_student_uid')) {
                Schema::table('student_academic_info', function (Blueprint $table) {
                    $table->bigInteger('current_student_uid')
                        ->unsigned()
                        ->nullable()
                        ->virtualAs('CASE WHEN is_current = 1 THEN student_id END')
                        ->after('is_current');
                });
            }

            if (! $this->indexExists('student_academic_info', 'uq_sai_current_student')) {
                DB::statement(
                    'CREATE UNIQUE INDEX uq_sai_current_student '
                    .'ON student_academic_info (current_student_uid)'
                );
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            if ($this->indexExists('student_academic_info', 'uq_sai_current_student')) {
                DB::statement('DROP INDEX uq_sai_current_student ON student_academic_info');
            }

            if (Schema::hasColumn('student_academic_info', 'current_student_uid')) {
                Schema::table('student_academic_info', function (Blueprint $table) {
                    $table->dropColumn('current_student_uid');
                });
            }
        }

        if ($this->indexExists('student_academic_info', 'uq_sai_student_year')) {
            Schema::table('student_academic_info', function (Blueprint $table) {
                $table->dropUnique('uq_sai_student_year');
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
};
