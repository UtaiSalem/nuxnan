<?php

namespace Tests\Feature;

use App\Exports\ClassScheduleExport;
use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\Semester;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ClassScheduleExportTest extends TestCase
{
    use RefreshDatabase;

    private array $ctx = [];

    private function setupData(): array
    {
        $owner = User::factory()->create([
            'name' => 'Owner',
            'username' => 'owner_user_exp',
            'email' => 'owner_exp@x.test',
            'password' => bcrypt('password'),
        ]);

        $academy = Academy::create([
            'user_id' => $owner->id,
            'name' => 'school1_exp',
            'display_name' => 'School 1 Exp',
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

        $course = Course::create([
            'academy_id' => $academy->id, 'user_id' => $owner->id, 'instructor_id' => $owner->id,
            'name' => 'คณิตศาสตร์', 'code' => 'MATH101',
        ]);

        $this->ctx = compact('owner', 'academy', 'academicYear', 'semester', 'semester2', 'classroom', 'course');

        return $this->ctx;
    }

    public function test_owner_can_export()
    {
        $ctx = $this->setupData();
        auth()->login($ctx['owner']);

        $response = $this->actingAs($ctx['owner'], 'api')
            ->getJson("/api/academies/{$ctx['academy']->id}/schedules/export");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_requires_schedule_view_permission()
    {
        $ctx = $this->setupData();
        $randomUser = User::factory()->create([
            'name' => 'Random',
            'username' => 'random_exp',
            'email' => 'rand_exp@x.test',
            'password' => bcrypt('password'),
        ]);

        auth()->login($randomUser);

        $response = $this->actingAs($randomUser, 'api')
            ->getJson("/api/academies/{$ctx['academy']->id}/schedules/export");

        $response->assertStatus(403);
    }

    public function test_filter_classroom_from_other_academy_returns_422()
    {
        $ctx = $this->setupData();
        auth()->login($ctx['owner']);

        $academy2 = Academy::create([
            'user_id' => $ctx['owner']->id,
            'name' => 'school2_exp',
            'display_name' => 'School 2 Exp',
        ]);
        $classroom2 = Classroom::create([
            'academy_id' => $academy2->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'name' => 'ม.1/2', 'grade_level' => 'ม.1', 'section' => '2', 'capacity' => 40,
        ]);

        $response = $this->actingAs($ctx['owner'], 'api')
            ->getJson("/api/academies/{$ctx['academy']->id}/schedules/export?classroom_id={$classroom2->id}");

        $response->assertStatus(422)->assertJsonValidationErrors(['classroom_id']);
    }

    public function test_day_of_week_9_returns_422()
    {
        $ctx = $this->setupData();
        auth()->login($ctx['owner']);

        $response = $this->actingAs($ctx['owner'], 'api')
            ->getJson("/api/academies/{$ctx['academy']->id}/schedules/export?day_of_week=9");

        $response->assertStatus(422)->assertJsonValidationErrors(['day_of_week']);
    }

    public function test_export_content()
    {
        Excel::fake();

        $ctx = $this->setupData();
        auth()->login($ctx['owner']);

        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id,
            'course_id' => $ctx['course']->id,
            'teacher_id' => $ctx['owner']->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'created_by' => $ctx['owner']->id,
            'entry_type' => 'course',
        ]);

        Carbon::setTestNow('2026-09-19 12:00:00');

        $response = $this->actingAs($ctx['owner'], 'api')
            ->getJson("/api/academies/{$ctx['academy']->id}/schedules/export?semester_id={$ctx['semester']->id}");

        $response->assertStatus(200);

        Excel::assertDownloaded('class-schedules-20260919-120000.xlsx', function (ClassScheduleExport $export) {
            $array = $export->array();

            return count($array) === 1 && $array[0][0] === 'ม.1/1' && $array[0][7] === 'คณิตศาสตร์';
        });
    }

    public function test_default_to_current_semester()
    {
        Excel::fake();

        $ctx = $this->setupData();
        auth()->login($ctx['owner']);

        // 2 in current semester
        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id, // is_current = true
            'classroom_id' => $ctx['classroom']->id,
            'course_id' => $ctx['course']->id,
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
            'classroom_id' => $ctx['classroom']->id,
            'course_id' => $ctx['course']->id,
            'teacher_id' => $ctx['owner']->id,
            'day_of_week' => 2,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'created_by' => $ctx['owner']->id,
        ]);

        // 1 in other semester
        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester2']->id, // is_current = false
            'classroom_id' => $ctx['classroom']->id,
            'course_id' => $ctx['course']->id,
            'teacher_id' => $ctx['owner']->id,
            'day_of_week' => 3,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'created_by' => $ctx['owner']->id,
        ]);

        Carbon::setTestNow('2026-09-19 12:00:00');

        $response = $this->actingAs($ctx['owner'], 'api')
            ->getJson("/api/academies/{$ctx['academy']->id}/schedules/export");

        $response->assertStatus(200);

        Excel::assertDownloaded('class-schedules-20260919-120000.xlsx', function (ClassScheduleExport $export) {
            return count($export->array()) === 2;
        });
    }

    public function test_cancelled_status_not_exported()
    {
        Excel::fake();

        $ctx = $this->setupData();
        auth()->login($ctx['owner']);

        // Active
        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id,
            'course_id' => $ctx['course']->id,
            'teacher_id' => $ctx['owner']->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'created_by' => $ctx['owner']->id,
            'status' => 'active',
        ]);

        // Cancelled
        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id,
            'course_id' => $ctx['course']->id,
            'teacher_id' => $ctx['owner']->id,
            'day_of_week' => 2,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'created_by' => $ctx['owner']->id,
            'status' => 'cancelled',
        ]);

        Carbon::setTestNow('2026-09-19 12:00:00');

        $response = $this->actingAs($ctx['owner'], 'api')
            ->getJson("/api/academies/{$ctx['academy']->id}/schedules/export?semester_id={$ctx['semester']->id}");

        $response->assertStatus(200);

        Excel::assertDownloaded('class-schedules-20260919-120000.xlsx', function (ClassScheduleExport $export) {
            return count($export->array()) === 1;
        });
    }
}
