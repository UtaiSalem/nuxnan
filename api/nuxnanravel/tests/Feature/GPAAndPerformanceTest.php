<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Course;
use App\Models\CourseGrade;
use App\Models\Semester;
use App\Models\SemesterTranscript;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GPAAndPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_gpa_calculation()
    {
        // Use direct creation instead of factories since they don't exist
        $user = User::factory()->create();

        $academy = Academy::create([
            'user_id' => $user->id,
            'name' => 'Test Academy',
            'slug' => 'test-academy',
        ]);

        $year = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2569',
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ]);

        $semester = Semester::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'semester_number' => 1,
            'name' => 'ภาคเรียนที่ 1/2569',
            'start_date' => '2026-05-16',
            'end_date' => '2026-10-10',
        ]);

        $studentUser = User::factory()->create();
        $student = Student::create([
            'user_id' => $studentUser->id,
            'academy_id' => $academy->id,
            'first_name_th' => 'สมชาย',
            'last_name_th' => 'สายเสมอ',
            'student_id' => 'ST001',
        ]);

        $course1 = Course::create([
            'user_id' => $user->id,
            'instructor_id' => $user->id,
            'academy_id' => $academy->id,
            'name' => 'Course 1',
            'slug' => 'course-1',
            'credit_units' => 3,
        ]);

        $course2 = Course::create([
            'user_id' => $user->id,
            'instructor_id' => $user->id,
            'academy_id' => $academy->id,
            'name' => 'Course 2',
            'slug' => 'course-2',
            'credit_units' => 2,
        ]);

        // Grade A (4.0) for 3 credits
        CourseGrade::create([
            'student_id' => $student->id,
            'course_id' => $course1->id,
            'semester_id' => $semester->id,
            'grade_points' => 4.0,
            'letter_grade' => 'A',
            'percentage' => 85,
            'status' => 'completed',
        ]);

        // Grade C (2.0) for 2 credits
        CourseGrade::create([
            'student_id' => $student->id,
            'course_id' => $course2->id,
            'semester_id' => $semester->id,
            'grade_points' => 2.0,
            'letter_grade' => 'C',
            'percentage' => 65,
            'status' => 'completed',
        ]);

        $transcript = SemesterTranscript::create([
            'student_id' => $student->id,
            'academy_id' => $academy->id,
            'semester_id' => $semester->id,
            'status' => 'published',
        ]);

        $transcript->calculate();

        // (4.0 * 3 + 2.0 * 2) / (3 + 2) = (12 + 4) / 5 = 16 / 5 = 3.2
        $this->assertEquals(3.2, (float) $transcript->fresh()->gpa);
        $this->assertEquals(5, (float) $transcript->fresh()->total_credits);
        $this->assertEquals(5, (float) $transcript->fresh()->earned_credits);
    }
}
