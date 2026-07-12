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
 * Regression coverage for getAllStudents().
 *
 * The listing ordered by a correlated-subquery alias (current_student_number) inside
 * ORDER BY, which MySQL rejects with "Unknown column 'current_student_number' in 'order
 * clause'" (SQLSTATE 42S22). This suite asserts the endpoints return 200 and that the
 * student_number ordering (ascending, nulls last) is preserved.
 */
class AcademyStudentListingTest extends TestCase
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
     * Create a student, optionally enrolled in the classroom with a student_number.
     */
    private function makeStudent(string $studentId, string $firstName, ?int $studentNumber): Student
    {
        $student = Student::create([
            'academy_id' => $this->academy->id,
            'student_id' => $studentId,
            'first_name_th' => $firstName,
            'last_name_th' => 'ทดสอบ',
            'class_level' => 'ม.1',
            'class_section' => '1',
            'status' => 'active',
        ]);

        if ($studentNumber !== null) {
            ClassroomStudent::create([
                'academy_id' => $this->academy->id,
                'classroom_id' => $this->classroom->id,
                'student_id' => $student->id,
                'student_number' => $studentNumber,
                'status' => 'active',
            ]);
        }

        return $student;
    }

    /**
     * The academy students index returns 200 and orders by student_number (nulls last).
     */
    public function test_academy_students_index_returns_200_ordered_by_student_number(): void
    {
        $this->actingAs($this->admin, 'api');

        // Insert out of order; expected ascending order is 1, 2, 3, then the null last.
        $this->makeStudent('S003', 'สาม', 3);
        $this->makeStudent('S001', 'หนึ่ง', 1);
        $this->makeStudent('S002', 'สอง', 2);
        $this->makeStudent('S999', 'ไม่มีเลขที่', null);

        $response = $this->getJson("/api/academies/{$this->academy->id}/students");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $numbers = collect($response->json('students'))
            ->pluck('student_number')
            ->all();

        $this->assertSame([1, 2, 3, null], $numbers);
    }

    /**
     * The classrooms/students alias route resolves to the same handler and returns 200.
     */
    public function test_classrooms_students_route_returns_200(): void
    {
        $this->actingAs($this->admin, 'api');

        $this->makeStudent('S002', 'สอง', 2);
        $this->makeStudent('S001', 'หนึ่ง', 1);

        $response = $this->getJson("/api/academies/{$this->academy->id}/classrooms/students");

        $response->assertStatus(200);

        $numbers = collect($response->json('students'))
            ->pluck('student_number')
            ->all();

        $this->assertSame([1, 2], $numbers);
    }
}
