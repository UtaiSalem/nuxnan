<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\MemberActivityLog;
use App\Models\Student;
use App\Models\User;
use App\Services\GuardianWriteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardianAuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function academyWithMember(array $permissions = []): array
    {
        $academy = Academy::factory()->create();
        $user = User::factory()->create();

        $roleId = null;
        if (! empty($permissions)) {
            $role = AcademyRole::create([
                'academy_id' => $academy->id,
                'name' => 'test-role-'.uniqid(),
                'display_name_th' => 'Test role',
                'permissions' => $permissions,
            ]);
            $roleId = $role->id;
        }

        $member = AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'status' => 2,
            'academy_role_id' => $roleId,
        ]);

        return [$academy, $user, $member];
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

    // 1. POST students/{student}/guardians Success → has row `guardian_create`
    public function test_post_guardian_creates_log(): void
    {
        [$academy, $user] = $this->academyWithMember(['guardians.manage']);
        $student = Student::create([
            'academy_id' => $academy->id,
            'student_id' => 'S'.uniqid(),
            'first_name_th' => 'Test',
            'last_name_th' => 'Student',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/guardians", [
                'guardian' => [
                    'first_name' => 'Somchai',
                    'last_name' => 'Jaidee',
                    'guardian_type' => 'father',
                ],
                'contact' => [
                    'contact_type' => 'phone',
                    'contact_value' => '0899999999',
                ],
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('member_activity_logs', [
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'action' => 'guardian_create',
        ]);
    }

    // 2. PATCH guardians/{id} Success → guardian_update
    public function test_patch_guardian_creates_update_log(): void
    {
        [$academy, $user] = $this->academyWithMember(['guardians.manage']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($user, 'api')
            ->patchJson("/api/academies/{$academy->id}/guardians/{$guardian->id}", [
                'first_name' => 'Updated Name',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('member_activity_logs', [
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'action' => 'guardian_update',
        ]);
    }

    // 3. DELETE guardians/{id} Success → guardian_delete
    public function test_delete_guardian_creates_delete_log(): void
    {
        [$academy, $user] = $this->academyWithMember(['guardians.manage']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($user, 'api')
            ->deleteJson("/api/academies/{$academy->id}/guardians/{$guardian->id}");

        $response->assertOk();

        $this->assertDatabaseHas('member_activity_logs', [
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'action' => 'guardian_delete',
        ]);
    }

    // 4. คำขอที่ถูกปฏิเสธ (403) ต้องไม่สร้างแถวล็อก
    public function test_forbidden_request_does_not_create_log(): void
    {
        [$academy, $user] = $this->academyWithMember(['guardians.view']); // no manage
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $beforeCount = MemberActivityLog::count();

        $response = $this->actingAs($user, 'api')
            ->patchJson("/api/academies/{$academy->id}/guardians/{$guardian->id}", [
                'first_name' => 'Updated Name',
            ]);

        $response->assertForbidden();

        $this->assertEquals($beforeCount, MemberActivityLog::count());
    }

    // 5. ค่าอ่อนไหวต้องไม่ถูกเก็บลงล็อก
    public function test_sensitive_fields_are_redacted_in_log(): void
    {
        [$academy, $user] = $this->academyWithMember(['guardians.manage', 'guardians.sensitive.manage']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($user, 'api')
            ->patchJson("/api/academies/{$academy->id}/guardians/{$guardian->id}", [
                'citizen_id' => '9999999999999',
            ]);

        $response->assertOk();

        $log = MemberActivityLog::where('action', 'guardian_update')->first();
        $this->assertNotNull($log);

        $newValues = json_encode($log->new_values);
        $this->assertStringNotContainsString('9999999999999', $newValues);
        $this->assertStringContainsString('"citizen_id":"changed"', $newValues);
    }

    // 6. PUT students/{student}/guardians (Master controller) Success → guardian_update
    public function test_master_put_guardian_creates_update_log(): void
    {
        [$academy, $user] = $this->academyWithMember(['guardians.manage']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($user, 'api')
            ->putJson("/api/academies/{$academy->id}/students/{$student->id}/guardians", [
                'guardian' => [
                    'first_name' => 'Master Update',
                    'last_name' => 'Jaidee',
                    'guardian_type' => 'father',
                ],
                'contact' => [
                    'contact_type' => 'phone',
                    'contact_value' => '0899999999',
                ],
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('member_activity_logs', [
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'action' => 'guardian_update',
        ]);
    }

    // 7. response ของ PUT students/{student}/guardians โดยคนที่ไม่มี sensitive.view → ไม่มีคีย์
    public function test_master_put_response_hides_sensitive_fields_if_missing_view_permission(): void
    {
        [$academy, $user] = $this->academyWithMember(['guardians.manage']); // No sensitive view
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($user, 'api')
            ->putJson("/api/academies/{$academy->id}/students/{$student->id}/guardians", [
                'guardian' => [
                    'first_name' => 'Master Update',
                    'last_name' => 'Jaidee',
                    'guardian_type' => 'father',
                ],
                'contact' => [
                    'contact_type' => 'phone',
                    'contact_value' => '0899999999',
                ],
            ]);

        $response->assertOk();
        $response->assertJsonMissingPath('data.guardian.citizen_id');
        $response->assertJsonMissingPath('data.guardian.monthly_income');
        $response->assertJsonPath('data.guardian.first_name', 'Master Update');
    }
}
