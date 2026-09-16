<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SemesterCurrentScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_for_academy_returns_correct_semester_for_different_academies()
    {
        $owner = User::factory()->create();

        $academyA = Academy::create(['name' => 'schoolA', 'user_id' => $owner->id, 'display_name' => 'A']);
        $academyB = Academy::create(['name' => 'schoolB', 'user_id' => $owner->id, 'display_name' => 'B']);

        $yearA = AcademicYear::create(['academy_id' => $academyA->id, 'name' => '2568', 'is_current' => true, 'start_date' => '2025-05-16', 'end_date' => '2026-03-31']);
        $yearB = AcademicYear::create(['academy_id' => $academyB->id, 'name' => '2568', 'is_current' => true, 'start_date' => '2025-05-16', 'end_date' => '2026-03-31']);

        $semesterA = Semester::create(['academic_year_id' => $yearA->id, 'semester_number' => 1, 'is_current' => true, 'name' => 'Sem A', 'start_date' => '2025-05-16', 'end_date' => '2025-10-15']);
        $semesterB = Semester::create(['academic_year_id' => $yearB->id, 'semester_number' => 1, 'is_current' => true, 'name' => 'Sem B', 'start_date' => '2025-05-16', 'end_date' => '2025-10-15']);

        $currentA = Semester::currentForAcademy($academyA->id);
        $currentB = Semester::currentForAcademy($academyB->id);

        $this->assertEquals($semesterA->id, $currentA->id);
        $this->assertEquals($semesterB->id, $currentB->id);
    }

    public function test_current_semester_of_academy_b_does_not_leak_to_academy_a_query()
    {
        $owner = User::factory()->create();

        $userA = User::factory()->create();
        $academyA = Academy::create(['name' => 'schoolA2', 'user_id' => $userA->id, 'display_name' => 'A']);
        $academyB = Academy::create(['name' => 'schoolB2', 'user_id' => $owner->id, 'display_name' => 'B']);

        $yearA = AcademicYear::create(['academy_id' => $academyA->id, 'name' => '2568', 'is_current' => true, 'start_date' => '2025-05-16', 'end_date' => '2026-03-31']);
        $yearB = AcademicYear::create(['academy_id' => $academyB->id, 'name' => '2568', 'is_current' => true, 'start_date' => '2025-05-16', 'end_date' => '2026-03-31']);

        $semesterA = Semester::create(['academic_year_id' => $yearA->id, 'semester_number' => 1, 'is_current' => true, 'name' => 'Sem A', 'start_date' => '2025-05-16', 'end_date' => '2025-10-15']);
        $semesterB = Semester::create(['academic_year_id' => $yearB->id, 'semester_number' => 1, 'is_current' => true, 'name' => 'Sem B', 'start_date' => '2025-05-16', 'end_date' => '2025-10-15']);

        $course = Course::create(['academy_id' => $academyA->id, 'user_id' => $userA->id, 'instructor_id' => $userA->id, 'name' => 'Math', 'code' => 'MATH101']);

        $classroom = Classroom::create([
            'academy_id' => $academyA->id,
            'academic_year_id' => $yearA->id,
            'name' => 'Room 1',
            'grade_level' => '1',
            'section' => 'A',
            'capacity' => 40,
        ]);

        $schedule = ClassSchedule::create([
            'academy_id' => $academyA->id,
            'academic_year_id' => $yearA->id,
            'classroom_id' => $classroom->id,
            'course_id' => $course->id,
            'semester_id' => $semesterA->id,
            'teacher_id' => $userA->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $response = $this->actingAs($userA, 'api')->getJson("/api/academies/{$academyA->id}/schedules");

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $schedule->id]);
    }

    public function test_set_as_current_removes_is_current_from_previous_year_in_same_academy()
    {
        $owner = User::factory()->create();
        $academy = Academy::create(['name' => 'schoolC', 'user_id' => $owner->id, 'display_name' => 'C']);

        $year2568 = AcademicYear::create(['academy_id' => $academy->id, 'name' => '2568', 'is_current' => false, 'start_date' => '2025-05-16', 'end_date' => '2026-03-31']);
        $semester2568 = Semester::create(['academic_year_id' => $year2568->id, 'semester_number' => 1, 'name' => 'Sem', 'is_current' => true, 'start_date' => '2025-05-16', 'end_date' => '2025-10-15']);

        $year2569 = AcademicYear::create(['academy_id' => $academy->id, 'name' => '2569', 'is_current' => true, 'start_date' => '2026-05-16', 'end_date' => '2027-03-31']);
        $semester2569 = Semester::create(['academic_year_id' => $year2569->id, 'semester_number' => 1, 'name' => 'Sem', 'is_current' => false, 'start_date' => '2026-05-16', 'end_date' => '2026-10-15']);

        $semester2569->setAsCurrent();

        $this->assertTrue($semester2569->fresh()->is_current);
        $this->assertFalse($semester2568->fresh()->is_current);
    }

    public function test_set_as_current_does_not_affect_other_academy()
    {
        $owner = User::factory()->create();
        $academyA = Academy::create(['name' => 'schoolA3', 'user_id' => $owner->id, 'display_name' => 'A']);
        $academyB = Academy::create(['name' => 'schoolB3', 'user_id' => $owner->id, 'display_name' => 'B']);

        $yearA = AcademicYear::create(['academy_id' => $academyA->id, 'name' => '2568', 'is_current' => true, 'start_date' => '2025-05-16', 'end_date' => '2026-03-31']);
        $semesterA = Semester::create(['academic_year_id' => $yearA->id, 'semester_number' => 1, 'name' => 'Sem', 'is_current' => true, 'start_date' => '2025-05-16', 'end_date' => '2025-10-15']);

        $yearB = AcademicYear::create(['academy_id' => $academyB->id, 'name' => '2568', 'is_current' => true, 'start_date' => '2025-05-16', 'end_date' => '2026-03-31']);
        $semesterB = Semester::create(['academic_year_id' => $yearB->id, 'semester_number' => 1, 'name' => 'Sem', 'is_current' => false, 'start_date' => '2025-05-16', 'end_date' => '2025-10-15']);

        $semesterB->setAsCurrent();

        $this->assertTrue($semesterB->fresh()->is_current);
        $this->assertTrue($semesterA->fresh()->is_current);
    }

    public function test_fallback_to_lowest_semester_number_if_none_is_current()
    {
        $owner = User::factory()->create();
        $academy = Academy::create(['name' => 'schoolD', 'user_id' => $owner->id, 'display_name' => 'D']);

        $year = AcademicYear::create(['academy_id' => $academy->id, 'name' => '2568', 'is_current' => true, 'start_date' => '2025-05-16', 'end_date' => '2026-03-31']);

        Semester::create(['academic_year_id' => $year->id, 'semester_number' => 2, 'name' => 'Sem 2', 'is_current' => false, 'start_date' => '2025-10-16', 'end_date' => '2026-03-31']);
        $semester1 = Semester::create(['academic_year_id' => $year->id, 'semester_number' => 1, 'name' => 'Sem 1', 'is_current' => false, 'start_date' => '2025-05-16', 'end_date' => '2025-10-15']);

        $current = Semester::currentForAcademy($academy->id);

        $this->assertNotNull($current);
        $this->assertEquals($semester1->id, $current->id);
    }
}
