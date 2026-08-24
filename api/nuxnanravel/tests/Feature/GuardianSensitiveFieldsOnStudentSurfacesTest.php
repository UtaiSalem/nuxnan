<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Student;
use App\Models\User;
use App\Services\GuardianWriteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardianSensitiveFieldsOnStudentSurfacesTest extends TestCase
{
    use RefreshDatabase;

    private function academyWithMember(array $permissions = [], string $roleName = 'teacher'): array
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
            'role' => $roleName,
            'academy_role_id' => $role->id,
            'status' => 2, // approved
        ]);

        return [$academy, $user];
    }

    private function createStudentWithGuardian(Academy $academy, ?User $studentUser = null): array
    {
        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $studentUser?->id,
            'student_id' => 'S'.uniqid(),
            'title_prefix_th' => 'นาย',
            'first_name_th' => 'ทดสอบ',
            'last_name_th' => 'นักเรียน',
            'status' => 'active',
        ]);

        $guardianData = [
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'citizen_id' => '1234567890123',
            'monthly_income' => 50000,
            'guardian_type' => 'father',
            'relationship' => 'father',
            'workplace' => 'Google',
            'status' => 'alive',
        ];

        $guardian = app(GuardianWriteService::class)->create($student, $guardianData);

        return [$student, $guardian];
    }

    // 1. GET /api/academies/{academy}/students/{student}/profile ด้วยครูที่ไม่ใช่ครูประจำชั้นและไม่มีคีย์
    // -> ก้อน guardians[0] ไม่มีคีย์ citizen_id และ monthly_income
    public function test_teacher_without_sensitive_view_cannot_see_sensitive_fields_on_profile(): void
    {
        [$academy, $user] = $this->academyWithMember(['students.view'], 'teacher');
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/profile");

        $response->assertOk();
        $response->assertJsonMissingPath('data.guardians.0.citizen_id');
        $response->assertJsonMissingPath('data.guardians.0.monthly_income');
        $response->assertJsonPath('data.guardians.0.first_name', 'Somchai');
    }

    // 2. เคสเดียวกันแต่ผู้ใช้มีคีย์ guardians.sensitive.view -> เห็นทั้ง 2 คีย์
    public function test_teacher_with_sensitive_view_can_see_sensitive_fields_on_profile(): void
    {
        [$academy, $user] = $this->academyWithMember(['students.view', 'guardians.sensitive.view'], 'teacher');
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/profile");

        $response->assertOk();
        $response->assertJsonPath('data.guardians.0.citizen_id', '1234567890123');
        $response->assertJsonPath('data.guardians.0.monthly_income', '50000.00'); // DB casts decimal
    }

    // 3. นักเรียนเจ้าของโปรไฟล์เอง -> เห็นทั้ง 2 คีย์ (พฤติกรรมเดิมต้องไม่เสีย)
    public function test_student_owner_can_see_sensitive_fields_on_profile(): void
    {
        $academy = Academy::factory()->create();
        $studentUser = User::factory()->create();
        [$student, $guardian] = $this->createStudentWithGuardian($academy, $studentUser);

        $response = $this->actingAs($studentUser, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/profile");

        $response->assertOk();
        $response->assertJsonPath('data.guardians.0.citizen_id', '1234567890123');
        $response->assertJsonPath('data.guardians.0.monthly_income', '50000.00');
    }

    // 4. workplace ต้องอยู่ใน response ทุกกรณี รวมกรณีข้อ 1
    public function test_workplace_is_always_visible_on_profile(): void
    {
        [$academy, $user] = $this->academyWithMember(['students.view'], 'teacher'); // No sensitive view
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/profile");

        $response->assertOk();
        $response->assertJsonMissingPath('data.guardians.0.citizen_id'); // Ensure it's hidden
        $response->assertJsonPath('data.guardians.0.workplace', 'Google');
    }

    // 5. เส้นทาง StudentResource: ยิง endpoint /api/student/master/{student} -> ครูที่ไม่ใช่ครูประจำชั้นต้องไม่เห็น
    public function test_teacher_without_sensitive_view_cannot_see_sensitive_fields_on_student_resource(): void
    {
        [$academy, $user] = $this->academyWithMember(['students.view'], 'teacher');
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/student/master/{$student->id}");

        $response->assertOk();
        $response->assertJsonMissingPath('data.guardians.0.citizen_id');
        $response->assertJsonMissingPath('data.guardians.0.monthly_income');
        $response->assertJsonPath('data.guardians.0.first_name', 'Somchai');
    }

    // 6. เส้นทาง ClassroomController::getStudent: เจ้าของโรงเรียนเห็นครบ
    public function test_academy_owner_can_see_sensitive_fields_on_classroom_get_student(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}");

        $response->assertOk();
        $response->assertJsonPath('student.guardians.0.citizen_id', '1234567890123');
        $response->assertJsonPath('student.guardians.0.monthly_income', '50000.00');
    }
}
