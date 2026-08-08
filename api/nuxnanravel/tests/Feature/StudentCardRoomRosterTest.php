<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentCardRoomRosterTest extends TestCase
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

    private function setupStudentAndAcademy(): array
    {
        $owner = $this->makeUser('owner');
        $admin = $this->makeUser('adm');

        $academy = Academy::create([
            'name' => 'TestAcademy_'.uniqid(),
            'user_id' => $admin->id,
        ]);

        AcademyMember::create([
            'user_id' => $admin->id,
            'academy_id' => $academy->id,
            'role' => 'admin',
            'status' => 2,
        ]);

        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $owner->id,
            'student_id' => 'STU1001',
            'citizen_id' => '1234567890123',
            'date_of_birth' => '2010-01-15',
            'title_prefix_th' => 'เด็กชาย',
            'first_name_th' => 'สมปอง',
            'last_name_th' => 'ใจดี',
            'status' => 'active',
        ]);

        $studentRole = AcademyRole::create([
            'academy_id' => $academy->id,
            'name' => 'student-local',
            'display_name_th' => 'นักเรียน',
            'permissions' => ['students.view'],
            'is_active' => true,
        ]);

        AcademyMember::create([
            'user_id' => $owner->id,
            'academy_id' => $academy->id,
            'role' => 'student',
            'academy_role_id' => $studentRole->id,
            'status' => 2,
        ]);

        $year = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2569',
            'is_current' => true,
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ]);

        $classroom = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
        ]);

        $enrollment = ClassroomStudent::create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'status' => 'active',
            'student_number' => '1',
        ]);

        return [$owner, $admin, $academy, $student, $enrollment, $year, $classroom];
    }

    public function test_room_endpoint_includes_student_without_any_card()
    {
        [$owner, $admin, $academy, $student, $enrollment, $year, $classroom] = $this->setupStudentAndAcademy();

        // ไม่สร้าง student_cards ให้เลย

        $response = $this->getJson('/api/student-card/1/1');
        $response->assertStatus(200);

        $students = $response->json('students');
        $this->assertCount(1, $students);

        $this->assertNull($students[0]['id']);
        $this->assertFalse($students[0]['has_card']);
        $this->assertSame(1, $students[0]['class_level']);
        $this->assertSame(1, $students[0]['class_section']);
        $this->assertSame('STU1001', $students[0]['student_number']);
    }

    public function test_room_endpoint_includes_student_whose_card_is_expired()
    {
        [$owner, $admin, $academy, $student, $enrollment, $year, $classroom] = $this->setupStudentAndAcademy();

        // สร้าง student_cards ให้ 1 ใบ student_status = 'expired'
        StudentCard::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'student_number' => 'STU1001',
            'national_id' => '1234567890123',
            'full_name_thai' => 'เด็กชาย สมปอง ใจดี',
            'class_level' => '1',
            'class_section' => '1',
            'student_status' => 'expired',
        ]);

        $response = $this->getJson('/api/student-card/1/1');
        $response->assertStatus(200);

        $students = $response->json('students');
        $this->assertCount(1, $students);

        $this->assertFalse($students[0]['has_card']);
        $this->assertNull($students[0]['student_status']);
    }

    public function test_room_endpoint_count_matches_active_enrollments()
    {
        [$owner, $admin, $academy, $student1, $enrollment1, $year, $classroom] = $this->setupStudentAndAcademy();

        // คนที่ 1 มีบัตร active
        StudentCard::create([
            'academy_id' => $academy->id,
            'student_id' => $student1->id,
            'academic_year_id' => $year->id,
            'student_number' => 'STU1001',
            'national_id' => '1234567890123',
            'full_name_thai' => 'เด็กชาย สมปอง ใจดี',
            'class_level' => '1',
            'class_section' => '1',
            'student_status' => 'active',
        ]);

        // สร้างคนที่ 2 ไม่มีบัตร
        $student2 = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $this->makeUser('stu2')->id,
            'student_id' => 'STU1002',
            'first_name_th' => 'คนที่สอง',
            'last_name_th' => 'ใจดี',
            'status' => 'active',
        ]);
        ClassroomStudent::create([
            'student_id' => $student2->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'status' => 'active',
            'student_number' => '2',
        ]);

        // สร้างคนที่ 3 มีบัตร expired
        $student3 = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $this->makeUser('stu3')->id,
            'student_id' => 'STU1003',
            'first_name_th' => 'คนที่สาม',
            'last_name_th' => 'ใจดี',
            'status' => 'active',
        ]);
        ClassroomStudent::create([
            'student_id' => $student3->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'status' => 'active',
            'student_number' => '3',
        ]);
        StudentCard::create([
            'academy_id' => $academy->id,
            'student_id' => $student3->id,
            'academic_year_id' => $year->id,
            'student_number' => 'STU1003',
            'class_level' => '1',
            'class_section' => '1',
            'student_status' => 'expired',
        ]);

        // สร้างคนที่ 4 status = transferred (ต้องไม่นับ)
        $student4 = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $this->makeUser('stu4')->id,
            'student_id' => 'STU1004',
            'first_name_th' => 'คนที่สี่',
            'last_name_th' => 'ใจดี',
            'status' => 'active',
        ]);
        ClassroomStudent::create([
            'student_id' => $student4->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'status' => 'transferred', // Not active
            'student_number' => '4',
        ]);

        $response = $this->getJson('/api/student-card/1/1');
        $response->assertStatus(200);

        $students = $response->json('students');
        $this->assertCount(3, $students);
    }
}
