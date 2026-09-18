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

class ClassScheduleBulkTest extends TestCase
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

        $this->ctx = compact('owner', 'academy', 'academicYear', 'semester', 'semester2', 'classroom', 'classroom2', 'course', 'teacher2');

        return $this->ctx;
    }

    public function test_bulk_store_creates_multiple_schedules()
    {
        $ctx = $this->setupData();
        $token = auth()->login($ctx['owner']);

        $schedules = [];
        for ($i = 1; $i <= 5; $i++) {
            $schedules[] = [
                'semester_id' => $ctx['semester']->id,
                'classroom_id' => $ctx['classroom']->id,
                'course_id' => $ctx['course']->id,
                'entry_type' => 'course',
                'teacher_id' => $ctx['owner']->id,
                'day_of_week' => $i,
                'start_time' => '08:00',
                'end_time' => '09:00',
            ];
        }

        $response = $this->actingAs($ctx['owner'], 'api')
            ->postJson("/api/academies/{$ctx['academy']->id}/schedules/bulk", [
                'schedules' => $schedules,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('class_schedules', 5);
        $this->assertDatabaseHas('class_schedules', ['day_of_week' => 5]);
    }

    public function test_title_too_long_returns_422()
    {
        $ctx = $this->setupData();
        $token = auth()->login($ctx['owner']);

        $response = $this->actingAs($ctx['owner'], 'api')
            ->postJson("/api/academies/{$ctx['academy']->id}/schedules/bulk", [
                'schedules' => [[
                    'semester_id' => $ctx['semester']->id,
                    'classroom_id' => $ctx['classroom']->id,
                    'teacher_id' => $ctx['owner']->id,
                    'day_of_week' => 1,
                    'start_time' => '08:00',
                    'end_time' => '09:00',
                    'title' => str_repeat('A', 300),
                ]],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['schedules.0.title']);
    }

    public function test_room_too_long_returns_422()
    {
        $ctx = $this->setupData();
        $token = auth()->login($ctx['owner']);

        $response = $this->actingAs($ctx['owner'], 'api')
            ->postJson("/api/academies/{$ctx['academy']->id}/schedules/bulk", [
                'schedules' => [[
                    'semester_id' => $ctx['semester']->id,
                    'classroom_id' => $ctx['classroom']->id,
                    'teacher_id' => $ctx['owner']->id,
                    'day_of_week' => 1,
                    'start_time' => '08:00',
                    'end_time' => '09:00',
                    'room' => str_repeat('A', 60),
                ]],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['schedules.0.room']);
    }

    public function test_period_number_out_of_range_returns_422()
    {
        $ctx = $this->setupData();
        $token = auth()->login($ctx['owner']);

        $response = $this->actingAs($ctx['owner'], 'api')
            ->postJson("/api/academies/{$ctx['academy']->id}/schedules/bulk", [
                'schedules' => [[
                    'semester_id' => $ctx['semester']->id,
                    'classroom_id' => $ctx['classroom']->id,
                    'teacher_id' => $ctx['owner']->id,
                    'day_of_week' => 1,
                    'start_time' => '08:00',
                    'end_time' => '09:00',
                    'period_number' => 99,
                ]],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['schedules.0.period_number']);
    }

    public function test_too_many_schedules_returns_422()
    {
        $ctx = $this->setupData();
        $token = auth()->login($ctx['owner']);

        $schedules = [];
        for ($i = 0; $i < 301; $i++) {
            $schedules[] = [
                'semester_id' => $ctx['semester']->id,
                'classroom_id' => $ctx['classroom']->id,
                'teacher_id' => $ctx['owner']->id,
                'day_of_week' => 1,
                'start_time' => '08:00',
                'end_time' => '09:00',
            ];
        }

        $response = $this->actingAs($ctx['owner'], 'api')
            ->postJson("/api/academies/{$ctx['academy']->id}/schedules/bulk", [
                'schedules' => $schedules,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['schedules']);
    }

    public function test_audit_log_created_for_bulk()
    {
        $ctx = $this->setupData();
        $token = auth()->login($ctx['owner']);

        $schedules = [];
        for ($i = 1; $i <= 3; $i++) {
            $schedules[] = [
                'semester_id' => $ctx['semester']->id,
                'classroom_id' => $ctx['classroom']->id,
                'course_id' => $ctx['course']->id,
                'entry_type' => 'course',
                'teacher_id' => $ctx['owner']->id,
                'day_of_week' => $i,
                'start_time' => '08:00',
                'end_time' => '09:00',
            ];
        }

        $this->actingAs($ctx['owner'], 'api')
            ->postJson("/api/academies/{$ctx['academy']->id}/schedules/bulk", [
                'schedules' => $schedules,
            ]);

        $this->assertEquals(3, DB::table('audit_logs')->where('module', 'schedules')->count());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'module' => 'schedules',
            'entity_type' => ClassSchedule::class,
        ]);
    }

    public function test_partial_success()
    {
        $ctx = $this->setupData();
        $token = auth()->login($ctx['owner']);

        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id,
            'course_id' => $ctx['course']->id,
            'teacher_id' => $ctx['owner']->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'created_by' => $ctx['owner']->id,
        ]);

        $schedules = [
            [
                'semester_id' => $ctx['semester']->id,
                'classroom_id' => $ctx['classroom']->id,
                'course_id' => $ctx['course']->id,
                'teacher_id' => $ctx['owner']->id,
                'day_of_week' => 1,
                'start_time' => '08:00',
                'end_time' => '09:00',
            ],
            [
                'semester_id' => $ctx['semester']->id,
                'classroom_id' => $ctx['classroom']->id,
                'course_id' => $ctx['course']->id,
                'teacher_id' => $ctx['owner']->id,
                'day_of_week' => 1,
                'start_time' => '09:00',
                'end_time' => '10:00', // Conflict here
            ],
            [
                'semester_id' => $ctx['semester']->id,
                'classroom_id' => $ctx['classroom']->id,
                'course_id' => $ctx['course']->id,
                'teacher_id' => $ctx['owner']->id,
                'day_of_week' => 1,
                'start_time' => '10:00',
                'end_time' => '11:00',
            ],
        ];

        $response = $this->actingAs($ctx['owner'], 'api')
            ->postJson("/api/academies/{$ctx['academy']->id}/schedules/bulk", [
                'schedules' => $schedules,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.created_count', 2)
            ->assertJsonPath('data.errors.0.index', 1);

        $this->assertDatabaseCount('class_schedules', 3); // 1 original + 2 created
    }

    public function test_conflict_within_batch()
    {
        $ctx = $this->setupData();
        $token = auth()->login($ctx['owner']);

        $schedules = [
            [
                'semester_id' => $ctx['semester']->id,
                'classroom_id' => $ctx['classroom']->id,
                'course_id' => $ctx['course']->id,
                'teacher_id' => $ctx['owner']->id,
                'day_of_week' => 1,
                'start_time' => '08:00',
                'end_time' => '09:00',
            ],
            [
                'semester_id' => $ctx['semester']->id,
                'classroom_id' => $ctx['classroom']->id,
                'course_id' => $ctx['course']->id,
                'teacher_id' => $ctx['owner']->id, // Same teacher
                'day_of_week' => 1,
                'start_time' => '08:30', // Conflict with first item in batch
                'end_time' => '09:30',
            ],
        ];

        $response = $this->actingAs($ctx['owner'], 'api')
            ->postJson("/api/academies/{$ctx['academy']->id}/schedules/bulk", [
                'schedules' => $schedules,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.created_count', 1)
            ->assertJsonPath('data.errors.0.index', 1);

        $this->assertDatabaseCount('class_schedules', 1);
    }

    public function test_get_rooms_unique_and_sorted()
    {
        $ctx = $this->setupData();
        $token = auth()->login($ctx['owner']);

        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id,
            'teacher_id' => $ctx['owner']->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => 'ห้อง 2',
            'created_by' => $ctx['owner']->id,
        ]);
        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id,
            'teacher_id' => $ctx['owner']->id,
            'day_of_week' => 2,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => 'ห้อง 1',
            'created_by' => $ctx['owner']->id,
        ]);
        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id,
            'teacher_id' => $ctx['owner']->id,
            'day_of_week' => 3,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => 'ห้อง 1',
            'created_by' => $ctx['owner']->id,
        ]);
        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id,
            'teacher_id' => $ctx['owner']->id,
            'day_of_week' => 4,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => null,
            'created_by' => $ctx['owner']->id,
        ]);

        $response = $this->actingAs($ctx['owner'], 'api')
            ->getJson("/api/academies/{$ctx['academy']->id}/schedules/rooms");

        $response->assertStatus(200)
            ->assertExactJson([
                'success' => true,
                'data' => ['ห้อง 1', 'ห้อง 2'],
            ]);
    }

    public function test_get_rooms_isolated_by_academy()
    {
        $ctx = $this->setupData();
        $token = auth()->login($ctx['owner']);

        $academy2 = Academy::create([
            'user_id' => $ctx['owner']->id,
            'name' => 'school2',
            'display_name' => 'School 2',
        ]);

        ClassSchedule::create([
            'academy_id' => $academy2->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id,
            'teacher_id' => $ctx['owner']->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => 'ห้อง 99',
            'created_by' => $ctx['owner']->id,
        ]);

        $response = $this->actingAs($ctx['owner'], 'api')
            ->getJson("/api/academies/{$ctx['academy']->id}/schedules/rooms");

        $response->assertStatus(200)
            ->assertExactJson([
                'success' => true,
                'data' => [],
            ]);
    }

    public function test_get_rooms_requires_schedule_view()
    {
        $ctx = $this->setupData();

        $randomUser = User::factory()->create([
            'name' => 'Random',
            'username' => 'random',
            'email' => 'rand@x.test',
            'password' => bcrypt('password'),
        ]);

        $token = auth()->login($randomUser);

        $response = $this->actingAs($randomUser, 'api')
            ->getJson("/api/academies/{$ctx['academy']->id}/schedules/rooms");

        $response->assertStatus(403);
    }
}
