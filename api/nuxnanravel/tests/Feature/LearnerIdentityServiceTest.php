<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\Student;
use App\Models\User;
use App\Services\LearnerIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnerIdentityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LearnerIdentityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LearnerIdentityService;
    }

    public function test_it_resolves_name_from_user_if_course_member_is_empty()
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $course = Course::factory()->create();

        $identity = $this->service->resolve($user, $course);

        $this->assertEquals('John Doe', $identity['member_name']);
        $this->assertEquals('user', $identity['source']);
    }

    public function test_it_prefers_course_member_override()
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $course = Course::factory()->create();

        $member = new CourseMember([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'member_name' => 'Override Name',
        ]);
        $member->save();

        $identity = $this->service->resolve($user, $course);

        $this->assertEquals('Override Name', $identity['member_name']);
        $this->assertEquals('course_override', $identity['source']);
    }

    public function test_it_resolves_member_code_from_academy()
    {
        $admin = User::factory()->create();
        $academy = new Academy(['name' => 'Test Academy', 'slug' => 'test-academy', 'user_id' => $admin->id]);
        $academy->save();

        $user = User::factory()->create();
        $course = Course::factory()->create(['academy_id' => $academy->id]);

        $academyMember = new AcademyMember([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'member_code' => 'ACAD-001',
        ]);
        $academyMember->save();

        $identity = $this->service->resolve($user, $course);

        $this->assertEquals('ACAD-001', $identity['member_code']);
        $this->assertEquals('academy', $identity['source']);
    }

    public function test_it_resolves_order_number_from_classroom()
    {
        $admin = User::factory()->create();
        $academy = new Academy(['name' => 'Test Academy', 'slug' => 'test-academy', 'user_id' => $admin->id]);
        $academy->save();

        $user = User::factory()->create();
        $course = Course::factory()->create(['academy_id' => $academy->id]);

        $academicYear = new AcademicYear([
            'academy_id' => $academy->id,
            'name' => '2569',
            'is_current' => true,
            'start_date' => now(),
            'end_date' => now()->addYear(),
        ]);
        $academicYear->save();

        $student = new Student([
            'user_id' => $user->id,
            'academy_id' => $academy->id,
            'student_id' => 'STU-001',
            'first_name_th' => 'สมชาย',
            'last_name_th' => 'เรียนดี',
            'first_name_en' => 'Somchai',
            'last_name_en' => 'Riandee',
            'gender' => 1,
        ]);
        $student->save();

        $classroom = new Classroom([
            'academy_id' => $academy->id,
            'name' => '6/1',
            'grade_level' => 'ม.6',
            'section' => '1',
        ]);
        $classroom->save();

        $classroomStudent = new ClassroomStudent([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'student_number' => 15,
            'status' => 'active',
        ]);
        $classroomStudent->save();

        $identity = $this->service->resolve($user, $course);

        $this->assertEquals(15, $identity['order_number']);
        $this->assertEquals('classroom', $identity['source']);
    }

    public function test_it_auto_populates_member_fields()
    {
        $admin = User::factory()->create();
        $academy = new Academy(['name' => 'Test Academy', 'slug' => 'test-academy', 'user_id' => $admin->id]);
        $academy->save();

        $user = User::factory()->create(['name' => 'Test User']);
        $course = Course::factory()->create(['academy_id' => $academy->id]);

        $academyMember = new AcademyMember([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'member_code' => 'CODE-123',
        ]);
        $academyMember->save();

        $member = new CourseMember([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);
        $member->setRelation('user', $user);
        $member->setRelation('course', $course);

        $this->service->autoPopulate($member);

        $this->assertEquals('Test User', $member->member_name);
        $this->assertEquals('CODE-123', $member->member_code);
    }
}
