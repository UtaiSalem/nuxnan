<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyMember;
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
            'status' => 'active',
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
            'status' => 'active',
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
}
