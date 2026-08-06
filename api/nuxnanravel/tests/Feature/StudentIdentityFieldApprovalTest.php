<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentChangeRequest;
use App\Models\User;
use App\Traits\HandlesStudentUpdates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Identity fields (gender, name, title prefix, date of birth) must not be
 * silently self-editable by the student — they feed student cards, transcripts
 * and the sports-day gender balance.
 */
class StudentIdentityFieldApprovalTest extends TestCase
{
    use HandlesStudentUpdates;
    use RefreshDatabase;

    private function makeUser(string $tag = ''): User
    {
        return User::create([
            'name' => 'U'.$tag,
            'email' => 'u'.$tag.uniqid().'@x.test',
            'password' => bcrypt('x'),
            'username' => 'u'.$tag.uniqid(),
            'reference_code' => 'R'.uniqid(),
            'personal_code' => 'P'.uniqid(),
        ]);
    }

    /**
     * @return array{owner: User, admin: User, academy: Academy, student: Student}
     */
    private function setupStudentAndAcademy(): array
    {
        $owner = $this->makeUser('owner');
        $admin = $this->makeUser('adm');

        $academy = Academy::create([
            'name' => 'TestAcademy_'.uniqid(),
            'user_id' => $admin->id,
        ]);

        // Not mass-assignable — mirrors what the 2026_08_07 migration stores.
        $academy->student_editable_fields = self::defaultEditableFieldSettings();
        $academy->save();

        AcademyMember::create([
            'user_id' => $admin->id,
            'academy_id' => $academy->id,
            'role' => 'admin',
            'status' => AcademyMember::STATUS_APPROVED,
        ]);

        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $owner->id,
            'student_id' => 'S'.uniqid(),
            'citizen_id' => '1234567890123',
            'title_prefix_th' => 'เด็กหญิง',
            'first_name_th' => 'สมชาย',
            'last_name_th' => 'ใจดี',
            'nickname' => 'ชาย',
            'gender' => Student::GENDER_FEMALE,
            'date_of_birth' => '2012-05-04',
        ]);

        return compact('owner', 'admin', 'academy', 'student');
    }

    public function test_student_changing_own_gender_creates_a_change_request(): void
    {
        ['owner' => $owner, 'academy' => $academy, 'student' => $student] = $this->setupStudentAndAcademy();

        $response = $this->actingAs($owner, 'api')
            ->patchJson("/api/academies/{$academy->id}/students/{$student->id}/personal", [
                'gender' => Student::GENDER_MALE,
            ]);

        $response->assertStatus(200);

        $this->assertSame(
            Student::GENDER_FEMALE,
            $student->fresh()->gender,
            'gender must not change until staff approve it'
        );

        $request = StudentChangeRequest::where('status', 'pending')->sole();
        $this->assertSame('gender', $request->field);
        $this->assertEquals(Student::GENDER_MALE, $request->new_value);
    }

    public function test_admin_changing_gender_applies_immediately(): void
    {
        ['admin' => $admin, 'academy' => $academy, 'student' => $student] = $this->setupStudentAndAcademy();

        $response = $this->actingAs($admin, 'api')
            ->patchJson("/api/academies/{$academy->id}/students/{$student->id}/personal", [
                'gender' => Student::GENDER_MALE,
                'title_prefix_th' => 'เด็กชาย',
            ]);

        $response->assertStatus(200);

        $fresh = $student->fresh();
        $this->assertSame(Student::GENDER_MALE, $fresh->gender);
        $this->assertSame('เด็กชาย', $fresh->title_prefix_th);
        $this->assertSame(0, StudentChangeRequest::count());
    }

    public function test_resubmitting_unchanged_identity_fields_creates_no_change_requests(): void
    {
        ['owner' => $owner, 'academy' => $academy, 'student' => $student] = $this->setupStudentAndAcademy();

        // The sectional form posts every field, so untouched blacklisted values
        // arrive alongside the one the student actually edited.
        $response = $this->actingAs($owner, 'api')
            ->patchJson("/api/academies/{$academy->id}/students/{$student->id}/personal", [
                'title_prefix_th' => 'เด็กหญิง',
                'first_name_th' => 'สมชาย',
                'last_name_th' => 'ใจดี',
                'gender' => Student::GENDER_FEMALE,
                'date_of_birth' => '2012-05-04',
                'nickname' => 'ชายน้อย',
            ]);

        $response->assertStatus(200);

        $this->assertSame('ชายน้อย', $student->fresh()->nickname);
        $this->assertSame(0, StudentChangeRequest::count());
    }

    /**
     * @return array{teacher: User, coTeacher: User, outsider: User}
     */
    private function attachClassroomStaff(Academy $academy, Student $student): array
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

        $staff = [];
        foreach ([
            'teacher' => ClassroomMember::ROLE_TEACHER,
            'coTeacher' => ClassroomMember::ROLE_CO_TEACHER,
        ] as $key => $role) {
            $user = $this->makeUser($key);
            AcademyMember::create([
                'user_id' => $user->id,
                'academy_id' => $academy->id,
                'role' => 'teacher',
                'status' => AcademyMember::STATUS_APPROVED,
            ]);
            ClassroomMember::create([
                'classroom_id' => $classroom->id,
                'user_id' => $user->id,
                'role' => $role,
                'is_active' => true,
            ]);
            $staff[$key] = $user;
        }

        // A teacher at the same school but not attached to this classroom.
        $outsider = $this->makeUser('subject');
        AcademyMember::create([
            'user_id' => $outsider->id,
            'academy_id' => $academy->id,
            'role' => 'teacher',
            'status' => AcademyMember::STATUS_APPROVED,
        ]);
        $staff['outsider'] = $outsider;

        return $staff;
    }

    public function test_homeroom_teacher_and_co_teacher_can_edit_their_own_students(): void
    {
        ['academy' => $academy, 'student' => $student] = $this->setupStudentAndAcademy();
        $staff = $this->attachClassroomStaff($academy, $student);

        foreach (['teacher' => 'สมชาย ก', 'coTeacher' => 'สมชาย ข'] as $key => $newName) {
            $this->actingAs($staff[$key], 'api')
                ->getJson("/api/academies/{$academy->id}/students/{$student->id}/profile")
                ->assertStatus(200)
                ->assertJsonPath('data.access_level', 'homeroom');

            $this->actingAs($staff[$key], 'api')
                ->patchJson("/api/academies/{$academy->id}/students/{$student->id}/personal", [
                    'first_name_th' => $newName,
                ])
                ->assertStatus(200);

            $this->assertSame($newName, $student->fresh()->first_name_th);
        }

        // Staff edits apply immediately — no approval queue.
        $this->assertSame(0, StudentChangeRequest::count());
    }

    public function test_teacher_outside_the_classroom_can_read_but_not_edit(): void
    {
        ['academy' => $academy, 'student' => $student] = $this->setupStudentAndAcademy();
        $outsider = $this->attachClassroomStaff($academy, $student)['outsider'];

        $this->actingAs($outsider, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/profile")
            ->assertStatus(200)
            ->assertJsonPath('data.access_level', 'teacher');

        $this->actingAs($outsider, 'api')
            ->patchJson("/api/academies/{$academy->id}/students/{$student->id}/personal", [
                'gender' => Student::GENDER_MALE,
            ])
            ->assertStatus(403);

        $this->assertSame(Student::GENDER_FEMALE, $student->fresh()->gender);
    }

    public function test_academy_owner_without_a_member_row_can_view_and_edit(): void
    {
        ['academy' => $academy, 'student' => $student] = $this->setupStudentAndAcademy();

        // The school owner is Academy::user_id and typically has no
        // academy_members row at all.
        $owner = User::find($academy->user_id);
        AcademyMember::where('academy_id', $academy->id)->delete();

        $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/profile")
            ->assertStatus(200)
            ->assertJsonPath('data.access_level', 'admin');

        $this->actingAs($owner, 'api')
            ->patchJson("/api/academies/{$academy->id}/students/{$student->id}/personal", [
                'gender' => Student::GENDER_MALE,
            ])
            ->assertStatus(200);

        $this->assertSame(Student::GENDER_MALE, $student->fresh()->gender);
        $this->assertSame(0, StudentChangeRequest::count());
    }

    public function test_profile_response_exposes_academy_id_for_the_edit_endpoints(): void
    {
        ['admin' => $admin, 'academy' => $academy, 'student' => $student] = $this->setupStudentAndAcademy();

        $response = $this->actingAs($admin, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/profile");

        $response->assertStatus(200);
        $response->assertJsonPath('data.student.academy_id', $academy->id);
    }
}
