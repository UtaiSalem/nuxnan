<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentAcademicInfo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentBackfillAcademicInfoCommandTest extends TestCase
{
    use RefreshDatabase;

    private Academy $academy;

    private AcademicYear $year2568;

    private AcademicYear $year2569;

    private Classroom $classroom2568;

    private Classroom $classroom2569;

    protected function setUp(): void
    {
        parent::setUp();

        $owner = User::factory()->create();

        $this->academy = Academy::create([
            'user_id' => $owner->id,
            'name' => 'Backfill Academy',
            'slug' => 'backfill-academy',
        ]);

        $this->year2568 = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2568',
            'start_date' => '2025-05-16',
            'end_date' => '2026-03-31',
            'is_current' => false,
        ]);

        $this->year2569 = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2569',
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
            'is_current' => true,
        ]);

        $this->classroom2568 = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year2568->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
            'academic_year' => '2568',
            'semester' => 1,
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->classroom2569 = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year2569->id,
            'grade_level' => 'ม.2',
            'section' => '1',
            'name' => 'ม.2/1',
            'academic_year' => '2569',
            'semester' => 1,
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
        ]);
    }

    public function test_command_creates_missing_academic_info_from_active_enrollment(): void
    {
        $student = $this->makeStudent('ST1001');

        ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'classroom_id' => $this->classroom2569->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->year2569->id,
            'student_number' => 1,
            'status' => ClassroomStudent::STATUS_ACTIVE,
            'enrolled_at' => '2026-05-20',
        ]);

        $this->artisan('enrollment:backfill-academic-info')
            ->assertExitCode(0);

        $this->assertDatabaseHas('student_academic_info', [
            'student_id' => $student->id,
            'classroom_id' => $this->classroom2569->id,
            'academic_year' => '2569',
            'current_grade' => 'ม.2',
            'current_class' => '1',
            'study_status' => StudentAcademicInfo::STATUS_STUDYING,
            'is_current' => true,
        ]);
    }

    public function test_command_patches_null_academic_year_row_instead_of_creating_duplicate(): void
    {
        $student = $this->makeStudent('ST1002');

        ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'classroom_id' => $this->classroom2568->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->year2568->id,
            'student_number' => 2,
            'status' => ClassroomStudent::STATUS_ACTIVE,
            'enrolled_at' => '2025-05-20',
        ]);

        $info = StudentAcademicInfo::create([
            'academy_id' => $this->academy->id,
            'student_id' => $student->id,
            'classroom_id' => $this->classroom2568->id,
            'current_grade' => 'ม.1',
            'current_class' => '1',
            'classroom_full' => 'ม.1/1',
            'academic_year' => null,
            'semester' => 1,
            'study_status' => StudentAcademicInfo::STATUS_STUDYING,
            'is_current' => false,
        ]);

        $this->artisan('enrollment:backfill-academic-info')
            ->assertExitCode(0);

        $this->assertEquals(1, StudentAcademicInfo::where('student_id', $student->id)->count());
        $this->assertDatabaseHas('student_academic_info', [
            'id' => $info->id,
            'student_id' => $student->id,
            'academic_year' => '2568',
        ]);
    }

    public function test_command_respects_year_filter(): void
    {
        $studentA = $this->makeStudent('ST1003');
        $studentB = $this->makeStudent('ST1004');

        ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'classroom_id' => $this->classroom2568->id,
            'student_id' => $studentA->id,
            'academic_year_id' => $this->year2568->id,
            'student_number' => 3,
            'status' => ClassroomStudent::STATUS_ACTIVE,
            'enrolled_at' => '2025-05-20',
        ]);

        ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'classroom_id' => $this->classroom2569->id,
            'student_id' => $studentB->id,
            'academic_year_id' => $this->year2569->id,
            'student_number' => 4,
            'status' => ClassroomStudent::STATUS_ACTIVE,
            'enrolled_at' => '2026-05-20',
        ]);

        $this->artisan('enrollment:backfill-academic-info', ['--year' => '2568'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('student_academic_info', [
            'student_id' => $studentA->id,
            'academic_year' => '2568',
        ]);

        $this->assertDatabaseMissing('student_academic_info', [
            'student_id' => $studentB->id,
            'academic_year' => '2569',
        ]);
    }

    public function test_command_does_not_write_in_dry_run_mode(): void
    {
        $student = $this->makeStudent('ST1005');

        ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'classroom_id' => $this->classroom2569->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->year2569->id,
            'student_number' => 5,
            'status' => ClassroomStudent::STATUS_ACTIVE,
            'enrolled_at' => '2026-05-20',
        ]);

        $this->artisan('enrollment:backfill-academic-info', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('student_academic_info', [
            'student_id' => $student->id,
            'academic_year' => '2569',
        ]);
    }

    public function test_command_promotes_existing_same_year_row_to_current_when_safe(): void
    {
        $student = $this->makeStudent('ST1006');

        ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'classroom_id' => $this->classroom2569->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->year2569->id,
            'student_number' => 6,
            'status' => ClassroomStudent::STATUS_ACTIVE,
            'enrolled_at' => '2026-05-20',
        ]);

        StudentAcademicInfo::create([
            'academy_id' => $this->academy->id,
            'student_id' => $student->id,
            'classroom_id' => $this->classroom2569->id,
            'current_grade' => 'ม.2',
            'current_class' => '1',
            'classroom_full' => 'ม.2/1',
            'academic_year' => '2569',
            'semester' => 1,
            'study_status' => StudentAcademicInfo::STATUS_STUDYING,
            'is_current' => false,
        ]);

        $this->artisan('enrollment:backfill-academic-info')
            ->assertExitCode(0);

        $this->assertDatabaseHas('student_academic_info', [
            'student_id' => $student->id,
            'academic_year' => '2569',
            'is_current' => true,
        ]);
    }

    private function makeStudent(string $studentId): Student
    {
        $user = User::factory()->create();

        return Student::create([
            'academy_id' => $this->academy->id,
            'user_id' => $user->id,
            'student_id' => $studentId,
            'citizen_id' => str_pad((string) random_int(1, 9999999999999), 13, '0', STR_PAD_LEFT),
            'first_name_th' => 'Test',
            'last_name_th' => 'Student',
            'status' => 'active',
        ]);
    }
}
