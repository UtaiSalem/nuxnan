<?php

namespace Tests\Feature\Api\Academy;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentIntakeControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Academy $academy;

    private AcademicYear $academicYear;

    private Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->academy = Academy::create([
            'user_id' => $this->owner->id,
            'name' => 'Intake Academy',
            'slug' => 'intake-academy',
        ]);
        $this->academicYear = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2569',
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ]);
        $this->classroom = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
        ]);
    }

    public function test_owner_can_complete_atomic_student_intake(): void
    {
        $response = $this->actingAs($this->owner, 'api')
            ->postJson($this->url(), $this->payload());

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.student.student_id', 'JSM260001')
            ->assertJsonPath('data.student.account_status', 'pending_activation')
            ->assertJsonPath('data.membership.role', 'student')
            ->assertJsonPath('data.enrollment.classroom_id', $this->classroom->id)
            ->assertJsonPath('data.academic_info.academic_year', '2569')
            ->assertJsonCount(2, 'data.guardians');

        $student = Student::where('academy_id', $this->academy->id)
            ->where('student_id', 'JSM260001')
            ->firstOrFail();

        $this->assertNull($student->user_id);
        $this->assertDatabaseHas('academy_members', [
            'academy_id' => $this->academy->id,
            'student_id' => $student->id,
            'user_id' => null,
            'status' => 2,
            'role' => 'student',
        ]);
        $this->assertDatabaseHas('classroom_students', [
            'academy_id' => $this->academy->id,
            'student_id' => $student->id,
            'classroom_id' => $this->classroom->id,
            'student_number' => 7,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('student_academic_info', [
            'academy_id' => $this->academy->id,
            'student_id' => $student->id,
            'classroom_id' => $this->classroom->id,
            'academic_year' => '2569',
            'previous_school_name' => 'Old School',
            'is_current' => true,
        ]);
        $this->assertDatabaseHas('student_guardians', [
            'academy_id' => $this->academy->id,
            'student_id' => $student->id,
            'guardian_type' => 'father',
            'is_primary_contact' => true,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => Student::class,
            'entity_id' => $student->id,
            'action' => 'student_intake',
            'user_id' => $this->owner->id,
        ]);
    }

    public function test_registrar_with_students_create_permission_can_intake(): void
    {
        $registrar = User::factory()->create();
        $role = AcademyRole::create([
            'academy_id' => $this->academy->id,
            'name' => 'registrar-local',
            'display_name_th' => 'นายทะเบียน',
            'permissions' => ['students.create'],
            'is_active' => true,
        ]);
        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $registrar->id,
            'status' => 2,
            'role' => 'registrar',
            'academy_role_id' => $role->id,
            'enrollment_date' => today(),
        ]);

        $this->actingAs($registrar, 'api')
            ->postJson($this->url(), $this->payload())
            ->assertCreated();
    }

    public function test_academy_role_with_students_manage_permission_can_intake(): void
    {
        $admin = User::factory()->create();
        $role = AcademyRole::create([
            'academy_id' => $this->academy->id,
            'name' => 'local-admin',
            'display_name_th' => 'ผู้ดูแล',
            'permissions' => ['students.manage'],
            'is_active' => true,
        ]);
        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $admin->id,
            'status' => 2,
            'academy_role_id' => $role->id,
            'enrollment_date' => today(),
        ]);

        $this->actingAs($admin, 'api')
            ->postJson($this->url(), $this->payload())
            ->assertCreated();
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create(), 'api')
            ->postJson($this->url(), $this->payload())
            ->assertForbidden();
    }

    public function test_duplicate_identity_is_blocked_without_partial_records(): void
    {
        Student::create([
            'academy_id' => $this->academy->id,
            'student_id' => 'JSM260001',
            'citizen_id' => '1234567890123',
            'first_name_th' => 'Existing',
            'last_name_th' => 'Student',
        ]);

        $this->actingAs($this->owner, 'api')
            ->postJson($this->url(), $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['identity.student_id', 'identity.citizen_id']);

        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('classroom_students', 0);
    }

    public function test_classroom_from_another_academy_is_rejected(): void
    {
        $otherOwner = User::factory()->create();
        $otherAcademy = Academy::create(['user_id' => $otherOwner->id, 'name' => 'Other', 'slug' => 'other-intake']);
        $otherYear = AcademicYear::create([
            'academy_id' => $otherAcademy->id,
            'name' => '2569',
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ]);
        $otherClassroom = Classroom::create([
            'academy_id' => $otherAcademy->id,
            'academic_year_id' => $otherYear->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'Other 1',
        ]);
        $payload = $this->payload();
        $payload['admission']['academic_year_id'] = $otherYear->id;
        $payload['admission']['classroom_id'] = $otherClassroom->id;

        $this->actingAs($this->owner, 'api')
            ->postJson($this->url(), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['admission.academic_year_id', 'admission.classroom_id']);

        $this->assertDatabaseCount('students', 0);
    }

    public function test_full_classroom_rolls_back_intake(): void
    {
        $this->classroom->update(['capacity' => 0]);

        $this->actingAs($this->owner, 'api')
            ->postJson($this->url(), $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['admission.classroom_id']);

        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('academy_members', 0);
    }

    public function test_duplicate_check_is_scoped_to_academy(): void
    {
        Student::create([
            'academy_id' => $this->academy->id,
            'student_id' => 'JSM260001',
            'citizen_id' => '1234567890123',
            'first_name_th' => 'Existing',
            'last_name_th' => 'Student',
        ]);

        $this->actingAs($this->owner, 'api')
            ->getJson($this->url().'/duplicate-check?citizen_id=1234567890123')
            ->assertOk()
            ->assertJsonPath('has_duplicates', true)
            ->assertJsonPath('data.0.matched_fields.0', 'citizen_id');
    }

    private function url(): string
    {
        return "/api/academies/{$this->academy->id}/student-intakes";
    }

    private function payload(): array
    {
        return [
            'identity' => [
                'student_id' => 'JSM260001',
                'citizen_id' => '1234567890123',
            ],
            'personal' => [
                'title_prefix_th' => 'เด็กชาย',
                'first_name_th' => 'ทดสอบ',
                'last_name_th' => 'ระบบรับเข้า',
                'date_of_birth' => '2013-01-15',
                'gender' => 1,
                'nationality' => 'ไทย',
            ],
            'admission' => [
                'academic_year_id' => $this->academicYear->id,
                'classroom_id' => $this->classroom->id,
                'student_number' => 7,
                'enrollment_date' => '2026-05-16',
            ],
            'previous_school' => [
                'name' => 'Old School',
                'province' => 'Bangkok',
                'grade_level' => 'ป.6',
            ],
            'guardians' => [
                [
                    'guardian_type' => 'father',
                    'first_name' => 'Father',
                    'last_name' => 'Test',
                    'relationship' => 'father',
                    'is_primary_contact' => true,
                    'is_emergency_contact' => true,
                ],
                [
                    'guardian_type' => 'mother',
                    'first_name' => 'Mother',
                    'last_name' => 'Test',
                    'relationship' => 'mother',
                ],
            ],
            'account' => [],
        ];
    }
}
