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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClassScheduleCopyTest extends TestCase
{
    use RefreshDatabase;

    private array $ctx = [];

    private function setupData(): array
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
            'name' => '2569', 'is_current' => true, 'start_date' => '2026-05-16', 'end_date' => '2027-03-31',
        ]);

        $academicYear2 = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2570', 'is_current' => false, 'start_date' => '2027-05-16', 'end_date' => '2028-03-31',
        ]);

        $semester = Semester::create([
            'academic_year_id' => $academicYear->id,
            'semester_number' => 1, 'name' => '1/2569', 'start_date' => '2026-05-16', 'end_date' => '2026-10-31',
            'is_current' => true,
        ]);

        $semester2 = Semester::create([
            'academic_year_id' => $academicYear->id,
            'semester_number' => 2, 'name' => '2/2569', 'start_date' => '2026-11-01', 'end_date' => '2027-03-31',
            'is_current' => false,
        ]);

        $semesterNextYear = Semester::create([
            'academic_year_id' => $academicYear2->id,
            'semester_number' => 1, 'name' => '1/2570', 'start_date' => '2027-05-16', 'end_date' => '2027-10-31',
            'is_current' => false,
        ]);

        $classroom = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'ม.1/1', 'grade_level' => 'ม.1', 'section' => '1', 'capacity' => 40,
        ]);

        $classroom2 = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'ม.1/2', 'grade_level' => 'ม.1', 'section' => '2', 'capacity' => 40,
        ]);

        $course = Course::create([
            'academy_id' => $academy->id, 'user_id' => $owner->id, 'instructor_id' => $owner->id,
            'name' => 'คณิตศาสตร์', 'code' => 'MATH101',
        ]);

        $teacher2 = User::factory()->create([
            'name' => 'Teacher Two',
            'username' => 'teacher_two',
            'email' => 'teacher2@x.test',
            'password' => bcrypt('password'),
        ]);

        DB::table('academy_members')->insert([
            'academy_id' => $academy->id,
            'user_id' => $teacher2->id,
            'status' => 2,
            'role' => 'teacher',
            'enrollment_date' => '2026-05-16',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->ctx = compact('owner', 'academy', 'academicYear', 'academicYear2', 'semester', 'semester2', 'semesterNextYear', 'classroom', 'classroom2', 'course', 'teacher2');

        return $this->ctx;
    }

    public function test_copy_success_and_preserves_source()
    {
        $ctx = $this->setupData();
        auth()->login($ctx['owner']);

        // Create 3 schedules in semester 1
        for ($i = 1; $i <= 3; $i++) {
            ClassSchedule::create([
                'academy_id' => $ctx['academy']->id,
                'academic_year_id' => $ctx['academicYear']->id,
                'semester_id' => $ctx['semester']->id,
                'classroom_id' => $ctx['classroom']->id,
                'course_id' => $ctx['course']->id,
                'teacher_id' => $ctx['owner']->id,
                'day_of_week' => $i,
                'start_time' => '08:00',
                'end_time' => '09:00',
                'created_by' => $ctx['owner']->id,
            ]);
        }

        $response = $this->actingAs($ctx['owner'], 'api')
            ->postJson("/api/academies/{$ctx['academy']->id}/schedules/copy", [
                'source_semester_id' => $ctx['semester']->id,
                'target_semester_id' => $ctx['semester2']->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.copied_count', 3)
            ->assertJsonPath('data.skipped_count', 0);

        // Assert 6 schedules total
        $this->assertDatabaseCount('class_schedules', 6);

        // Assert target schedules have correct semester and academic_year
        $this->assertEquals(3, ClassSchedule::where('semester_id', $ctx['semester2']->id)
            ->where('academic_year_id', $ctx['academicYear']->id)
            ->count());

        // Assert source schedules are intact
        $this->assertEquals(3, ClassSchedule::where('semester_id', $ctx['semester']->id)->count());

        // Assert audit logs
        $this->assertEquals(3, DB::table('audit_logs')->where('module', 'schedules')->where('action', 'created')->count());
    }

    public function test_merge_with_conflicts_and_adjacent_periods()
    {
        $ctx = $this->setupData();
        auth()->login($ctx['owner']);

        // Target already has a schedule on day 1, 08:00-09:00 (Conflict)
        $existing = ClassSchedule::create([
            'academy_id' => $ctx['academy']->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester2']->id,
            'classroom_id' => $ctx['classroom']->id,
            'teacher_id' => $ctx['owner']->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'created_by' => $ctx['owner']->id,
            'title' => 'Existing',
        ]);

        // Target already has a schedule on day 2, 08:00-09:00
        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester2']->id,
            'classroom_id' => $ctx['classroom']->id,
            'teacher_id' => $ctx['owner']->id,
            'day_of_week' => 2,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'created_by' => $ctx['owner']->id,
        ]);

        // Source has day 1, 08:00-09:00 (Will conflict)
        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id,
            'teacher_id' => $ctx['owner']->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'created_by' => $ctx['owner']->id,
        ]);

        // Source has day 2, 09:00-10:00 (Adjacent to existing, should NOT conflict)
        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id,
            'teacher_id' => $ctx['owner']->id,
            'day_of_week' => 2,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'created_by' => $ctx['owner']->id,
        ]);

        // Source has day 3, 08:00-09:00 (No conflict)
        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id,
            'teacher_id' => $ctx['owner']->id,
            'day_of_week' => 3,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'created_by' => $ctx['owner']->id,
        ]);

        $response = $this->actingAs($ctx['owner'], 'api')
            ->postJson("/api/academies/{$ctx['academy']->id}/schedules/copy", [
                'source_semester_id' => $ctx['semester']->id,
                'target_semester_id' => $ctx['semester2']->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.copied_count', 2)
            ->assertJsonPath('data.skipped_count', 1);

        // Existing target schedule should still be there
        $this->assertDatabaseHas('class_schedules', [
            'id' => $existing->id,
            'title' => 'Existing',
        ]);

        // Verify skipped reason format
        $skipped = $response->json('data.skipped');
        $this->assertEquals('ครูผู้สอนมีตารางสอนซ้ำซ้อน', $skipped[0]['reason']);
        $this->assertEquals('08:00-09:00', $skipped[0]['time']);
    }

    public function test_cross_academic_year_fails()
    {
        $ctx = $this->setupData();
        auth()->login($ctx['owner']);

        $response = $this->actingAs($ctx['owner'], 'api')
            ->postJson("/api/academies/{$ctx['academy']->id}/schedules/copy", [
                'source_semester_id' => $ctx['semester']->id,
                'target_semester_id' => $ctx['semesterNextYear']->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['target_semester_id']);
    }

    public function test_same_semester_fails()
    {
        $ctx = $this->setupData();
        auth()->login($ctx['owner']);

        $response = $this->actingAs($ctx['owner'], 'api')
            ->postJson("/api/academies/{$ctx['academy']->id}/schedules/copy", [
                'source_semester_id' => $ctx['semester']->id,
                'target_semester_id' => $ctx['semester']->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['target_semester_id']);
    }

    public function test_invalid_academy_for_semesters()
    {
        $ctx = $this->setupData();
        auth()->login($ctx['owner']);

        $otherAcademy = Academy::create([
            'user_id' => $ctx['owner']->id,
            'name' => 'school2',
            'display_name' => 'School 2',
        ]);

        $otherYear = AcademicYear::create([
            'academy_id' => $otherAcademy->id,
            'name' => '2569', 'is_current' => true, 'start_date' => '2026-05-16', 'end_date' => '2027-03-31',
        ]);

        $otherSemester = Semester::create([
            'academic_year_id' => $otherYear->id,
            'semester_number' => 1, 'name' => '1', 'start_date' => '2026-05-16', 'end_date' => '2026-10-31',
        ]);

        // Source from other academy
        $response1 = $this->actingAs($ctx['owner'], 'api')
            ->postJson("/api/academies/{$ctx['academy']->id}/schedules/copy", [
                'source_semester_id' => $otherSemester->id,
                'target_semester_id' => $ctx['semester2']->id,
            ]);
        $response1->assertStatus(422)->assertJsonValidationErrors(['source_semester_id']);

        // Target from other academy
        $response2 = $this->actingAs($ctx['owner'], 'api')
            ->postJson("/api/academies/{$ctx['academy']->id}/schedules/copy", [
                'source_semester_id' => $ctx['semester']->id,
                'target_semester_id' => $otherSemester->id,
            ]);
        $response2->assertStatus(422)->assertJsonValidationErrors(['target_semester_id']);
    }

    public function test_source_empty_fails()
    {
        $ctx = $this->setupData();
        auth()->login($ctx['owner']);

        $response = $this->actingAs($ctx['owner'], 'api')
            ->postJson("/api/academies/{$ctx['academy']->id}/schedules/copy", [
                'source_semester_id' => $ctx['semester']->id,
                'target_semester_id' => $ctx['semester2']->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['source_semester_id']);
    }

    public function test_filter_by_classroom_ids()
    {
        $ctx = $this->setupData();
        auth()->login($ctx['owner']);

        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id,
            'teacher_id' => $ctx['owner']->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'created_by' => $ctx['owner']->id,
        ]);

        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom2']->id,
            'teacher_id' => $ctx['owner']->id,
            'day_of_week' => 2,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'created_by' => $ctx['owner']->id,
        ]);

        $response = $this->actingAs($ctx['owner'], 'api')
            ->postJson("/api/academies/{$ctx['academy']->id}/schedules/copy", [
                'source_semester_id' => $ctx['semester']->id,
                'target_semester_id' => $ctx['semester2']->id,
                'classroom_ids' => [$ctx['classroom']->id],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.copied_count', 1);

        $this->assertDatabaseHas('class_schedules', [
            'semester_id' => $ctx['semester2']->id,
            'classroom_id' => $ctx['classroom']->id,
        ]);
        $this->assertDatabaseMissing('class_schedules', [
            'semester_id' => $ctx['semester2']->id,
            'classroom_id' => $ctx['classroom2']->id,
        ]);
    }

    public function test_cancelled_status_not_copied()
    {
        $ctx = $this->setupData();
        auth()->login($ctx['owner']);

        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id,
            'teacher_id' => $ctx['owner']->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'created_by' => $ctx['owner']->id,
            'status' => 'cancelled',
        ]);

        $response = $this->actingAs($ctx['owner'], 'api')
            ->postJson("/api/academies/{$ctx['academy']->id}/schedules/copy", [
                'source_semester_id' => $ctx['semester']->id,
                'target_semester_id' => $ctx['semester2']->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['source_semester_id']); // Because 0 active schedules
    }

    public function test_requires_permission()
    {
        $ctx = $this->setupData();
        $user = User::factory()->create();

        // Give them a role without schedule.manage
        DB::table('academy_members')->insert([
            'academy_id' => $ctx['academy']->id,
            'user_id' => $user->id,
            'status' => 2,
            'role' => 'student',
            'enrollment_date' => '2026-05-16',
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$ctx['academy']->id}/schedules/copy", [
                'source_semester_id' => $ctx['semester']->id,
                'target_semester_id' => $ctx['semester2']->id,
            ]);

        $response->assertStatus(403);
    }
}
