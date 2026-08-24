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

class GuardianSensitiveFieldsTest extends TestCase
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

    private function createStudentWithGuardian(Academy $academy, ?User $studentUser = null): array
    {
        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $studentUser?->id,
            'student_id' => 'S'.uniqid(),
            'title_prefix_th' => 'นาย',
            'first_name_th' => 'ทดสอบ',
            'last_name_th' => 'ผู้ปกครอง',
            'status' => 'active',
        ]);

        $guardianData = [
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'citizen_id' => '1234567890123',
            'monthly_income' => 50000,
            'guardian_type' => 'father',
            'relationship' => 'father',
            'status' => 'alive',
        ];

        $guardian = app(GuardianWriteService::class)->create($student, $guardianData);

        return [$student, $guardian];
    }

    // 1. สมาชิกที่มี guardians.view แต่ไม่มี guardians.sensitive.view -> GET students/{student}/guardians
    // response ไม่มีคีย์ citizen_id และ monthly_income
    public function test_member_without_sensitive_view_cannot_see_sensitive_fields(): void
    {
        [$academy, $user] = $this->academyWithMember(['guardians.view']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/guardians");

        $response->assertOk();
        $response->assertJsonMissingPath('data.guardian.citizen_id');
        $response->assertJsonMissingPath('data.guardian.monthly_income');
        // ensure other keys are there
        $response->assertJsonPath('data.guardian.first_name', 'Somchai');
    }

    // 2. สมาชิกที่มี guardians.sensitive.view -> เห็นทั้ง 2 คีย์และค่าตรงกับในฐาน
    public function test_member_with_sensitive_view_can_see_sensitive_fields(): void
    {
        [$academy, $user] = $this->academyWithMember(['guardians.view', 'guardians.sensitive.view']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/guardians");

        $response->assertOk();
        $response->assertJsonPath('data.guardian.citizen_id', '1234567890123');
        $response->assertJsonPath('data.guardian.monthly_income', '50000.00'); // DB casts decimal
    }

    // 3. นักเรียนเจ้าของโปรไฟล์ (ไม่มีคีย์อะไรเลย) -> เห็นทั้ง 2 คีย์
    public function test_student_owner_can_see_sensitive_fields(): void
    {
        $academy = Academy::factory()->create();
        $studentUser = User::factory()->create();
        [$student, $guardian] = $this->createStudentWithGuardian($academy, $studentUser);

        $response = $this->actingAs($studentUser, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/guardians");

        $response->assertOk();
        $response->assertJsonPath('data.guardian.citizen_id', '1234567890123');
        $response->assertJsonPath('data.guardian.monthly_income', '50000.00');
    }

    // 4. เจ้าของโรงเรียน -> เห็นทั้ง 2 คีย์
    public function test_academy_owner_can_see_sensitive_fields(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/guardians");

        $response->assertOk();
        $response->assertJsonPath('data.guardian.citizen_id', '1234567890123');
        $response->assertJsonPath('data.guardian.monthly_income', '50000.00');
    }

    // 5. สมาชิกที่มี guardians.manage แต่ไม่มี guardians.sensitive.manage ยิง PATCH /guardians/{id}
    // พร้อม citizen_id ค่าใหม่ -> 403 และค่าในฐานไม่เปลี่ยน
    public function test_member_without_sensitive_manage_cannot_update_sensitive_fields(): void
    {
        [$academy, $user] = $this->academyWithMember(['guardians.manage']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($user, 'api')
            ->patchJson("/api/academies/{$academy->id}/guardians/{$guardian->id}", [
                'first_name' => 'Somchai Updated',
                'citizen_id' => '9999999999999',
            ]);

        $response->assertForbidden();
        $this->assertStringContainsString('ไม่มีสิทธิ์แก้ไขข้อมูลอ่อนไหว', $response->json('message'));

        $this->assertDatabaseHas('student_guardians', [
            'id' => $guardian->id,
            'first_name' => 'Somchai', // Not updated
        ]);
    }

    // 6. เคสเดียวกันแต่ส่ง citizen_id ค่าเดิม พร้อม first_name ใหม่ -> 200 และชื่อเปลี่ยนจริง
    public function test_member_without_sensitive_manage_can_update_other_fields_if_sensitive_unchanged(): void
    {
        [$academy, $user] = $this->academyWithMember(['guardians.manage']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($user, 'api')
            ->patchJson("/api/academies/{$academy->id}/guardians/{$guardian->id}", [
                'first_name' => 'Somchai Updated',
                'citizen_id' => '1234567890123', // Original
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('student_guardians', [
            'id' => $guardian->id,
            'first_name' => 'Somchai Updated',
        ]);
    }

    // 7. สมาชิกที่มี guardians.sensitive.manage ส่ง monthly_income ค่าใหม่ -> 200 และค่าเปลี่ยนจริงในฐาน
    public function test_member_with_sensitive_manage_can_update_sensitive_fields(): void
    {
        [$academy, $user] = $this->academyWithMember(['guardians.manage', 'guardians.sensitive.manage']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($user, 'api')
            ->patchJson("/api/academies/{$academy->id}/guardians/{$guardian->id}", [
                'first_name' => 'Somchai Updated',
                'monthly_income' => 60000,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('student_guardians', [
            'id' => $guardian->id,
            'monthly_income' => 60000,
        ]);
    }

    // 8. response ของ PATCH ที่สำเร็จโดยคนที่ไม่มี sensitive.view -> ไม่มี citizen_id/monthly_income ในก้อน guardian
    public function test_patch_response_hides_sensitive_fields_if_missing_view_permission(): void
    {
        // Give manage (for other fields) but no sensitive.view
        [$academy, $user] = $this->academyWithMember(['guardians.manage']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($user, 'api')
            ->patchJson("/api/academies/{$academy->id}/guardians/{$guardian->id}", [
                'first_name' => 'Somchai Updated',
            ]);

        $response->assertOk();

        $response->assertJsonMissingPath('guardian.citizen_id');
        $response->assertJsonMissingPath('guardian.monthly_income');
        $response->assertJsonPath('guardian.first_name', 'Somchai Updated');
    }
}
