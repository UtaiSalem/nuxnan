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

class EnrollmentRepairDirtyDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        $_ENV['DB_FOREIGN_KEYS'] = 'false';
        parent::setUp();
    }

    private function createSetup()
    {
        $user = User::create([
            'name' => 'Test User '.uniqid(),
            'email' => 'test'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'username' => 'testuser'.uniqid(),
            'reference_code' => 'REF'.uniqid(),
            'personal_code' => 'PER'.uniqid(),
        ]);

        $academy = Academy::create([
            'name' => 'Test Academy '.uniqid(),
            'user_id' => $user->id,
        ]);

        $academicYear = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2567',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_current' => true,
        ]);

        $classroom = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $academicYear->id,
            'grade_level' => 'Grade 10',
            'section' => 'A',
            'name' => 'Classroom 10-A',
            'academic_year' => '2567',
            'semester' => 1,
            'is_active' => true,
            'status' => 'active',
        ]);

        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'student_id' => 'STU'.rand(1000, 9999),
            'citizen_id' => '1234567890'.rand(100, 999),
            'first_name_th' => 'Test',
            'last_name_th' => 'Student',
            'class_level' => 'Grade 10',
            'class_section' => 'A',
        ]);

        return [$user, $academy, $academicYear, $classroom, $student];
    }

    public function test_duplicate_classroom_students_repaired()
    {
        [$user, $academy, $academicYear, $classroom, $student] = $this->createSetup();

        // Create a second classroom to bypass classroom_id + student_id unique constraint
        $classroom2 = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $academicYear->id,
            'grade_level' => 'Grade 10',
            'section' => 'B',
            'name' => 'Classroom 10-B',
            'academic_year' => '2567',
            'semester' => 1,
            'is_active' => true,
            'status' => 'active',
        ]);

        // Create duplicate active classroom student records
        $oldRecord = ClassroomStudent::create([
            'academy_id' => $academy->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'student_number' => 10,
            'status' => 'active',
            'enrolled_at' => now()->subDays(5),
        ]);

        $newRecord = ClassroomStudent::create([
            'academy_id' => $academy->id,
            'classroom_id' => $classroom2->id,
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'student_number' => 10,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        // Run dry run first
        $this->artisan('enrollment:repair-dirty-data', ['--dry-run' => true])
            ->expectsOutputToContain('DRY RUN completed')
            ->assertExitCode(0);

        // Verify no change in dry-run
        $this->assertEquals('active', $oldRecord->refresh()->status);
        $this->assertEquals('active', $newRecord->refresh()->status);

        // Run in commit mode (default, no --dry-run)
        $this->artisan('enrollment:repair-dirty-data')
            ->expectsOutputToContain('COMMIT completed')
            ->assertExitCode(0);

        // Verify older row is superseded and newer row remains active
        $this->assertEquals('superseded', $oldRecord->refresh()->status);
        $this->assertEquals('active', $newRecord->refresh()->status);
    }

    public function test_student_class_drift_repaired()
    {
        [$user, $academy, $academicYear, $classroom, $student] = $this->createSetup();

        // Enroll student actively in classroom (Grade 10, A)
        ClassroomStudent::create([
            'academy_id' => $academy->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'student_number' => 10,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        // Introduce drift in student table
        $student->update([
            'class_level' => 'Grade 11',
            'class_section' => 'B',
        ]);

        // Run dry run
        $this->artisan('enrollment:repair-dirty-data', ['--dry-run' => true])
            ->assertExitCode(0);

        $student->refresh();
        $this->assertEquals('Grade 11', $student->class_level);
        $this->assertEquals('B', $student->class_section);

        // Run default (commit)
        $this->artisan('enrollment:repair-dirty-data')
            ->assertExitCode(0);

        // Re-sync correct details from active classroom
        $student->refresh();
        $this->assertEquals('10', $student->class_level);
        $this->assertEquals('A', $student->class_section);
    }

    public function test_student_no_active_enrollment_does_not_clear_snapshot()
    {
        [$user, $academy, $academicYear, $classroom, $student] = $this->createSetup();

        // Student has no active enrollment, but has class details
        $student->update([
            'class_level' => 'Grade 10',
            'class_section' => 'A',
        ]);

        // Run repair in commit mode
        $this->artisan('enrollment:repair-dirty-data')
            ->assertExitCode(0);

        // Class details MUST NOT be cleared in Phase 9 per user request
        $student->refresh();
        $this->assertEquals('Grade 10', $student->class_level);
        $this->assertEquals('A', $student->class_section);
    }

    public function test_duplicate_academic_info_repaired()
    {
        [$user, $academy, $academicYear, $classroom, $student] = $this->createSetup();

        // Create duplicate student_academic_info rows with is_current = true
        $oldInfo = StudentAcademicInfo::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'current_grade' => 'Grade 10',
            'current_class' => 'A',
            'academic_year' => '2566',
            'semester' => 2,
            'is_current' => true,
        ]);

        $newInfo = StudentAcademicInfo::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'current_grade' => 'Grade 10',
            'current_class' => 'A',
            'academic_year' => '2567',
            'semester' => 1,
            'is_current' => true,
        ]);

        // Run dry run
        $this->artisan('enrollment:repair-dirty-data', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertTrue($oldInfo->refresh()->is_current);
        $this->assertTrue($newInfo->refresh()->is_current);

        // Run commit
        $this->artisan('enrollment:repair-dirty-data')
            ->assertExitCode(0);

        // Verify old is unset, new remains current
        $this->assertFalse($oldInfo->refresh()->is_current);
        $this->assertTrue($newInfo->refresh()->is_current);
    }

    public function test_manual_review_items_detected()
    {
        [$user, $academy, $academicYear, $classroom, $student] = $this->createSetup();

        // Case 1: active enrollment with null academic_year_id
        ClassroomStudent::create([
            'academy_id' => $academy->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'academic_year_id' => null,
            'student_number' => 15,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        // Case 2: active enrollment with deleted classroom
        ClassroomStudent::create([
            'academy_id' => $academy->id,
            'classroom_id' => 99999, // Non-existent
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'student_number' => 16,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        // Run the command and assert counts
        $this->artisan('enrollment:repair-dirty-data')
            ->expectsOutputToContain('Total issues: 2')
            ->assertExitCode(0);
    }

    public function test_academy_filtering()
    {
        // Setup 1 (Academy 1)
        [$user1, $academy1, $ay1, $class1, $student1] = $this->createSetup();

        $class1_B = Classroom::create([
            'academy_id' => $academy1->id,
            'academic_year_id' => $ay1->id,
            'grade_level' => 'Grade 10',
            'section' => 'B',
            'name' => 'Classroom 10-B',
            'academic_year' => '2567',
            'semester' => 1,
            'is_active' => true,
            'status' => 'active',
        ]);

        $record1_old = ClassroomStudent::create([
            'academy_id' => $academy1->id,
            'classroom_id' => $class1->id,
            'student_id' => $student1->id,
            'academic_year_id' => $ay1->id,
            'student_number' => 1,
            'status' => 'active',
            'enrolled_at' => now()->subDays(2),
        ]);
        $record1_new = ClassroomStudent::create([
            'academy_id' => $academy1->id,
            'classroom_id' => $class1_B->id,
            'student_id' => $student1->id,
            'academic_year_id' => $ay1->id,
            'student_number' => 1,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        // Setup 2 (Academy 2)
        [$user2, $academy2, $ay2, $class2, $student2] = $this->createSetup();

        $class2_B = Classroom::create([
            'academy_id' => $academy2->id,
            'academic_year_id' => $ay2->id,
            'grade_level' => 'Grade 10',
            'section' => 'B',
            'name' => 'Classroom 10-B',
            'academic_year' => '2567',
            'semester' => 1,
            'is_active' => true,
            'status' => 'active',
        ]);

        $record2_old = ClassroomStudent::create([
            'academy_id' => $academy2->id,
            'classroom_id' => $class2->id,
            'student_id' => $student2->id,
            'academic_year_id' => $ay2->id,
            'student_number' => 2,
            'status' => 'active',
            'enrolled_at' => now()->subDays(2),
        ]);
        $record2_new = ClassroomStudent::create([
            'academy_id' => $academy2->id,
            'classroom_id' => $class2_B->id,
            'student_id' => $student2->id,
            'academic_year_id' => $ay2->id,
            'student_number' => 2,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        // Run filtered for Academy 1
        $this->artisan('enrollment:repair-dirty-data', [
            '--academy' => $academy1->id,
        ])->assertExitCode(0);

        // Academy 1 duplicates should be resolved
        $this->assertEquals('superseded', $record1_old->refresh()->status);
        $this->assertEquals('active', $record1_new->refresh()->status);

        // Academy 2 duplicates should NOT be resolved
        $this->assertEquals('active', $record2_old->refresh()->status);
        $this->assertEquals('active', $record2_new->refresh()->status);
    }

    public function test_student_class_drift_uses_normalized_grade_snapshot()
    {
        $user = User::create([
            'name' => 'Thai Grade User '.uniqid(),
            'email' => 'thai-grade-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'username' => 'thaigrade'.uniqid(),
            'reference_code' => 'REF'.uniqid(),
            'personal_code' => 'PER'.uniqid(),
        ]);

        $academy = Academy::create([
            'name' => 'Thai Grade Academy '.uniqid(),
            'user_id' => $user->id,
        ]);

        $academicYear = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2568',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_current' => true,
        ]);

        $classroom = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $academicYear->id,
            'grade_level' => 'ม.6',
            'section' => '5',
            'name' => 'ม.6/5',
            'academic_year' => '2568',
            'semester' => 1,
            'is_active' => true,
            'status' => 'active',
        ]);

        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'student_id' => 'TH'.rand(1000, 9999),
            'citizen_id' => '9999999999'.rand(100, 999),
            'first_name_th' => 'Test',
            'last_name_th' => 'ThaiGrade',
            'class_level' => '6',
            'class_section' => '5',
        ]);

        ClassroomStudent::create([
            'academy_id' => $academy->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'student_number' => 1,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        $this->artisan('enrollment:repair-dirty-data')
            ->assertExitCode(0);

        $student->refresh();
        $this->assertEquals('6', $student->class_level);
        $this->assertEquals('5', $student->class_section);
    }
}
