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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GuardianAppointmentTest extends TestCase
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
            'status' => 2,
        ]);

        return [$academy, $user];
    }

    private function createStudent(Academy $academy, ?User $studentUser = null): Student
    {
        return Student::create([
            'academy_id' => $academy->id,
            'user_id' => $studentUser?->id,
            'student_id' => 'S'.uniqid(),
            'title_prefix_th' => 'นาย',
            'first_name_th' => 'ทดสอบ',
            'last_name_th' => 'ผู้ปกครอง',
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
    }

    public function test_student_can_appoint_guardian(): void
    {
        $academy = Academy::factory()->create();
        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser);

        $sibling = $this->createStudent($academy);
        app(GuardianWriteService::class)->create($sibling, [
            'citizen_id' => '1234567890123',
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'guardian_type' => 'father',
            'relationship' => 'father',
            'guardian_type' => 'father',
            'relationship' => 'father',
        ]);
        $guardian = Guardian::where('citizen_id', '1234567890123')->first();

        $response = $this->actingAs($studentUser, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/guardians/appoint", [
                'guardian_id' => $guardian->id, 'guardian_type' => 'father', 'relationship' => 'father',
                'guardian_type' => 'father',
                'relationship' => 'father',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('student_guardian_links', [
            'student_id' => $student->id,
            'guardian_id' => $guardian->id, 'guardian_type' => 'father', 'relationship' => 'father',
            'appointed_by_role' => 'student',
            'appointed_by_user_id' => $studentUser->id,
            'verified_at' => null,
        ]);
    }

    public function test_student_appointing_guardian_creates_a_link_with_no_legacy_row(): void
    {
        $academy = Academy::factory()->create();
        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser);

        $sibling = $this->createStudent($academy);
        app(GuardianWriteService::class)->create($sibling, [
            'citizen_id' => '1234567890123',
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'guardian_type' => 'father',
            'relationship' => 'father',
            'guardian_type' => 'father',
            'relationship' => 'father',
        ]);
        $guardian = Guardian::where('citizen_id', '1234567890123')->first();

        $this->actingAs($studentUser, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/guardians/appoint", [
                'guardian_id' => $guardian->id, 'guardian_type' => 'father', 'relationship' => 'father',
            ]);

        $this->assertDatabaseHas('student_guardian_links', [
            'student_id' => $student->id,
            'guardian_id' => $guardian->id,
            'appointed_by_role' => 'student',
            'legacy_row_ids' => null,
        ]);
    }

    public function test_homeroom_teacher_can_appoint_guardian(): void
    {
        [$academy, $teacher] = $this->academyWithMember();
        $student = $this->createStudent($academy);
        $this->setupHomeroomTeacher($academy, $student, $teacher);

        $sibling = $this->createStudent($academy);
        app(GuardianWriteService::class)->create($sibling, [
            'citizen_id' => '1234567890123',
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'guardian_type' => 'father',
            'relationship' => 'father',
            'guardian_type' => 'father',
            'relationship' => 'father',
        ]);
        $guardian = Guardian::where('citizen_id', '1234567890123')->first();

        $response = $this->actingAs($teacher, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/guardians/appoint", [
                'guardian_id' => $guardian->id, 'guardian_type' => 'father', 'relationship' => 'father',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('student_guardian_links', [
            'student_id' => $student->id,
            'guardian_id' => $guardian->id, 'guardian_type' => 'father', 'relationship' => 'father',
            'appointed_by_role' => 'homeroom',
        ]);
    }

    public function test_teacher_without_permission_and_not_homeroom_cannot_appoint(): void
    {
        [$academy, $teacher] = $this->academyWithMember(); // No guardians.appoint key
        $student = $this->createStudent($academy); // Not in their classroom

        $sibling = $this->createStudent($academy);
        app(GuardianWriteService::class)->create($sibling, [
            'citizen_id' => '1234567890123',
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'guardian_type' => 'father',
            'relationship' => 'father',
            'guardian_type' => 'father',
            'relationship' => 'father',
        ]);
        $guardian = Guardian::where('citizen_id', '1234567890123')->first();

        $response = $this->actingAs($teacher, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/guardians/appoint", [
                'guardian_id' => $guardian->id, 'guardian_type' => 'father', 'relationship' => 'father',
            ]);

        $response->assertStatus(403);
    }

    public function test_staff_with_permission_can_appoint(): void
    {
        [$academy, $staff] = $this->academyWithMember(['guardians.appoint']);
        $student = $this->createStudent($academy);

        $sibling = $this->createStudent($academy);
        app(GuardianWriteService::class)->create($sibling, [
            'citizen_id' => '1234567890123',
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'guardian_type' => 'father',
            'relationship' => 'father',
            'guardian_type' => 'father',
            'relationship' => 'father',
        ]);
        $guardian = Guardian::where('citizen_id', '1234567890123')->first();

        $response = $this->actingAs($staff, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/guardians/appoint", [
                'guardian_id' => $guardian->id, 'guardian_type' => 'father', 'relationship' => 'father',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('student_guardian_links', [
            'student_id' => $student->id,
            'guardian_id' => $guardian->id, 'guardian_type' => 'father', 'relationship' => 'father',
            'appointed_by_role' => 'staff',
        ]);
    }

    public function test_appointing_same_guardian_returns_409(): void
    {
        $academy = Academy::factory()->create();
        $owner = User::factory()->create();
        $academy->update(['user_id' => $owner->id]);
        $student = $this->createStudent($academy);

        $guardian = app(GuardianWriteService::class)->create($student, [
            'citizen_id' => '1234567890123',
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'guardian_type' => 'father',
            'relationship' => 'father',
        ]);

        $guardianId = Guardian::where('citizen_id', '1234567890123')->first()->id;

        $response = $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/guardians/appoint", [
                'guardian_id' => $guardianId, 'guardian_type' => 'father', 'relationship' => 'father',
            ]);

        $response->assertStatus(409);
    }

    public function test_cannot_appoint_guardian_from_another_academy(): void
    {
        $academy = Academy::factory()->create();
        $owner = User::factory()->create();
        $academy->update(['user_id' => $owner->id]);
        $student = $this->createStudent($academy);

        $otherAcademy = Academy::factory()->create();
        $sibling = $this->createStudent($otherAcademy);
        app(GuardianWriteService::class)->create($sibling, [
            'citizen_id' => '1234567890123',
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'guardian_type' => 'father',
            'relationship' => 'father',
        ]);
        $guardian = Guardian::where('citizen_id', '1234567890123')->first();

        $response = $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/guardians/appoint", [
                'guardian_id' => $guardian->id, 'guardian_type' => 'father', 'relationship' => 'father',
            ]);

        $response->assertStatus(404);
        $this->assertDatabaseMissing('student_guardian_links', [
            'student_id' => $student->id,
            'guardian_id' => $guardian->id, 'guardian_type' => 'father', 'relationship' => 'father',
        ]);
    }

    public function test_match_returns_200_and_hides_sensitive_info_when_details_match(): void
    {
        $academy = Academy::factory()->create();
        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser);

        $sibling = $this->createStudent($academy);
        app(GuardianWriteService::class)->create($sibling, [
            'citizen_id' => '1234567890123',
            'first_name' => 'สมชาย',
            'last_name' => 'ใจดี',
            'guardian_type' => 'father',
            'relationship' => 'father',
            'monthly_income' => 50000,
        ]);
        $guardian = Guardian::where('citizen_id', '1234567890123')->first();

        $response = $this->actingAs($studentUser, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/guardians/match", [
                'citizen_id' => '1234567890123',
                'first_name' => 'สมชาย',
                'last_name' => 'ใจดี',
                'guardian_type' => 'father',
                'relationship' => 'father',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $guardian->id);
        $response->assertJsonPath('data.children_count', 1);
        $response->assertJsonMissingPath('data.citizen_id');
        $response->assertJsonMissingPath('data.monthly_income');
    }

    public function test_match_returns_null_when_last_name_mismatches(): void
    {
        $academy = Academy::factory()->create();
        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser);

        $sibling = $this->createStudent($academy);
        app(GuardianWriteService::class)->create($sibling, [
            'citizen_id' => '1234567890123',
            'first_name' => 'สมชาย',
            'last_name' => 'ใจดี',
            'guardian_type' => 'father',
            'relationship' => 'father',
        ]);

        $response = $this->actingAs($studentUser, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/guardians/match", [
                'citizen_id' => '1234567890123',
                'first_name' => 'สมชาย',
                'last_name' => 'ใจร้าย',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data', null);
    }

    public function test_search_forbidden_for_student_but_allowed_for_owner(): void
    {
        $academy = Academy::factory()->create();
        $owner = User::factory()->create();
        $academy->update(['user_id' => $owner->id]);
        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser);

        $sibling = $this->createStudent($academy);
        app(GuardianWriteService::class)->create($sibling, [
            'citizen_id' => '1234567890123',
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'guardian_type' => 'father',
            'relationship' => 'father',
            'monthly_income' => 50000,
        ]);

        // Student tries to search
        $responseStudent = $this->actingAs($studentUser, 'api')
            ->getJson("/api/academies/{$academy->id}/guardians/search?q=Somchai");

        $responseStudent->assertStatus(403);

        // Owner tries to search
        $responseOwner = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/guardians/search?q=Somchai");

        $responseOwner->assertStatus(200);
        $responseOwner->assertJsonMissingPath('data.0.citizen_id');
        $responseOwner->assertJsonMissingPath('data.0.monthly_income');
    }

    public function test_student_cannot_verify_own_appointment(): void
    {
        $academy = Academy::factory()->create();
        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser);

        $sibling = $this->createStudent($academy);
        app(GuardianWriteService::class)->create($sibling, [
            'citizen_id' => '1234567890123',
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'guardian_type' => 'father',
            'relationship' => 'father',
        ]);
        $guardian = Guardian::where('citizen_id', '1234567890123')->first();

        $this->actingAs($studentUser, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/guardians/appoint", [
                'guardian_id' => $guardian->id, 'guardian_type' => 'father', 'relationship' => 'father',
            ]);

        $link = StudentGuardianLink::where('student_id', $student->id)->where('guardian_id', $guardian->id)->first();

        $response = $this->actingAs($studentUser, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/guardians/links/{$link->id}/verify");

        $response->assertStatus(403);
        $this->assertNull($link->fresh()->verified_at);
    }

    public function test_homeroom_teacher_can_verify(): void
    {
        [$academy, $teacher] = $this->academyWithMember();
        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser);
        $this->setupHomeroomTeacher($academy, $student, $teacher);

        $sibling = $this->createStudent($academy);
        app(GuardianWriteService::class)->create($sibling, [
            'citizen_id' => '1234567890123',
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'guardian_type' => 'father',
            'relationship' => 'father',
        ]);
        $guardian = Guardian::where('citizen_id', '1234567890123')->first();

        $this->actingAs($studentUser, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/guardians/appoint", [
                'guardian_id' => $guardian->id, 'guardian_type' => 'father', 'relationship' => 'father',
            ]);

        $link = StudentGuardianLink::where('student_id', $student->id)->where('guardian_id', $guardian->id)->first();

        $response = $this->actingAs($teacher, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/guardians/links/{$link->id}/verify");

        $response->assertStatus(200);
        $this->assertNotNull($link->fresh()->verified_at);
        $this->assertEquals($teacher->id, $link->fresh()->verified_by_user_id);
    }

    public function test_verifying_already_verified_link_returns_409(): void
    {
        [$academy, $teacher] = $this->academyWithMember();
        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser);
        $this->setupHomeroomTeacher($academy, $student, $teacher);

        $sibling = $this->createStudent($academy);
        app(GuardianWriteService::class)->create($sibling, [
            'citizen_id' => '1234567890123',
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'guardian_type' => 'father',
            'relationship' => 'father',
        ]);
        $guardian = Guardian::where('citizen_id', '1234567890123')->first();

        $this->actingAs($studentUser, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/guardians/appoint", [
                'guardian_id' => $guardian->id, 'guardian_type' => 'father', 'relationship' => 'father',
            ]);

        $link = StudentGuardianLink::where('student_id', $student->id)->where('guardian_id', $guardian->id)->first();

        // First verification
        $this->actingAs($teacher, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/guardians/links/{$link->id}/verify");

        // Second verification
        $response = $this->actingAs($teacher, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/guardians/links/{$link->id}/verify");

        $response->assertStatus(409);
    }

    public function test_appoint_and_verify_create_activity_logs_without_sensitive_info(): void
    {
        [$academy, $teacher] = $this->academyWithMember();
        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser);
        $this->setupHomeroomTeacher($academy, $student, $teacher);

        $sibling = $this->createStudent($academy);
        app(GuardianWriteService::class)->create($sibling, [
            'citizen_id' => '1234567890123',
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'guardian_type' => 'father',
            'relationship' => 'father',
            'monthly_income' => 50000,
        ]);
        $guardian = Guardian::where('citizen_id', '1234567890123')->first();

        $this->actingAs($studentUser, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/guardians/appoint", [
                'guardian_id' => $guardian->id, 'guardian_type' => 'father', 'relationship' => 'father',
            ]);

        $appointLog = DB::table('member_activity_logs')
            ->where('academy_id', $academy->id)
            ->where('action', 'guardian_appoint')
            ->latest('id')
            ->first();

        $this->assertNotNull($appointLog);

        $newValues = json_decode($appointLog->new_values, true);
        $this->assertArrayNotHasKey('citizen_id', $newValues);
        $this->assertArrayNotHasKey('monthly_income', $newValues);

        $link = StudentGuardianLink::where('student_id', $student->id)->where('guardian_id', $guardian->id)->first();

        $this->actingAs($teacher, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/guardians/links/{$link->id}/verify");

        $verifyLog = DB::table('member_activity_logs')
            ->where('academy_id', $academy->id)
            ->where('action', 'guardian_verify')
            ->latest('id')
            ->first();

        $this->assertNotNull($verifyLog);
    }
}
