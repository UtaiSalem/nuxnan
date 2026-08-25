<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\AcademyGroupMember;
use App\Models\AcademyGroupPermission;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Student;
use App\Models\User;
use App\Services\GuardianWriteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardianAuthorizationTest extends TestCase
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

    private function academyWithGroupPermission(?bool $enabled): array
    {
        [$academy, $user] = $this->academyWithMember([]);
        $group = AcademyGroup::create(['academy_id' => $academy->id, 'name' => 'Assigned department', 'type' => 'department']);
        AcademyGroupMember::create(['academy_group_id' => $group->id, 'user_id' => $user->id, 'status' => 2]);

        if ($enabled !== null) {
            AcademyGroupPermission::create(['academy_group_id' => $group->id, 'permission_key' => 'guardians.view', 'enabled' => $enabled]);
        }

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
            'guardian_type' => 'father',
            'relationship' => 'father',
            'status' => 'alive',
        ];

        $guardian = app(GuardianWriteService::class)->create($student, $guardianData);

        return [$student, $guardian];
    }

    public function test_non_member_cannot_view_guardians(): void
    {
        $academy = Academy::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/guardians")
            ->assertForbidden();
    }

    public function test_member_without_permission_cannot_view_guardians(): void
    {
        [$academy, $user] = $this->academyWithMember([]);

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/guardians")
            ->assertForbidden();
    }

    public function test_member_with_view_permission_can_view_guardians(): void
    {
        [$academy, $user] = $this->academyWithMember(['guardians.view']);

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/guardians")
            ->assertOk();
    }

    public function test_academy_owner_can_view_guardians(): void
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/guardians")
            ->assertOk();
    }

    public function test_member_with_department_permission_can_view_guardians(): void
    {
        [$academy, $user] = $this->academyWithGroupPermission(true);

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/guardians")
            ->assertOk();
    }

    public function test_member_with_only_view_cannot_update_guardian(): void
    {
        [$academy, $user] = $this->academyWithMember(['guardians.view']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $this->actingAs($user, 'api')
            ->patchJson("/api/academies/{$academy->id}/guardians/{$guardian->id}", [
                'first_name' => 'Updated',
            ])
            ->assertForbidden();
    }

    public function test_member_with_manage_permission_can_update_guardian(): void
    {
        [$academy, $user] = $this->academyWithMember(['guardians.manage']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $this->actingAs($user, 'api')
            ->patchJson("/api/academies/{$academy->id}/guardians/{$guardian->id}", [
                'first_name' => 'UpdatedName',
            ])
            ->assertOk();

        $this->assertDatabaseHas('guardians', [
            'id' => $guardian->guardian_id,
            'first_name' => 'UpdatedName',
        ]);
    }

    public function test_link_user_returns_501_and_creates_no_member(): void
    {
        [$academy, $user] = $this->academyWithMember(['guardians.manage']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $parentUser = User::factory()->create();

        $initialMemberCount = AcademyMember::count();

        $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/guardians/{$guardian->id}/link-user", [
                'user_id' => $parentUser->id,
            ])
            ->assertStatus(501);

        $this->assertEquals($initialMemberCount, AcademyMember::count());
        $this->assertDatabaseMissing('academy_members', [
            'user_id' => $parentUser->id,
            'academy_id' => $academy->id,
        ]);
    }

    public function test_student_can_view_own_guardians(): void
    {
        $academy = Academy::factory()->create();
        $studentUser = User::factory()->create();
        [$student, $guardian] = $this->createStudentWithGuardian($academy, $studentUser);

        $this->actingAs($studentUser, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/guardians")
            ->assertOk();
    }

    public function test_non_homeroom_teacher_cannot_view_student_guardians_without_permission(): void
    {
        [$academy, $teacher] = $this->academyWithMember([]);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);

        $this->actingAs($teacher, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/guardians")
            ->assertForbidden();
    }
}
