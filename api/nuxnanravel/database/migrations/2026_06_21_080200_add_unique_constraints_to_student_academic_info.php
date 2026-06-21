<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — student_academic_info integrity
 *
 * 1. Unique (student_id, academic_year) — กัน duplicate ประวัติปีเดียวกัน
 * 2. Functional unique on (student_id) WHERE is_current=1 — กัน 2 current rows
 *    ใช้ expression index ของ MySQL 8 (preflight ยืนยัน 8.4.7 → supported)
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

        if (DB::getDriverName() === 'mysql' && ! $this->indexExists('student_academic_info', 'uq_sai_current_student')) {
            // Functional unique: only counts rows where is_current=1.
            // For is_current=0, expression returns NULL, and NULL is not unique-checked.
            DB::statement(
                'CREATE UNIQUE INDEX uq_sai_current_student '
                .'ON student_academic_info ((CASE WHEN is_current = 1 THEN student_id END))'
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql' && $this->indexExists('student_academic_info', 'uq_sai_current_student')) {
            DB::statement('DROP INDEX uq_sai_current_student ON student_academic_info');
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
