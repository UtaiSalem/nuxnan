<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\ClassroomStudent;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentGuardianLink;
use App\Models\User;
use App\Services\GuardianWriteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardianAppointmentStatusPayloadTest extends TestCase
{
    use RefreshDatabase;

    private function academyWithMember(array $permissions = []): array
    {
        $user = User::factory()->create();
        $academy = Academy::factory()->create();
        $role = AcademyRole::create([
            'academy_id' => $academy->id,
            'name' => 'test-role-'.uniqid(),
            'display_name_th' => 'Test role',
            'permissions' => $permissions,
        ]);
        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $role->id,
            'status' => 2, // approved
        ]);

        return [$academy, $user];
    }

    private function createStudent(Academy $academy, ?User $studentUser = null, string $firstName = 'Student'): Student
    {
        return Student::create([
            'academy_id' => $academy->id,
            'user_id' => $studentUser?->id,
            'student_id' => 'S'.uniqid(),
            'title_prefix_th' => 'นาย',
            'first_name_th' => $firstName,
            'last_name_th' => 'ทดสอบ',
            'status' => 'active',
        ]);
    }

    private function setupHomeroomTeacher(Academy $academy, Student $student, User $teacher): void
    {
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
            'status' => Classroom::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        ClassroomStudent::create([
            'academy_id' => $academy->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'student_number' => 1,
            'status' => ClassroomStudent::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);
        ClassroomMember::create([
            'classroom_id' => $classroom->id,
            'user_id' => $teacher->id,
            'role' => ClassroomMember::ROLE_TEACHER,
            'is_active' => true,
        ]);
        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $teacher->id,
            'role' => 'teacher',
            'status' => 2,
        ]);
    }

    public function test_owner_can_see_unverified_appointment_payload()
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        $sibling = $this->createStudent($academy, null, 'พี่');
        app(GuardianWriteService::class)->create($sibling, [
            'first_name' => 'Somchai', 'last_name' => 'Jaidee', 'citizen_id' => '1234567890123',
            'monthly_income' => 50000, 'guardian_type' => 'father', 'status' => 'alive',
        ]);
        $person = Guardian::where('citizen_id', '1234567890123')->firstOrFail();

        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser, 'น้อง');
        app(GuardianWriteService::class)->appoint($student, $person, ['guardian_type' => 'father'], 'student', $studentUser->id);

        $link = StudentGuardianLink::where('student_id', $student->id)->where('guardian_id', $person->id)->firstOrFail();

        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/profile");

        $response->assertOk();
        $response->assertJsonPath('data.guardians.0.link_id', $link->id);
        $response->assertJsonPath('data.guardians.0.is_verified', false);
        $response->assertJsonPath('data.guardians.0.appointed_by_role', 'student');
        $this->assertNull($response->json('data.guardians.0.verified_at'));
    }

    public function test_owner_can_see_verified_appointment_payload()
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        $sibling = $this->createStudent($academy, null, 'พี่');
        app(GuardianWriteService::class)->create($sibling, [
            'first_name' => 'Somchai', 'last_name' => 'Jaidee', 'citizen_id' => '1234567890123',
            'monthly_income' => 50000, 'guardian_type' => 'father', 'status' => 'alive',
        ]);
        $person = Guardian::where('citizen_id', '1234567890123')->firstOrFail();

        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser, 'น้อง');
        app(GuardianWriteService::class)->appoint($student, $person, ['guardian_type' => 'father'], 'student', $studentUser->id);

        $link = StudentGuardianLink::where('student_id', $student->id)->where('guardian_id', $person->id)->firstOrFail();
        $link->update(['verified_at' => now(), 'verified_by_user_id' => $owner->id]);

        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/profile");

        $response->assertOk();
        $response->assertJsonPath('data.guardians.0.link_id', $link->id);
        $response->assertJsonPath('data.guardians.0.is_verified', true);
        $this->assertNotNull($response->json('data.guardians.0.verified_at'));
    }

    public function test_owner_can_see_payload_for_normally_created_guardian()
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser, 'น้อง');

        app(GuardianWriteService::class)->create($student, [
            'first_name' => 'Somchai', 'last_name' => 'Jaidee', 'citizen_id' => '1234567890123',
            'monthly_income' => 50000, 'guardian_type' => 'father', 'status' => 'alive',
        ], 'staff');

        $link = StudentGuardianLink::where('student_id', $student->id)->firstOrFail();

        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/profile");

        $response->assertOk();
        $response->assertJsonPath('data.guardians.0.link_id', $link->id);
        $this->assertNotNull($response->json('data.guardians.0.link_id'));
    }

    public function test_homeroom_teacher_can_see_new_keys()
    {
        $academy = Academy::factory()->create();

        $sibling = $this->createStudent($academy, null, 'พี่');
        app(GuardianWriteService::class)->create($sibling, [
            'first_name' => 'Somchai', 'last_name' => 'Jaidee', 'citizen_id' => '1234567890123',
            'monthly_income' => 50000, 'guardian_type' => 'father', 'status' => 'alive',
        ]);
        $person = Guardian::where('citizen_id', '1234567890123')->firstOrFail();

        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser, 'น้อง');
        app(GuardianWriteService::class)->appoint($student, $person, ['guardian_type' => 'father'], 'student', $studentUser->id);
        $link = StudentGuardianLink::where('student_id', $student->id)->where('guardian_id', $person->id)->firstOrFail();

        $teacher = User::factory()->create();
        $this->setupHomeroomTeacher($academy, $student, $teacher);

        $response = $this->actingAs($teacher, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/profile");

        $response->assertOk();
        $response->assertJsonPath('data.guardians.0.link_id', $link->id);
        $response->assertJsonPath('data.guardians.0.is_verified', false);
        $response->assertJsonPath('data.guardians.0.appointed_by_role', 'student');
        $this->assertArrayHasKey('verified_at', $response->json('data.guardians.0'));
        $this->assertArrayNotHasKey('monthly_income', $response->json('data.guardians.0'));
    }

    public function test_student_can_see_new_keys_even_when_sensitive_fields_are_blocked()
    {
        $academy = Academy::factory()->create();

        $sibling = $this->createStudent($academy, null, 'พี่');
        app(GuardianWriteService::class)->create($sibling, [
            'first_name' => 'Somchai', 'last_name' => 'Jaidee', 'citizen_id' => '1234567890123',
            'monthly_income' => 50000, 'guardian_type' => 'father', 'status' => 'alive',
        ]);
        $person = Guardian::where('citizen_id', '1234567890123')->firstOrFail();

        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser, 'น้อง');
        app(GuardianWriteService::class)->appoint($student, $person, ['guardian_type' => 'father'], 'student', $studentUser->id);

        $link = StudentGuardianLink::where('student_id', $student->id)->where('guardian_id', $person->id)->firstOrFail();

        $response = $this->actingAs($studentUser, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/profile");

        $response->assertOk();

        $response->assertJsonPath('data.guardians.0.link_id', $link->id);
        $response->assertJsonPath('data.guardians.0.is_verified', false);
        $response->assertJsonPath('data.guardians.0.appointed_by_role', 'student');

        $this->assertArrayNotHasKey('citizen_id', $response->json('data.guardians.0'));
        $this->assertArrayNotHasKey('monthly_income', $response->json('data.guardians.0'));
    }
}
