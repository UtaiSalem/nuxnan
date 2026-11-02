<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Student;
use App\Models\User;
use App\Policies\StudentMasterProfilePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentMasterPolicyTest extends TestCase
{
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

    public function test_owner_can_view_their_own_profile()
    {
        $owner = $this->makeUser('owner');
        $academy = Academy::create(['name' => 'A', 'user_id' => $owner->id]);
        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $owner->id,
            'student_id' => 'S1',
            'citizen_id' => '1234567890123',
            'first_name_th' => 'a',
            'last_name_th' => 'b',
        ]);

        $policy = new StudentMasterProfilePolicy;
        $this->assertTrue($policy->view($owner, $student));
    }

    public function test_stranger_cannot_view_or_update()
    {
        $owner = $this->makeUser('o');
        $stranger = $this->makeUser('s');
        $academy = Academy::create(['name' => 'A', 'user_id' => $owner->id]);
        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $owner->id,
            'student_id' => 'S2',
            'citizen_id' => '2234567890123',
            'first_name_th' => 'a',
            'last_name_th' => 'b',
        ]);

        $policy = new StudentMasterProfilePolicy;
        $this->assertFalse($policy->view($stranger, $student));
        $this->assertFalse($policy->update($stranger, $student));
        $this->assertFalse($policy->approveRequests($stranger, $academy->id));
    }

    public function test_admin_can_view_update_approve()
    {
        $owner = $this->makeUser('o');
        $admin = $this->makeUser('adm');
        $academy = Academy::create(['name' => 'A', 'user_id' => $owner->id]);
        AcademyMember::create([
            'user_id' => $admin->id,
            'academy_id' => $academy->id,
            'role' => 'admin',
            'status' => AcademyMember::STATUS_APPROVED,
        ]);
        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $owner->id,
            'student_id' => 'S3',
            'citizen_id' => '3234567890123',
            'first_name_th' => 'a',
            'last_name_th' => 'b',
        ]);

        $policy = new StudentMasterProfilePolicy;
        $this->assertTrue($policy->view($admin, $student));
        $this->assertTrue($policy->update($admin, $student));
        $this->assertTrue($policy->approveRequests($admin, $academy->id));
        $this->assertTrue($policy->viewAny($admin, $academy->id));
    }

    public function test_teacher_can_view_but_not_approve()
    {
        $owner = $this->makeUser('o');
        $teacher = $this->makeUser('t');
        $academy = Academy::create(['name' => 'A', 'user_id' => $owner->id]);
        AcademyMember::create([
            'user_id' => $teacher->id,
            'academy_id' => $academy->id,
            'role' => 'teacher',
            'status' => AcademyMember::STATUS_APPROVED,
        ]);
        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $owner->id,
            'student_id' => 'S4',
            'citizen_id' => '4234567890123',
            'first_name_th' => 'a',
            'last_name_th' => 'b',
        ]);

        $policy = new StudentMasterProfilePolicy;
        $this->assertTrue($policy->view($teacher, $student));
        $this->assertFalse($policy->update($teacher, $student));
        $this->assertFalse($policy->approveRequests($teacher, $academy->id));
    }

    /** custom role ที่ถือ students.manage ทำได้ครบ (G5 align, ST15-S3) */
    public function test_registrar_with_students_manage_can_view_update_approve()
    {
        $owner = $this->makeUser('o');
        $registrar = $this->makeUser('reg');
        $academy = Academy::create(['name' => 'A', 'user_id' => $owner->id]);
        $role = AcademyRole::create([
            'academy_id' => $academy->id,
            'name' => 'registrar-'.uniqid(),
            'display_name_th' => 'นายทะเบียน',
            'permissions' => ['students.manage'],
        ]);
        AcademyMember::create([
            'user_id' => $registrar->id,
            'academy_id' => $academy->id,
            'academy_role_id' => $role->id,
            'status' => 2,
        ]);
        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $owner->id,
            'student_id' => 'S5',
            'citizen_id' => '5234567890123',
            'first_name_th' => 'a',
            'last_name_th' => 'b',
        ]);

        $policy = new StudentMasterProfilePolicy;
        $this->assertTrue($policy->view($registrar, $student));
        $this->assertTrue($policy->update($registrar, $student));
        $this->assertTrue($policy->approveRequests($registrar, $academy->id));
    }

    /** custom role ที่ถือ students.view อย่างเดียว ดูได้ แต่แก้/อนุมัติไม่ได้ (G5 align, ST15-S3) */
    public function test_registrar_with_students_view_can_view_but_not_update_or_approve()
    {
        $owner = $this->makeUser('o');
        $viewer = $this->makeUser('vw');
        $academy = Academy::create(['name' => 'A', 'user_id' => $owner->id]);
        $role = AcademyRole::create([
            'academy_id' => $academy->id,
            'name' => 'viewer-'.uniqid(),
            'display_name_th' => 'ผู้ดูทะเบียน',
            'permissions' => ['students.view'],
        ]);
        AcademyMember::create([
            'user_id' => $viewer->id,
            'academy_id' => $academy->id,
            'academy_role_id' => $role->id,
            'status' => 2,
        ]);
        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $owner->id,
            'student_id' => 'S6',
            'citizen_id' => '6234567890123',
            'first_name_th' => 'a',
            'last_name_th' => 'b',
        ]);

        $policy = new StudentMasterProfilePolicy;
        $this->assertTrue($policy->view($viewer, $student));
        $this->assertFalse($policy->update($viewer, $student));
        $this->assertFalse($policy->approveRequests($viewer, $academy->id));
    }

    /**
     * สถานะที่ไม่ใช่ APPROVED ต้องหมดสิทธิ์ทันที
     *
     * แถวใน academy_members ยังเก็บค่า role ไว้แม้ถูกปฏิเสธหรือพ้นสภาพ ตัวกรอง
     * status จึงเป็นสิ่งเดียวที่กั้นไม่ให้ครูที่พ้นสภาพอ่านทะเบียนนักเรียนทั้งสถาบันได้
     */
    public function test_non_approved_staff_lose_access()
    {
        $statuses = [
            AcademyMember::STATUS_PENDING,
            AcademyMember::STATUS_REJECTED,
            AcademyMember::STATUS_INVITED,
            AcademyMember::STATUS_DISCHARGED,
        ];

        foreach ($statuses as $status) {
            $owner = $this->makeUser('o');
            $teacher = $this->makeUser('t');
            $admin = $this->makeUser('adm');
            $academy = Academy::create(['name' => 'A'.$status, 'user_id' => $owner->id]);

            foreach ([['teacher', $teacher], ['admin', $admin]] as [$role, $member]) {
                AcademyMember::create([
                    'user_id' => $member->id,
                    'academy_id' => $academy->id,
                    'role' => $role,
                    'status' => $status,
                ]);
            }

            $student = Student::create([
                'academy_id' => $academy->id,
                'user_id' => $owner->id,
                'student_id' => 'S'.uniqid(),
                'citizen_id' => (string) random_int(1000000000000, 9999999999999),
                'first_name_th' => 'a',
                'last_name_th' => 'b',
            ]);

            $policy = new StudentMasterProfilePolicy;

            $this->assertFalse($policy->view($teacher, $student), "teacher status $status ยังดูได้");
            $this->assertFalse($policy->viewAny($teacher, $academy->id), "teacher status $status ยัง list ได้");
            $this->assertFalse($policy->update($admin, $student), "admin status $status ยังแก้ได้");
            $this->assertFalse($policy->approveRequests($admin, $academy->id), "admin status $status ยังอนุมัติได้");
        }
    }
}
