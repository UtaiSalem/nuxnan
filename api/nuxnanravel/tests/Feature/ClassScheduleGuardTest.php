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

class ClassScheduleGuardTest extends TestCase
{
    use RefreshDatabase;

    private function setupData()
    {
        $owner = User::factory()->create([
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

    public function test_owner_can_get_timetable()
    {
        [$owner, $academy, $academicYear, $semester, $classroom, $course] = $this->setupData();

        $response = $this->actingAs($owner, 'api')->getJson("/api/academies/{$academy->id}/schedules/timetable?classroom_id={$classroom->id}");
        $response->assertStatus(200);
    }

    public function test_route_rejects_name()
    {
        [$owner, $academy] = $this->setupData();

        $response = $this->actingAs($owner, 'api')->getJson("/api/academies/{$academy->name}/schedules");
        $response->assertStatus(404);
    }

    public function test_non_member_forbidden()
    {
        [$owner, $academy] = $this->setupData();

        $outsider = User::factory()->create([
            'name' => 'Outsider',
            'username' => 'outsider_'.uniqid(),
            'email' => 'outsider@x.test',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($outsider, 'api')->getJson("/api/academies/{$academy->id}/schedules");
        $response->assertStatus(403);
    }

    public function test_owner_can_create_schedule()
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
            'period_number' => 1,
            'room' => '101',
        ];

        $response = $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/schedules", $payload);
        $response->assertStatus(201);
        $this->assertDatabaseHas('class_schedules', [
            'academy_id' => $academy->id,
            'classroom_id' => $classroom->id,
            'course_id' => $course->id,
            'teacher_id' => $owner->id,
        ]);
    }

    public function test_cannot_use_other_academy_classroom()
    {
        [$owner, $academy, $academicYear, $semester, $classroom, $course] = $this->setupData();

        $otherAcademy = Academy::create(['user_id' => $owner->id, 'name' => 'school2', 'display_name' => 'School 2']);
        $otherAcademicYear = AcademicYear::create(['academy_id' => $otherAcademy->id, 'name' => '2567', 'is_current' => true, 'start_date' => '2026-05-16', 'end_date' => '2027-03-31']);
        $otherClassroom = Classroom::create([
            'academy_id' => $otherAcademy->id,
            'academic_year_id' => $otherAcademicYear->id,
            'name' => 'Room 2',
            'grade_level' => '1',
            'section' => 'A',
        ]);

        $payload = [
            'semester_id' => $semester->id,
            'classroom_id' => $otherClassroom->id,
            'course_id' => $course->id,
            'teacher_id' => $owner->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ];

        $response = $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/schedules", $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['classroom_id']);
    }

    public function test_cannot_use_non_member_teacher()
    {
        [$owner, $academy, $academicYear, $semester, $classroom, $course] = $this->setupData();

        $outsider = User::factory()->create([
            'name' => 'Outsider',
            'username' => 'outsider_'.uniqid(),
            'email' => 'outsider@x.test',
            'password' => bcrypt('password'),
        ]);

        $payload = [
            'semester_id' => $semester->id,
            'classroom_id' => $classroom->id,
            'course_id' => $course->id,
            'teacher_id' => $outsider->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ];

        $response = $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/schedules", $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['teacher_id']);
    }

    public function test_update_can_change_classroom()
    {
        [$owner, $academy, $academicYear, $semester, $classroom, $course] = $this->setupData();

        $schedule = ClassSchedule::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'classroom_id' => $classroom->id,
            'course_id' => $course->id,
            'teacher_id' => $owner->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'created_by' => $owner->id,
        ]);

        $newClassroom = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Room 1 B',
            'grade_level' => '1',
            'section' => 'B',
        ]);

        $response = $this->actingAs($owner, 'api')->patchJson("/api/academies/{$academy->id}/schedules/{$schedule->id}", [
            'classroom_id' => $newClassroom->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('class_schedules', [
            'id' => $schedule->id,
            'classroom_id' => $newClassroom->id,
        ]);
    }

    public function test_cannot_delete_other_academy_schedule()
    {
        [$owner, $academy, $academicYear, $semester, $classroom, $course] = $this->setupData();

        $otherAcademy = Academy::create(['user_id' => $owner->id, 'name' => 'school2', 'display_name' => 'School 2']);
        $otherAcademicYear = AcademicYear::create(['academy_id' => $otherAcademy->id, 'name' => '2567', 'is_current' => true, 'start_date' => '2026-05-16', 'end_date' => '2027-03-31']);
        $otherSemester = Semester::create(['academic_year_id' => $otherAcademicYear->id, 'semester_number' => 1, 'name' => '1/2567', 'start_date' => '2026-05-16', 'end_date' => '2026-10-31', 'is_current' => true]);
        $otherClassroom = Classroom::create([
            'academy_id' => $otherAcademy->id,
            'academic_year_id' => $otherAcademicYear->id,
            'name' => 'Room 2',
            'grade_level' => '1',
            'section' => 'A',
        ]);
        $otherCourse = Course::create(['academy_id' => $otherAcademy->id, 'user_id' => $owner->id, 'instructor_id' => $owner->id, 'name' => 'English', 'code' => 'ENG101']);

        $schedule = ClassSchedule::create([
            'academy_id' => $otherAcademy->id,
            'academic_year_id' => $otherAcademicYear->id,
            'semester_id' => $otherSemester->id,
            'classroom_id' => $otherClassroom->id,
            'course_id' => $otherCourse->id,
            'teacher_id' => $owner->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($owner, 'api')->deleteJson("/api/academies/{$academy->id}/schedules/{$schedule->id}");

        $response->assertStatus(404);
    }
}
