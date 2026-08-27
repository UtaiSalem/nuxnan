<?php

namespace Tests\Feature;

use App\Exceptions\GuardianAccountLinkException;
use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\ClassroomStudent;
use App\Models\Guardian;
use App\Models\GuardianAccountRequest;
use App\Models\Student;
use App\Models\StudentGuardianLink;
use App\Models\User;
use App\Services\GuardianAccountLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardianAccountLinkServiceTest extends TestCase
{
    use RefreshDatabase;

    private GuardianAccountLinkService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(GuardianAccountLinkService::class);
    }

    private function academyWithMember(array $permissions = [], ?string $roleName = null): array
    {
        $user = User::factory()->create();
        $academy = Academy::factory()->create();
        $role = AcademyRole::create([
            'academy_id' => $academy->id,
            'name' => $roleName ?? 'test-role-'.uniqid(),
            'display_name_th' => 'Test role',
            'permissions' => $permissions,
        ]);
        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $role->id,
            'role' => $roleName ?? 'teacher',
            'status' => AcademyMember::STATUS_APPROVED,
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
            'last_name_th' => 'นักเรียน',
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

    public function test_student_initiates_guardian_accepts()
    {
        $academy = Academy::factory()->create();
        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser);
        $parentUser = User::factory()->create();

        $request = $this->service->createRequest($academy, $student, $parentUser, $studentUser);

        $this->assertEquals(GuardianAccountRequest::DIRECTION_GUARDIAN, $request->direction);
        $this->assertEquals(GuardianAccountRequest::ROLE_STUDENT, $request->initiated_by_role);

        $accepted = $this->service->accept($request, $parentUser)->fresh();

        $this->assertEquals(GuardianAccountRequest::STATUS_ACCEPTED, $accepted->status);
        $guardian = $accepted->guardian;
        $this->assertEquals($parentUser->id, $guardian->user_id);

        $this->assertDatabaseHas('student_guardian_links', [
            'student_id' => $student->id,
            'guardian_id' => $guardian->id,
        ]);

        $this->assertDatabaseHas('academy_members', [
            'academy_id' => $academy->id,
            'user_id' => $parentUser->id,
            'role' => 'parent',
            'status' => 2,
        ]);
    }

    public function test_guardian_initiates_student_accepts()
    {
        $academy = Academy::factory()->create();
        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser);
        $parentUser = User::factory()->create();

        $request = $this->service->createRequest($academy, $student, $parentUser, $parentUser);

        $this->assertEquals(GuardianAccountRequest::DIRECTION_STUDENT, $request->direction);
        $this->assertEquals(GuardianAccountRequest::ROLE_GUARDIAN, $request->initiated_by_role);

        $accepted = $this->service->accept($request, $studentUser);

        $this->assertEquals(GuardianAccountRequest::STATUS_ACCEPTED, $accepted->status);
    }

    public function test_homeroom_teacher_initiates_student_cannot_accept()
    {
        [$academy, $teacher] = $this->academyWithMember(['guardians.appoint'], 'teacher');
        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser);
        $this->setupHomeroomTeacher($academy, $student, $teacher);

        $parentUser = User::factory()->create();

        $request = $this->service->createRequest($academy, $student, $parentUser, $teacher);

        $this->assertEquals(GuardianAccountRequest::DIRECTION_GUARDIAN, $request->direction);

        $this->expectException(GuardianAccountLinkException::class);
        $this->expectExceptionCode(403);
        $this->service->accept($request, $studentUser);
    }

    public function test_wrong_responder_cannot_accept()
    {
        $academy = Academy::factory()->create();
        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser);
        $parentUser = User::factory()->create();

        $request = $this->service->createRequest($academy, $student, $parentUser, $studentUser);

        $otherUser = User::factory()->create();

        $this->expectException(GuardianAccountLinkException::class);
        $this->expectExceptionCode(403);
        $this->service->accept($request, $otherUser);
    }

    public function test_pending_request_prevents_duplicate()
    {
        $academy = Academy::factory()->create();
        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser);
        $parentUser = User::factory()->create();

        $this->service->createRequest($academy, $student, $parentUser, $studentUser);

        $this->expectException(GuardianAccountLinkException::class);
        $this->expectExceptionCode(409);
        $this->service->createRequest($academy, $student, $parentUser, $studentUser);
    }

    public function test_user_cannot_link_second_guardian_in_same_academy()
    {
        $academy = Academy::factory()->create();
        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser);
        $parentUser = User::factory()->create();

        Guardian::create([
            'academy_id' => $academy->id,
            'user_id' => $parentUser->id,
            'first_name' => 'Existing',
            'last_name' => 'Guardian',
            'status' => 'alive',
        ]);

        $otherGuardian = Guardian::create([
            'academy_id' => $academy->id,
            'first_name' => 'Other',
            'last_name' => 'Guardian',
            'status' => 'alive',
        ]);

        $this->expectException(GuardianAccountLinkException::class);
        $this->expectExceptionCode(409);
        $this->service->createRequest($academy, $student, $parentUser, $studentUser, $otherGuardian);
    }

    public function test_existing_member_role_not_changed_on_accept()
    {
        [$academy, $parentUser] = $this->academyWithMember([], 'teacher');
        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser);

        $request = $this->service->createRequest($academy, $student, $parentUser, $studentUser);
        $this->service->accept($request, $parentUser);

        $member = AcademyMember::where('academy_id', $academy->id)->where('user_id', $parentUser->id)->first();
        $this->assertEquals('teacher', $member->role);
    }

    public function test_verified_link_keeps_verified_data()
    {
        $academy = Academy::factory()->create();
        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser);
        $parentUser = User::factory()->create();

        $guardian = Guardian::create([
            'academy_id' => $academy->id,
            'first_name' => 'Guardian',
            'last_name' => 'Parent',
            'status' => 'alive',
        ]);

        $adminUser = User::factory()->create();
        $pastDate = now()->subDays(5);

        StudentGuardianLink::create([
            'student_id' => $student->id,
            'guardian_id' => $guardian->id,
            'verified_at' => $pastDate,
            'verified_by_user_id' => $adminUser->id,
            'appointed_by_role' => 'staff',
        ]);

        $request = $this->service->createRequest($academy, $student, $parentUser, $studentUser, $guardian);
        $this->service->accept($request, $parentUser);

        $link = StudentGuardianLink::where('student_id', $student->id)->where('guardian_id', $guardian->id)->first();
        $this->assertEquals($pastDate->timestamp, $link->verified_at->timestamp);
        $this->assertEquals($adminUser->id, $link->verified_by_user_id);
    }

    public function test_accept_without_guardian_creates_new_row_correctly()
    {
        $academy = Academy::factory()->create();
        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser);
        $parentUser = User::factory()->create([
            'name' => 'นาย สมชาย รักดี มากมาย',
        ]);

        $request = $this->service->createRequest($academy, $student, $parentUser, $studentUser);
        $accepted = $this->service->accept($request, $parentUser)->fresh();

        $guardian = $accepted->guardian()->first();
        $this->assertNotNull($guardian);
        $this->assertEquals('นาย สมชาย รักดี', $guardian->first_name);
        $this->assertEquals('มากมาย', $guardian->last_name);
        $this->assertNull($guardian->citizen_id);
        $this->assertNull($guardian->monthly_income);
    }

    public function test_unlink_sets_user_id_null_but_keeps_rows()
    {
        $academy = Academy::factory()->create();
        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser);
        $parentUser = User::factory()->create();

        $request = $this->service->createRequest($academy, $student, $parentUser, $studentUser);
        $accepted = $this->service->accept($request, $parentUser)->fresh();
        $guardian = $accepted->guardian()->first();

        $this->service->unlink($guardian, $parentUser);

        $this->assertNull($guardian->fresh()->user_id);

        $this->assertTrue(AcademyMember::where('academy_id', $academy->id)->where('user_id', $parentUser->id)->exists());
        $this->assertTrue(StudentGuardianLink::where('student_id', $student->id)->where('guardian_id', $guardian->id)->exists());
    }

    public function test_create_request_student_direction_without_student_user_fails()
    {
        $academy = Academy::factory()->create();
        $student = $this->createStudent($academy);
        $parentUser = User::factory()->create();

        $this->expectException(GuardianAccountLinkException::class);
        $this->expectExceptionCode(422);

        $this->service->createRequest($academy, $student, $parentUser, $parentUser);
    }
}
