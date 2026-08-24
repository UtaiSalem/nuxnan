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

class GuardianSensitiveViewLogTest extends TestCase
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
            'status' => 'alive',
        ];

        $guardian = app(GuardianWriteService::class)->create($student, $guardianData);

        return [$student, $guardian];
    }

    // 1. คนที่มี guardians.sensitive.view เปิด /students/{student}/profile -> มีแถว action guardian_sensitive_view 1 แถว
    public function test_sensitive_view_is_logged_on_profile()
    {
        [$academy, $user] = $this->academyWithMember(['students.view', 'guardians.sensitive.view']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/profile");
        $response->assertOk();

        $this->assertDatabaseHas('member_activity_logs', [
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'action' => MemberActivityLog::ACTION_GUARDIAN_SENSITIVE_VIEW,
            'new_values->student_id' => $student->id,
        ]);
    }

    // 2. เปิดซ้ำครั้งที่ 2 ทันที -> ยังมีแถวเดียว (พิสูจน์ dedupe)
    public function test_sensitive_view_is_deduped()
    {
        [$academy, $user] = $this->academyWithMember(['students.view', 'guardians.sensitive.view']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/profile");

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/profile");

        $this->assertDatabaseCount('member_activity_logs', 1);
    }

    // 3. คนที่ไม่มีสิทธิ์เปิดหน้าเดิม -> ไม่มีแถวล็อกเลย
    public function test_no_log_without_sensitive_permission()
    {
        [$academy, $user] = $this->academyWithMember(['students.view']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/profile");

        $this->assertDatabaseCount('member_activity_logs', 0);
    }

    // 4. นักเรียนเปิดโปรไฟล์ตัวเอง -> ไม่มีแถวล็อก
    public function test_no_log_when_student_views_own_profile()
    {
        $academy = Academy::factory()->create();
        $studentUser = User::factory()->create();
        [$student, $guardian] = $this->createStudentWithGuardian($academy, $studentUser);

        $this->actingAs($studentUser, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/profile");

        $this->assertDatabaseCount('member_activity_logs', 0);
    }

    // 5. นักเรียนที่ไม่มีผู้ปกครองเลย ถูกเปิดโดยคนที่มีสิทธิ์ -> ไม่มีแถวล็อก
    public function test_no_log_when_student_has_no_guardians()
    {
        [$academy, $user] = $this->academyWithMember(['students.view', 'guardians.sensitive.view']);

        $student = Student::create([
            'academy_id' => $academy->id,
            'student_id' => 'S'.uniqid(),
            'title_prefix_th' => 'นาย',
            'first_name_th' => 'ทดสอบ',
            'last_name_th' => 'นักเรียน',
            'status' => 'active',
        ]);

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/profile");

        $this->assertDatabaseCount('member_activity_logs', 0);
    }

    // 6. GET /students/{student}/guardians (Master show) โดยคนที่มีสิทธิ์ -> มีแถวล็อก
    public function test_sensitive_view_is_logged_on_guardian_master()
    {
        [$academy, $user] = $this->academyWithMember(['students.view', 'guardians.view', 'guardians.sensitive.view']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/guardians");
        $response->assertOk();

        $this->assertDatabaseHas('member_activity_logs', [
            'action' => MemberActivityLog::ACTION_GUARDIAN_SENSITIVE_VIEW,
            'new_values->student_id' => $student->id,
        ]);
    }

    // 7. ผู้ใช้ คนละคน ดูนักเรียนคนเดียวกันในช่วงเวลาเดียวกัน -> ได้ 2 แถว
    public function test_different_users_viewing_same_student_creates_separate_logs()
    {
        [$academy, $user1] = $this->academyWithMember(['students.view', 'guardians.sensitive.view']);

        $user2 = User::factory()->create();
        $role = AcademyRole::where('academy_id', $academy->id)->first();
        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $user2->id,
            'role' => 'teacher',
            'academy_role_id' => $role->id,
            'status' => 2,
        ]);

        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $this->actingAs($user1, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/profile");

        $this->actingAs($user2, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/profile");

        $this->assertDatabaseCount('member_activity_logs', 2);
    }
}
