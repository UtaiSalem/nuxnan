<?php

namespace Tests\Feature\Learn\Academy;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for getStudent().
 *
 * The detail endpoint eager-loaded a non-existent `classroom` relation on the Student
 * model (only classrooms/activeClassroom/currentEnrollment exist), so
 * GET /api/academies/{academy}/students/{student} threw "Call to undefined relationship
 * [classroom] on model [App\Models\Student]" (HTTP 500). This suite asserts the endpoint
 * returns 200 and surfaces the active enrollment's classroom + student_number as the flat
 * attributes the gradebook transcript page reads.
 */
class AcademyStudentDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Academy $academy;

    private Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Academy Admin',
            'email' => 'admin@academy.test',
            'password' => bcrypt('password'),
            'username' => 'academy_admin',
        ]);

        $this->academy = Academy::create([
            'name' => 'Test Academy',
            'user_id' => $this->admin->id,
        ]);

        $year = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2569',
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
            'is_current' => true,
        ]);

        $this->classroom = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $year->id,
            'academic_year' => '2569',
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
        ]);
    }

    /**
     * The student detail endpoint returns 200 and exposes the active enrollment's
     * classroom object and student_number the frontend reads.
     */
    public function test_student_detail_returns_200_with_classroom_and_student_number(): void
    {
        $this->actingAs($this->admin, 'api');

        $student = Student::create([
            'academy_id' => $this->academy->id,
            'student_id' => 'S001',
            'first_name_th' => 'หนึ่ง',
            'last_name_th' => 'ทดสอบ',
            'class_level' => 'ม.1',
            'class_section' => '1',
            'status' => 'active',
        ]);

        ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'classroom_id' => $this->classroom->id,
            'student_id' => $student->id,
            'student_number' => 7,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/academies/{$this->academy->id}/students/{$student->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('student.id', $student->id);
        $response->assertJsonPath('student.student_number', 7);
        $response->assertJsonPath('student.classroom.name', 'ม.1/1');
    }

    /**
     * A student with no active enrollment still resolves (200) with null classroom.
     */
    public function test_student_detail_returns_200_without_enrollment(): void
    {
        $this->actingAs($this->admin, 'api');

        $student = Student::create([
            'academy_id' => $this->academy->id,
            'student_id' => 'S002',
            'first_name_th' => 'สอง',
            'last_name_th' => 'ทดสอบ',
            'class_level' => 'ม.1',
            'class_section' => '1',
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/academies/{$this->academy->id}/students/{$student->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('student.classroom', null);
        $response->assertJsonPath('student.student_number', null);
    }
}
