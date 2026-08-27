<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\User;
use App\Services\GuardianAccountLinkService;
use App\Services\GuardianWriteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardianAccountEndpointsTest extends TestCase
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
            'guardian_type' => 'father',
            'relationship' => 'father',
            'status' => 'alive',
        ];

        $link = app(GuardianWriteService::class)->create($student, $guardianData);

        return [$student, $link->guardian];
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

    public function test_search_by_username_exact_match_hides_sensitive_fields()
    {
        [$academy, $member] = $this->academyWithMember(['guardians.appoint']);
        $target = User::factory()->create(['username' => 'test_parent_user', 'email' => 'a@b.com', 'phone_number' => '0812345678']);

        $response = $this->actingAs($member, 'api')
            ->getJson("/api/academies/{$academy->id}/guardian-accounts/search?q=test_parent_user");

        $response->assertOk()
            ->assertJsonPath('data.username', 'test_parent_user')
            ->assertJsonMissing(['email' => 'a@b.com'])
            ->assertJsonMissing(['phone_number' => '0812345678'])
            ->assertJsonMissing(['personal_code' => $target->personal_code]);
    }

    public function test_search_by_substring_returns_null()
    {
        [$academy, $member] = $this->academyWithMember(['guardians.appoint']);
        User::factory()->create(['username' => 'test_parent_user']);

        $response = $this->actingAs($member, 'api')
            ->getJson("/api/academies/{$academy->id}/guardian-accounts/search?q=test_parent");

        $response->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_search_by_non_member_returns_403()
    {
        $academy = Academy::factory()->create();
        $nonMember = User::factory()->create();

        $this->actingAs($nonMember, 'api')
            ->getJson("/api/academies/{$academy->id}/guardian-accounts/search?q=something")
            ->assertForbidden();
    }

    public function test_student_search_wrong_last_name_returns_null()
    {
        $academy = Academy::factory()->create();
        [$student, $guardian] = $this->createStudentWithGuardian($academy);
        $user = User::factory()->create(); // any logged in user

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/guardian-accounts/student-search?student_code={$student->student_id}&last_name=ผิดนามสกุล");

        $response->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_student_search_exact_match_hides_citizen_id()
    {
        $academy = Academy::factory()->create();
        [$student, $guardian] = $this->createStudentWithGuardian($academy);
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/guardian-accounts/student-search?student_code={$student->student_id}&last_name={$student->last_name_th}");

        $response->assertOk()
            ->assertJsonPath('data.id', $student->id)
            ->assertJsonMissing(['citizen_id' => $student->citizen_id]);
    }

    public function test_student_can_create_request_for_themselves()
    {
        $academy = Academy::factory()->create();
        $studentUser = User::factory()->create();
        [$student, $guardian] = $this->createStudentWithGuardian($academy, $studentUser);
        $parentUser = User::factory()->create();

        $this->actingAs($studentUser, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/guardian-accounts", [
                'user_id' => $parentUser->id,
            ])
            ->assertStatus(201);
    }

    public function test_member_without_permission_cannot_create_request()
    {
        [$academy, $member] = $this->academyWithMember([]);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);
        $parentUser = User::factory()->create();

        $this->actingAs($member, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/guardian-accounts", [
                'user_id' => $parentUser->id,
            ])
            ->assertForbidden();
    }

    public function test_homeroom_teacher_can_create_request()
    {
        [$academy, $teacher] = $this->academyWithMember([]);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);
        $this->setupHomeroomTeacher($academy, $student, $teacher);
        $parentUser = User::factory()->create();

        $this->actingAs($teacher, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/guardian-accounts", [
                'user_id' => $parentUser->id,
            ])
            ->assertStatus(201);
    }

    public function test_member_with_appoint_permission_can_create_request()
    {
        [$academy, $member] = $this->academyWithMember(['guardians.appoint']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);
        $parentUser = User::factory()->create();

        $this->actingAs($member, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/guardian-accounts", [
                'user_id' => $parentUser->id,
            ])
            ->assertStatus(201);
    }

    public function test_duplicate_request_returns_409()
    {
        [$academy, $member] = $this->academyWithMember(['guardians.appoint']);
        [$student, $guardian] = $this->createStudentWithGuardian($academy);
        $parentUser = User::factory()->create();

        app(GuardianAccountLinkService::class)->createRequest($academy, $student, $parentUser, $member);

        $this->actingAs($member, 'api')
            ->postJson("/api/academies/{$academy->id}/students/{$student->id}/guardian-accounts", [
                'user_id' => $parentUser->id,
            ])
            ->assertStatus(409);
    }

    public function test_accept_by_wrong_user_is_forbidden_and_correct_user_succeeds()
    {
        $academy = Academy::factory()->create();
        $studentUser = User::factory()->create();
        [$student, $guardian] = $this->createStudentWithGuardian($academy, $studentUser);
        $parentUser = User::factory()->create();

        // Student initiates, so parent must accept
        $request = app(GuardianAccountLinkService::class)->createRequest($academy, $student, $parentUser, $studentUser, $guardian);

        $otherUser = User::factory()->create();

        $this->actingAs($otherUser, 'api')
            ->postJson("/api/academies/{$academy->id}/guardian-account-requests/{$request->id}/accept")
            ->assertForbidden();

        $this->actingAs($parentUser, 'api')
            ->postJson("/api/academies/{$academy->id}/guardian-account-requests/{$request->id}/accept")
            ->assertOk();

        $this->assertEquals($parentUser->id, $guardian->fresh()->user_id);
    }

    public function test_parent_sees_own_incoming_request()
    {
        $academy = Academy::factory()->create();
        $studentUser = User::factory()->create();
        [$student, $guardian] = $this->createStudentWithGuardian($academy, $studentUser);
        $parentUser = User::factory()->create();

        $myRequest = app(GuardianAccountLinkService::class)->createRequest($academy, $student, $parentUser, $studentUser, $guardian);

        $otherParent = User::factory()->create();
        app(GuardianAccountLinkService::class)->createRequest($academy, $student, $otherParent, $studentUser);

        $response = $this->actingAs($parentUser, 'api')
            ->getJson("/api/academies/{$academy->id}/guardian-account-requests")
            ->assertOk();

        $response->assertJsonCount(1, 'incoming');
        $response->assertJsonPath('incoming.0.id', $myRequest->id);
    }

    public function test_academy_scope_requires_view_permission()
    {
        [$academy, $member] = $this->academyWithMember([]); // no view permission

        $this->actingAs($member, 'api')
            ->getJson("/api/academies/{$academy->id}/guardian-account-requests?scope=academy")
            ->assertForbidden();

        // Upgrade permission
        $memberRec = AcademyMember::where('user_id', $member->id)->first();
        $memberRec->academyRole->update(['permissions' => ['guardians.view']]);

        $this->actingAs($member, 'api')
            ->getJson("/api/academies/{$academy->id}/guardian-account-requests?scope=academy")
            ->assertOk();
    }

    public function test_delete_guardian_account_by_unauthorized_user_returns_403()
    {
        $academy = Academy::factory()->create();
        [$student, $guardian] = $this->createStudentWithGuardian($academy);
        $parentUser = User::factory()->create();
        $guardian->update(['user_id' => $parentUser->id]);

        $otherUser = User::factory()->create();

        $this->actingAs($otherUser, 'api')
            ->deleteJson("/api/academies/{$academy->id}/guardian-people/{$guardian->id}/account")
            ->assertForbidden();

        $this->actingAs($parentUser, 'api')
            ->deleteJson("/api/academies/{$academy->id}/guardian-people/{$guardian->id}/account")
            ->assertOk();
    }
}
