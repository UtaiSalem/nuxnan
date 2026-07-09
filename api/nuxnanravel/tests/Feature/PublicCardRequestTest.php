<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademySetting;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCardRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'student-card.public_requests' => true,
        ]);
    }

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

    private function makeAcademy(bool $requestFlowEnabled = true): array
    {
        $admin = $this->makeUser('adm');

        $academy = Academy::create([
            'name' => 'TestAcademy_'.uniqid(),
            'user_id' => $admin->id,
        ]);

        AcademySetting::create([
            'academy_id' => $academy->id,
            'card_request_flow_enabled' => $requestFlowEnabled,
        ]);

        $year = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2569',
            'is_current' => true,
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ]);

        return [$academy, $year];
    }

    private function makeClassroom(Academy $academy, AcademicYear $year, string $level = 'ม.1', string $section = '1'): Classroom
    {
        return Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'grade_level' => $level,
            'section' => $section,
            'name' => $level.'/'.$section,
        ]);
    }

    private function makeStudent(Academy $academy, string $code = 'STU1001'): Student
    {
        return Student::create([
            'academy_id' => $academy->id,
            'user_id' => $this->makeUser('stu')->id,
            'student_id' => $code,
            'first_name_th' => 'สมปอง',
            'last_name_th' => 'ใจดี'.uniqid(),
            'status' => 'active',
        ]);
    }

    private function enroll(Student $student, Classroom $classroom, int $number = 1): ClassroomStudent
    {
        return ClassroomStudent::create([
            'academy_id' => $classroom->academy_id,
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $classroom->academic_year_id,
            'status' => ClassroomStudent::STATUS_ACTIVE,
            'student_number' => $number,
        ]);
    }

    public function test_public_request_fails_if_global_flag_is_disabled(): void
    {
        config(['student-card.public_requests' => false]);
        [$academy, $year] = $this->makeAcademy(true);
        $classroom = $this->makeClassroom($academy, $year);
        $student = $this->makeStudent($academy);
        $this->enroll($student, $classroom);

        $response = $this->postJson('/api/student-card/1/1/requests', [
            'student_id' => $student->id,
            'request_type' => 'replacement',
        ]);

        $response->assertForbidden();
    }

    public function test_public_request_fails_if_academy_flag_is_disabled(): void
    {
        [$academy, $year] = $this->makeAcademy(false);
        $classroom = $this->makeClassroom($academy, $year);
        $student = $this->makeStudent($academy);
        $this->enroll($student, $classroom);

        $response = $this->postJson('/api/student-card/1/1/requests', [
            'student_id' => $student->id,
            'request_type' => 'replacement',
        ]);

        $response->assertForbidden();
    }

    public function test_public_request_requires_an_active_card_for_replacement(): void
    {
        [$academy, $year] = $this->makeAcademy(true);
        $classroom = $this->makeClassroom($academy, $year);
        $student = $this->makeStudent($academy);
        $this->enroll($student, $classroom);

        // No active student card exists
        $response = $this->postJson('/api/student-card/1/1/requests', [
            'student_id' => $student->id,
            'request_type' => 'replacement',
        ]);

        $response->assertUnprocessable();
    }

    public function test_public_request_submits_successfully(): void
    {
        [$academy, $year] = $this->makeAcademy(true);
        $classroom = $this->makeClassroom($academy, $year);
        $student = $this->makeStudent($academy);
        $this->enroll($student, $classroom);

        // Create an active card
        StudentCard::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'student_number' => $student->student_id,
            'full_name_thai' => 'เด็กชาย สมปอง ใจดี',
            'class_level' => '1',
            'class_section' => '1',
            'student_status' => 'active',
        ]);

        $response = $this->postJson('/api/student-card/1/1/requests', [
            'student_id' => $student->id,
            'request_type' => 'replacement',
            'reason' => 'บัตรชำรุด',
            'requester_name' => 'ครูสมเจต',
            'requester_phone' => '0812345678',
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'status' => 'pending',
            ]);

        $this->assertDatabaseHas('student_card_requests', [
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'origin' => 'public',
            'requested_by' => null,
        ]);
    }

    public function test_public_request_fails_if_first_issue_requested(): void
    {
        [$academy, $year] = $this->makeAcademy(true);
        $classroom = $this->makeClassroom($academy, $year);
        $student = $this->makeStudent($academy);
        $this->enroll($student, $classroom);

        $response = $this->postJson('/api/student-card/1/1/requests', [
            'student_id' => $student->id,
            'request_type' => 'first_issue', // Invalid for public endpoint validation
        ]);

        $response->assertUnprocessable();
    }

    public function test_public_request_prevents_duplicates(): void
    {
        [$academy, $year] = $this->makeAcademy(true);
        $classroom = $this->makeClassroom($academy, $year);
        $student = $this->makeStudent($academy);
        $this->enroll($student, $classroom);

        $card = StudentCard::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'student_number' => $student->student_id,
            'full_name_thai' => 'เด็กชาย สมปอง ใจดี',
            'class_level' => '1',
            'class_section' => '1',
            'student_status' => 'active',
        ]);

        // Submit first request
        $this->postJson('/api/student-card/1/1/requests', [
            'student_id' => $student->id,
            'request_type' => 'replacement',
        ])->assertCreated();

        // Submit duplicate request
        $response = $this->postJson('/api/student-card/1/1/requests', [
            'student_id' => $student->id,
            'request_type' => 'replacement',
        ]);

        $response->assertUnprocessable();
    }
}
