<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentGuardianLink;
use App\Models\User;
use App\Services\GuardianWriteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardianUnverifiedAppointmentVisibilityTest extends TestCase
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

    public function test_student_cannot_see_sensitive_fields_of_unverified_shared_guardian()
    {
        $academy = Academy::factory()->create();

        // พี่
        $sibling = $this->createStudent($academy, null, 'พี่');
        app(GuardianWriteService::class)->create($sibling, [
            'first_name' => 'Somchai', 'last_name' => 'Jaidee', 'citizen_id' => '1234567890123',
            'monthly_income' => 50000, 'guardian_type' => 'father', 'status' => 'alive',
        ]);
        $person = Guardian::where('citizen_id', '1234567890123')->firstOrFail();

        // น้อง (ล็อกอิน)
        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser, 'น้อง');
        app(GuardianWriteService::class)->appoint($student, $person, ['guardian_type' => 'father'], 'student', $studentUser->id);

        // 1. นักเรียนที่ไปเกาะผู้ปกครองของพี่น้องแบบยังไม่ถูกยืนยัน
        $response = $this->actingAs($studentUser, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/guardians");

        $response->assertOk();
        $response->assertJsonMissingPath('data.guardian.citizen_id');
        $response->assertJsonMissingPath('data.guardian.monthly_income');

        // 4. เส้นทาง student profile
        $profileResponse = $this->actingAs($studentUser, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/profile");
        $profileResponse->assertOk();
        $this->assertArrayNotHasKey('citizen_id', $profileResponse->json('data.guardians.0'));
        $this->assertArrayNotHasKey('monthly_income', $profileResponse->json('data.guardians.0'));

        // 2. พอเจ้าหน้าที่ยืนยันแล้ว
        $link = StudentGuardianLink::where('student_id', $student->id)->where('guardian_id', $person->id)->first();
        $staff = User::factory()->create();
        $link->update(['verified_at' => now(), 'verified_by_user_id' => $staff->id]);

        $responseVerified = $this->actingAs($studentUser, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/guardians");
        $responseVerified->assertOk();
        $responseVerified->assertJsonPath('data.guardian.citizen_id', '1234567890123');

        $profileResponseVerified = $this->actingAs($studentUser, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/profile");
        $profileResponseVerified->assertOk();
        $profileResponseVerified->assertJsonPath('data.guardians.0.citizen_id', '1234567890123');
    }

    public function test_student_can_see_sensitive_fields_of_unverified_unshared_guardian()
    {
        $academy = Academy::factory()->create();
        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser, 'น้อง');

        // 3. ผู้ปกครองที่นักเรียนกรอกเองใหม่
        // Send actorRole = 'student' to GuardianWriteService::create
        app(GuardianWriteService::class)->create($student, [
            'first_name' => 'Somchai', 'last_name' => 'Jaidee', 'citizen_id' => '9999999999999',
            'monthly_income' => 50000, 'guardian_type' => 'father', 'status' => 'alive',
        ], 'student');

        // Link should be unverified, but count is 1.
        $response = $this->actingAs($studentUser, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/guardians");

        $response->assertOk();
        $response->assertJsonPath('data.guardian.citizen_id', '9999999999999');
    }

    public function test_staff_can_see_sensitive_fields_of_unverified_shared_guardian()
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

        // Ensure student endpoint works correctly
        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/guardians");
        $response->assertOk();
        $response->assertJsonPath('data.guardian.citizen_id', '1234567890123');

        // 6. เจ้าหน้าที่ที่มี guardians.sensitive.view
        [$academy2, $staff] = $this->academyWithMember(['guardians.view', 'guardians.sensitive.view']);
        $sibling2 = $this->createStudent($academy2, null, 'พี่2');
        app(GuardianWriteService::class)->create($sibling2, [
            'first_name' => 'Somchai', 'last_name' => 'Jaidee', 'citizen_id' => '3210987654321',
            'monthly_income' => 50000, 'guardian_type' => 'father', 'status' => 'alive',
        ]);
        $person2 = Guardian::where('citizen_id', '3210987654321')->firstOrFail();

        $studentUser2 = User::factory()->create();
        $student2 = $this->createStudent($academy2, $studentUser2, 'น้อง2');
        app(GuardianWriteService::class)->appoint($student2, $person2, ['guardian_type' => 'father'], 'student', $studentUser2->id);

        $staffResponse = $this->actingAs($staff, 'api')
            ->getJson("/api/academies/{$academy2->id}/students/{$student2->id}/guardians");
        $staffResponse->assertOk();
        $staffResponse->assertJsonPath('data.guardian.citizen_id', '3210987654321');
    }

    public function test_staff_can_see_sensitive_fields_in_student_resource()
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

        // 5. เส้นทาง StudentResource `GET /api/academies/{academy}/students/{student}` ด้วย token เจ้าของโรงเรียน -> ยังเห็นครบ
        $resourceResponse = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}?include=guardians");
        $resourceResponse->assertOk();
        $resourceResponse->assertJsonPath('student.guardians.0.citizen_id', '1234567890123');
    }
}
