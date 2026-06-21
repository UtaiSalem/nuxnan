<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\RolloverBatch;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class EnrollmentPolicyTest extends TestCase
{
    use RefreshDatabase;

    private Academy $academy;

    private Student $student;

    private Classroom $classroom;

    private AcademicYear $academicYear2568;

    private AcademicYear $academicYear2569;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create owner and academy
        $owner = User::factory()->create();
        $this->academy = Academy::create([
            'user_id' => $owner->id,
            'name' => 'Policy Test Academy',
            'slug' => 'policy-test-academy',
        ]);

        // 2. Create academic years and classroom
        $this->academicYear2568 = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2568',
            'start_date' => '2025-05-16',
            'end_date' => '2026-03-31',
        ]);

        $this->academicYear2569 = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2569',
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ]);

        $this->classroom = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->academicYear2568->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
            'capacity' => 50,
            'is_active' => true,
            'status' => 'active',
        ]);

        // 3. Create student
        $studentUser = User::factory()->create();
        $this->student = Student::create([
            'academy_id' => $this->academy->id,
            'user_id' => $studentUser->id,
            'first_name_th' => 'สิทธิ์',
            'last_name_th' => 'ทดสอบ',
            'student_id' => 'ST_POLICY',
            'citizen_id' => '1111111111111',
        ]);
    }

    public function test_owner_can_manage_enrollment_lifecycle(): void
    {
        $owner = $this->academy->user;
        $this->assertTrue(Gate::forUser($owner)->allows('enrollment.lifecycle', [$this->academy, $this->student]));
    }

    public function test_academy_admin_can_manage_enrollment_lifecycle(): void
    {
        $adminUser = User::factory()->create();
        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $adminUser->id,
            'role' => 'admin',
        ]);

        $this->assertTrue(Gate::forUser($adminUser)->allows('enrollment.lifecycle', [$this->academy, $this->student]));
    }

    public function test_academy_director_can_manage_enrollment_lifecycle(): void
    {
        $directorUser = User::factory()->create();
        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $directorUser->id,
            'role' => 'director',
        ]);

        $this->assertTrue(Gate::forUser($directorUser)->allows('enrollment.lifecycle', [$this->academy, $this->student]));
    }

    public function test_homeroom_teacher_can_manage_lifecycle_of_their_active_student(): void
    {
        $teacher = User::factory()->create();
        $this->classroom->update([
            'homeroom_teacher_id' => $teacher->id,
        ]);

        // Put student in teacher's classroom actively
        ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->classroom->academic_year_id,
            'classroom_id' => $this->classroom->id,
            'student_id' => $this->student->id,
            'status' => ClassroomStudent::STATUS_ACTIVE,
        ]);

        $this->assertTrue(Gate::forUser($teacher)->allows('enrollment.lifecycle', [$this->academy, $this->student]));
    }

    public function test_homeroom_teacher_cannot_manage_lifecycle_if_student_is_inactive_in_their_classroom(): void
    {
        $teacher = User::factory()->create();
        $this->classroom->update([
            'homeroom_teacher_id' => $teacher->id,
        ]);

        // Put student in teacher's classroom as inactive/graduated
        ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->classroom->academic_year_id,
            'classroom_id' => $this->classroom->id,
            'student_id' => $this->student->id,
            'status' => ClassroomStudent::STATUS_GRADUATED,
        ]);

        $this->assertFalse(Gate::forUser($teacher)->allows('enrollment.lifecycle', [$this->academy, $this->student]));
    }

    public function test_unrelated_teacher_cannot_manage_enrollment_lifecycle(): void
    {
        $teacher = User::factory()->create();
        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $teacher->id,
            'role' => 'teacher',
        ]);

        $this->assertFalse(Gate::forUser($teacher)->allows('enrollment.lifecycle', [$this->academy, $this->student]));
    }

    public function test_student_cannot_manage_enrollment_lifecycle(): void
    {
        $studentUser = $this->student->user;
        $this->assertFalse(Gate::forUser($studentUser)->allows('enrollment.lifecycle', [$this->academy, $this->student]));
    }

    public function test_cross_academy_student_lifecycle_denied(): void
    {
        $owner = $this->academy->user;

        $otherAcademy = Academy::create([
            'user_id' => $owner->id,
            'name' => 'Other Academy',
            'slug' => 'other-academy',
        ]);

        $otherStudent = Student::create([
            'academy_id' => $otherAcademy->id,
            'user_id' => User::factory()->create()->id,
            'first_name_th' => 'อื่น',
            'last_name_th' => 'ๆ',
            'student_id' => 'ST_OTHER',
            'citizen_id' => '2222222222222',
        ]);

        // Owner of academy A tries to manage student in academy B
        $this->assertFalse(Gate::forUser($owner)->allows('enrollment.lifecycle', [$this->academy, $otherStudent]));
    }

    public function test_preview_rollover_authorization(): void
    {
        $owner = $this->academy->user;
        $admin = User::factory()->create();
        AcademyMember::create(['academy_id' => $this->academy->id, 'user_id' => $admin->id, 'role' => 'admin']);

        $teacher = User::factory()->create();
        AcademyMember::create(['academy_id' => $this->academy->id, 'user_id' => $teacher->id, 'role' => 'teacher']);

        $studentUser = $this->student->user;

        $this->assertTrue(Gate::forUser($owner)->allows('enrollment.preview', $this->academy));
        $this->assertTrue(Gate::forUser($admin)->allows('enrollment.preview', $this->academy));
        $this->assertTrue(Gate::forUser($teacher)->allows('enrollment.preview', $this->academy));
        $this->assertFalse(Gate::forUser($studentUser)->allows('enrollment.preview', $this->academy));
    }

    public function test_plan_rollover_authorization(): void
    {
        $owner = $this->academy->user;
        $admin = User::factory()->create();
        AcademyMember::create(['academy_id' => $this->academy->id, 'user_id' => $admin->id, 'role' => 'admin']);

        $teacher = User::factory()->create();
        AcademyMember::create(['academy_id' => $this->academy->id, 'user_id' => $teacher->id, 'role' => 'teacher']);

        $studentUser = $this->student->user;

        $this->assertTrue(Gate::forUser($owner)->allows('enrollment.plan', $this->academy));
        $this->assertTrue(Gate::forUser($admin)->allows('enrollment.plan', $this->academy));
        $this->assertTrue(Gate::forUser($teacher)->allows('enrollment.plan', $this->academy));
        $this->assertFalse(Gate::forUser($studentUser)->allows('enrollment.plan', $this->academy));
    }

    public function test_commit_rollover_authorization(): void
    {
        $owner = $this->academy->user;
        $admin = User::factory()->create();
        AcademyMember::create(['academy_id' => $this->academy->id, 'user_id' => $admin->id, 'role' => 'admin']);

        $teacher = User::factory()->create();
        AcademyMember::create(['academy_id' => $this->academy->id, 'user_id' => $teacher->id, 'role' => 'teacher']);

        $studentUser = $this->student->user;

        $this->assertTrue(Gate::forUser($owner)->allows('enrollment.commit', $this->academy));
        $this->assertTrue(Gate::forUser($admin)->allows('enrollment.commit', $this->academy));
        $this->assertFalse(Gate::forUser($teacher)->allows('enrollment.commit', $this->academy));
        $this->assertFalse(Gate::forUser($studentUser)->allows('enrollment.commit', $this->academy));
    }

    public function test_undo_rollover_authorization(): void
    {
        $owner = $this->academy->user;
        $admin = User::factory()->create();
        AcademyMember::create(['academy_id' => $this->academy->id, 'user_id' => $admin->id, 'role' => 'admin']);

        $teacher = User::factory()->create();
        AcademyMember::create(['academy_id' => $this->academy->id, 'user_id' => $teacher->id, 'role' => 'teacher']);

        $batch = RolloverBatch::create([
            'id' => '11111111-2222-3333-4444-555555555555',
            'academy_id' => $this->academy->id,
            'from_academic_year_id' => $this->academicYear2568->id,
            'to_academic_year_id' => $this->academicYear2569->id,
            'status' => 'committed',
            'plan_summary' => [],
            'totals' => [],
        ]);

        $this->assertTrue(Gate::forUser($owner)->allows('enrollment.undo', [$this->academy, $batch]));
        $this->assertTrue(Gate::forUser($admin)->allows('enrollment.undo', [$this->academy, $batch]));
        $this->assertFalse(Gate::forUser($teacher)->allows('enrollment.undo', [$this->academy, $batch]));
    }

    public function test_cross_academy_undo_rollover_denied(): void
    {
        $owner = $this->academy->user;

        $otherAcademy = Academy::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Other Academy',
            'slug' => 'other-academy',
        ]);

        $batch = RolloverBatch::create([
            'id' => '11111111-2222-3333-4444-555555555555',
            'academy_id' => $otherAcademy->id,
            'from_academic_year_id' => $this->academicYear2568->id,
            'to_academic_year_id' => $this->academicYear2569->id,
            'status' => 'committed',
            'plan_summary' => [],
            'totals' => [],
        ]);

        // Owner of academy A tries to undo batch of academy B
        $this->assertFalse(Gate::forUser($owner)->allows('enrollment.undo', [$this->academy, $batch]));
    }
}
