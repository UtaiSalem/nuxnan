<?php

namespace Tests\Feature;

use App\Models\ClassroomStudent;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 1 schema-hardening guards.
 *
 * Functional-index assertions are MySQL-specific and skipped under sqlite;
 * the portable parts (status varchar, status constants, columns, unique on
 * student_id+academic_year) run on both drivers.
 */
class ClassroomEnrollmentSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_classroom_students_has_rollover_tracking_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('classroom_students', 'rollover_batch_id'));
        $this->assertTrue(Schema::hasColumn('classroom_students', 'created_by_user_id'));
    }

    public function test_rollover_batches_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('rollover_batches'));
        foreach (['id', 'academy_id', 'from_academic_year_id', 'to_academic_year_id',
            'committed_at', 'undo_closed_at', 'plan_summary', 'totals'] as $col) {
            $this->assertTrue(
                Schema::hasColumn('rollover_batches', $col),
                "rollover_batches.{$col} missing"
            );
        }
    }

    public function test_status_constants_cover_all_lifecycle_states(): void
    {
        $expected = ['active', 'transferred', 'promoted', 'graduated', 'dropped', 'repeating', 'superseded'];
        $this->assertEqualsCanonicalizing($expected, ClassroomStudent::$statuses);
    }

    public function test_status_text_accessor_renders_each_state(): void
    {
        foreach (ClassroomStudent::$statuses as $status) {
            $row = (new ClassroomStudent)->forceFill(['status' => $status]);
            $this->assertNotSame('ไม่ระบุ', $row->status_text, "missing label for {$status}");
        }
    }

    public function test_student_academic_info_rejects_duplicate_year(): void
    {
        if (DB::getDriverName() !== 'mysql' && DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped('unique behavior verified on mysql/sqlite only');
        }

        DB::table('student_academic_info')->insert([
            'student_id' => 999001,
            'academic_year' => '2568',
            'is_current' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);
        DB::table('student_academic_info')->insert([
            'student_id' => 999001,
            'academic_year' => '2568',
            'is_current' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
