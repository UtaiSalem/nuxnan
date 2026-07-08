<?php

namespace Tests\Feature\Migrations;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BackfillAcademicInfoClassroomIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_backfills_classroom_id_correctly(): void
    {
        // 1. Create setup
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'username' => 'admin'.uniqid(),
            'reference_code' => 'R'.uniqid(),
            'personal_code' => 'P'.uniqid(),
        ]);
        $academy = Academy::create(['name' => 'Test Academy', 'user_id' => $admin->id]);
        $year = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2569',
            'start_date' => '2026-05-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
        ]);
        $classroom = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
        ]);
        $student = Student::create([
            'academy_id' => $academy->id,
            'student_id' => 'S'.uniqid(),
            'first_name_th' => 'Test',
            'last_name_th' => 'Student',
        ]);

        // Create enrollment
        $student->classroomEnrollments()->create([
            'academy_id' => $academy->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'student_number' => 1,
            'status' => 'active',
            'enrolled_at' => today(),
        ]);

        // Create academic info with classroom_id null
        $infoId = DB::table('student_academic_info')->insertGetId([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'classroom_id' => null,
            'current_grade' => 'ม.1',
            'current_class' => '1',
            'classroom_full' => null,
            'academic_year' => '2569',
            'is_current' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Run the migration up logic
        $migration = require database_path('migrations/2026_07_09_000001_backfill_academic_info_classroom_id_from_current_enrollment.php');
        $migration->up();

        // 3. Assert values are filled
        $info = DB::table('student_academic_info')->where('id', $infoId)->first();
        $this->assertEquals($classroom->id, $info->classroom_id);
        $this->assertEquals('ม.1/1', $info->classroom_full);
    }

    public function test_migration_is_idempotent(): void
    {
        [$academy, $classroom, $student] = $this->setupBaseData();

        $student->classroomEnrollments()->create([
            'academy_id' => $academy->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $classroom->academic_year_id,
            'student_number' => 1,
            'status' => 'active',
            'enrolled_at' => today(),
        ]);

        $infoId = DB::table('student_academic_info')->insertGetId([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'classroom_id' => null,
            'current_grade' => 'ม.1',
            'current_class' => '1',
            'classroom_full' => null,
            'academic_year' => '2569',
            'is_current' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_07_09_000001_backfill_academic_info_classroom_id_from_current_enrollment.php');

        $migration->up();
        $migration->up();

        $rows = DB::table('student_academic_info')->where('student_id', $student->id)->get();
        $this->assertCount(1, $rows);
        $this->assertEquals($classroom->id, $rows->first()->classroom_id);
    }

    public function test_migration_prefers_academic_year_matching_row(): void
    {
        [$academy, $classroom, $student] = $this->setupBaseData();

        $oldYear = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2568',
            'start_date' => '2025-05-01',
            'end_date' => '2026-03-31',
            'is_current' => false,
        ]);

        $oldClassroom = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $oldYear->id,
            'grade_level' => 'ป.6',
            'section' => '1',
            'name' => 'ป.6/1',
        ]);

        // Enrollment in current year first (should be preferred by "is_current DESC" fallback)
        $student->classroomEnrollments()->create([
            'academy_id' => $academy->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $classroom->academic_year_id,
            'student_number' => 1,
            'status' => 'active',
            'enrolled_at' => '2026-05-01',
        ]);

        // Also enrollment in old year
        $student->classroomEnrollments()->create([
            'academy_id' => $academy->id,
            'classroom_id' => $oldClassroom->id,
            'academic_year_id' => $oldYear->id,
            'student_number' => 1,
            'status' => 'graduated',
            'enrolled_at' => '2025-05-01',
        ]);

        // Academic info explicitly names OLD academic_year — migration should prefer old classroom by name match
        $infoId = DB::table('student_academic_info')->insertGetId([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'classroom_id' => null,
            'academic_year' => '2568',
            'is_current' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_07_09_000001_backfill_academic_info_classroom_id_from_current_enrollment.php');
        $migration->up();

        $info = DB::table('student_academic_info')->where('id', $infoId)->first();
        $this->assertEquals($oldClassroom->id, $info->classroom_id, 'Should match by academic_year name first');
    }

    public function test_migration_leaves_null_when_student_has_no_enrollment(): void
    {
        [$academy, , $student] = $this->setupBaseData();

        $infoId = DB::table('student_academic_info')->insertGetId([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'classroom_id' => null,
            'academic_year' => '2569',
            'is_current' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_07_09_000001_backfill_academic_info_classroom_id_from_current_enrollment.php');
        $migration->up();

        $info = DB::table('student_academic_info')->where('id', $infoId)->first();
        $this->assertNull($info->classroom_id);
    }

    private function setupBaseData(): array
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'username' => 'admin'.uniqid(),
            'reference_code' => 'R'.uniqid(),
            'personal_code' => 'P'.uniqid(),
        ]);
        $academy = Academy::create(['name' => 'Base Academy', 'user_id' => $admin->id]);
        $year = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2569',
            'start_date' => '2026-05-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
        ]);
        $classroom = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
        ]);
        $student = Student::create([
            'academy_id' => $academy->id,
            'student_id' => 'S'.uniqid(),
            'first_name_th' => 'Base',
            'last_name_th' => 'Student',
        ]);

        return [$academy, $classroom, $student];
    }
}
