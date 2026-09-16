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

class ClassScheduleCourseEntryTest extends TestCase
{
    use RefreshDatabase;

    private function setupData()
    {
        $owner = User::create([
            'name' => 'Owner',
            'username' => 'owner_user',
            'email' => 'owner@x.test',
            'password' => bcrypt('password'),
        ]);

        $academy = Academy::create([
            'user_id' => $owner->id,
            'name' => 'school1',
            'display_name' => 'School 1',
            'description' => 'Test',
        ]);

        $academicYear = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2567', 'is_current' => true, 'start_date' => '2026-05-16', 'end_date' => '2027-03-31',
        ]);

        $semester = Semester::create([
            'academic_year_id' => $academicYear->id,
            'semester_number' => 1, 'name' => '1/2567', 'start_date' => '2026-05-16', 'end_date' => '2026-10-31',
            'is_current' => true,
        ]);

        $classroom = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Room 1',
            'grade_level' => '1',
            'section' => 'A',
            'capacity' => 40,
        ]);

        $course = Course::create(['academy_id' => $academy->id, 'user_id' => $owner->id, 'instructor_id' => $owner->id, 'name' => 'Math', 'code' => 'MATH101']);

        return [$owner, $academy, $academicYear, $semester, $classroom, $course];
    }

    public function test_create_with_valid_course()
    {
        [$owner, $academy, $academicYear, $semester, $classroom, $course] = $this->setupData();

        $payload = [
            'semester_id' => $semester->id,
            'classroom_id' => $classroom->id,
            'course_id' => $course->id,
            'teacher_id' => $owner->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ];

        $response = $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/schedules", $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('class_schedules', [
            'academy_id' => $academy->id,
            'course_id' => $course->id,
            'entry_type' => 'course',
        ]);
    }

    public function test_cannot_create_with_other_academy_course()
    {
        [$owner, $academy, $academicYear, $semester, $classroom, $course] = $this->setupData();

        $otherAcademy = Academy::create(['user_id' => $owner->id, 'name' => 'school2', 'display_name' => 'School 2']);
        $otherCourse = Course::create(['academy_id' => $otherAcademy->id, 'user_id' => $owner->id, 'instructor_id' => $owner->id, 'name' => 'English', 'code' => 'ENG101']);

        $payload = [
            'semester_id' => $semester->id,
            'classroom_id' => $classroom->id,
            'course_id' => $otherCourse->id,
            'teacher_id' => $owner->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ];

        $response = $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/schedules", $payload);
        $response->assertStatus(422)->assertJsonValidationErrors(['course_id']);
    }

    public function test_create_activity_without_course()
    {
        [$owner, $academy, $academicYear, $semester, $classroom, $course] = $this->setupData();

        $payload = [
            'semester_id' => $semester->id,
            'classroom_id' => $classroom->id,
            'title' => 'ชุมนุม',
            'entry_type' => 'activity',
            'teacher_id' => $owner->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ];

        $response = $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/schedules", $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('class_schedules', [
            'academy_id' => $academy->id,
            'course_id' => null,
            'title' => 'ชุมนุม',
            'entry_type' => 'activity',
        ]);
    }

    public function test_requires_either_course_or_title()
    {
        [$owner, $academy, $academicYear, $semester, $classroom, $course] = $this->setupData();

        $payload = [
            'semester_id' => $semester->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $owner->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ];

        $response = $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/schedules", $payload);
        $response->assertStatus(422)->assertJsonValidationErrors(['title']);
    }

    public function test_rejects_invalid_entry_type()
    {
        [$owner, $academy, $academicYear, $semester, $classroom, $course] = $this->setupData();

        $payload = [
            'semester_id' => $semester->id,
            'classroom_id' => $classroom->id,
            'title' => 'Lunch',
            'entry_type' => 'lunchx',
            'teacher_id' => $owner->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ];

        $response = $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/schedules", $payload);
        $response->assertStatus(422)->assertJsonValidationErrors(['entry_type']);
    }

    public function test_timetable_format_for_activity()
    {
        [$owner, $academy, $academicYear, $semester, $classroom, $course] = $this->setupData();

        ClassSchedule::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'classroom_id' => $classroom->id,
            'course_id' => null,
            'title' => 'ชุมนุม',
            'entry_type' => 'activity',
            'teacher_id' => $owner->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($owner, 'api')->getJson("/api/academies/{$academy->id}/schedules/timetable?classroom_id={$classroom->id}");

        $response->assertStatus(200);
        $json = $response->json();

        $schedule = $json['data']['timetable'][0]['schedules'][0];
        $this->assertEquals('ชุมนุม', $schedule['title']);
        $this->assertNull($schedule['course']);
        $this->assertEquals('activity', $schedule['entry_type']);
        $this->assertArrayNotHasKey('subject', $schedule);
    }
}
