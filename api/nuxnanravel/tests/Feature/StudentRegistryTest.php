<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * เมนู #15 ทะเบียนนักเรียน — ST-S1 (canView รับ students.view OR groups.view)
 * + ST-S5 (ลบถาวรเฉพาะคนไม่มีประวัติ)
 */
class StudentRegistryTest extends TestCase
{
    use RefreshDatabase;

    private Academy $academy;

    private User $owner;

    private AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->makeUser('owner');
        $this->academy = Academy::create([
            'name' => 'RegistryAcademy_'.uniqid(),
            'user_id' => $this->owner->id,
        ]);
        $this->year = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2569',
            'start_date' => '2026-05-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
        ]);
    }

    // ── ST-S1: canView รับทั้งสองคีย์ ───────────────────────────────

    public function test_member_with_students_view_can_list_registry(): void
    {
        $user = $this->makeMember(['students.view']);

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/students")
            ->assertOk();
    }

    public function test_member_with_groups_view_can_list_registry(): void
    {
        $user = $this->makeMember(['groups.view']);

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/students")
            ->assertOk();
    }

    public function test_member_without_view_keys_cannot_list_registry(): void
    {
        $user = $this->makeMember(['members.view']);

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$this->academy->id}/students")
            ->assertStatus(403);
    }

    // ── ST-S5: ลบถาวร ───────────────────────────────────────────────

    public function test_delete_removes_student_without_history(): void
    {
        $manager = $this->makeMember(['students.delete']);
        $student = $this->makeStudent('S-DEL-1');

        $this->actingAs($manager, 'api')
            ->deleteJson("/api/academies/{$this->academy->id}/students/{$student->id}")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }

    public function test_delete_blocked_when_student_has_card(): void
    {
        $manager = $this->makeMember(['students.delete']);
        $student = $this->makeStudent('S-DEL-2');
        StudentCard::create([
            'academy_id' => $this->academy->id,
            'student_id' => $student->id,
            'student_number' => $student->student_id,
            'full_name_thai' => 'นาย ทดสอบ',
            'student_status' => 'active',
        ]);

        $this->actingAs($manager, 'api')
            ->deleteJson("/api/academies/{$this->academy->id}/students/{$student->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }

    public function test_delete_forbidden_without_permission(): void
    {
        $viewer = $this->makeMember(['students.view']);
        $student = $this->makeStudent('S-DEL-3');

        $this->actingAs($viewer, 'api')
            ->deleteJson("/api/academies/{$this->academy->id}/students/{$student->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }

    public function test_owner_can_delete_student_without_history(): void
    {
        $student = $this->makeStudent('S-DEL-4');

        $this->actingAs($this->owner, 'api')
            ->deleteJson("/api/academies/{$this->academy->id}/students/{$student->id}")
            ->assertOk();

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }

    public function test_delete_rejects_student_from_other_academy(): void
    {
        $manager = $this->makeMember(['students.delete']);

        $otherOwner = $this->makeUser('other');
        $otherAcademy = Academy::create(['name' => 'Other_'.uniqid(), 'user_id' => $otherOwner->id]);
        $otherStudent = Student::create([
            'academy_id' => $otherAcademy->id,
            'student_id' => 'S-OTHER',
            'title_prefix_th' => 'นาย',
            'first_name_th' => 'ข้าม',
            'last_name_th' => 'โรงเรียน',
            'status' => 'active',
        ]);

        // path {academy} = โรงเรียนเรา แต่ student เป็นของอีกโรงเรียน → policy/tenant ปฏิเสธ
        $this->actingAs($manager, 'api')
            ->deleteJson("/api/academies/{$this->academy->id}/students/{$otherStudent->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('students', ['id' => $otherStudent->id]);
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function makeUser(string $tag): User
    {
        return User::create([
            'name' => 'U'.$tag,
            'email' => $tag.uniqid().'@x.test',
            'password' => bcrypt('x'),
            'username' => $tag.uniqid(),
            'reference_code' => 'R'.uniqid(),
            'personal_code' => 'P'.uniqid(),
        ]);
    }

    private function makeMember(array $permissions): User
    {
        $user = $this->makeUser('m');
        $role = AcademyRole::create([
            'academy_id' => $this->academy->id,
            'name' => 'role-'.uniqid(),
            'display_name_th' => 'บทบาท',
            'permissions' => $permissions,
            'is_active' => true,
        ]);
        AcademyMember::create([
            'user_id' => $user->id,
            'academy_id' => $this->academy->id,
            'role' => 'teacher',
            'academy_role_id' => $role->id,
            'status' => AcademyMember::STATUS_APPROVED,
        ]);

        return $user;
    }

    private function makeStudent(string $code): Student
    {
        return Student::create([
            'academy_id' => $this->academy->id,
            'student_id' => $code,
            'title_prefix_th' => 'นาย',
            'first_name_th' => 'ทดสอบ',
            'last_name_th' => $code,
            'status' => 'active',
        ]);
    }
}
