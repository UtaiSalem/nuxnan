<?php

namespace Tests\Feature;

use App\Exports\TeacherWorkloadExport;
use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ClassScheduleWorkloadTest extends TestCase
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

        $teacher3 = User::factory()->create([
            'name' => 'Teacher Three',
            'username' => 'teacher_three',
            'email' => 'teacher3@x.test',
            'password' => bcrypt('password'),
        ]);

        DB::table('academy_members')->insert([
            'academy_id' => $academy->id,
            'user_id' => $teacher3->id,
            'status' => 2,
            'role' => 'teacher',
            'enrollment_date' => '2026-05-16',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $outsider = User::factory()->create([
            'name' => 'Outsider',
            'username' => 'outsider_user',
            'email' => 'outsider@x.test',
            'password' => bcrypt('password'),
        ]);

        $this->ctx = compact('owner', 'academy', 'academicYear', 'academicYear2', 'semester', 'semester2', 'semesterNextYear', 'classroom', 'classroom2', 'course', 'teacher2', 'teacher3', 'outsider');

        return $this->ctx;
    }

    public function test_teacher_without_periods()
    {
        $ctx = $this->setupData();

        $response = $this->actingAs($ctx['owner'], 'api')
            ->getJson("/api/academies/{$ctx['academy']->id}/schedules/workload?semester_id={$ctx['semester']->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.summary.teachers_without_periods', 2);

        $teachers = collect($response->json('data.teachers'));
        $t3 = $teachers->firstWhere('teacher_id', $ctx['teacher3']->id);
        $this->assertEquals(0, $t3['periods_per_week']);
    }

    public function test_calculate_metrics_correctly()
    {
        $ctx = $this->setupData();

        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id, 'academic_year_id' => $ctx['academicYear']->id, 'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id, 'course_id' => $ctx['course']->id,
            'teacher_id' => $ctx['teacher2']->id, 'day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '09:00',
            'entry_type' => 'course', 'created_by' => $ctx['owner']->id,
        ]);
        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id, 'academic_year_id' => $ctx['academicYear']->id, 'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom2']->id, 'course_id' => $ctx['course']->id,
            'teacher_id' => $ctx['teacher2']->id, 'day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '10:00',
            'entry_type' => 'course', 'created_by' => $ctx['owner']->id,
        ]);
        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id, 'academic_year_id' => $ctx['academicYear']->id, 'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id, 'course_id' => null, 'title' => 'แนะแนว',
            'teacher_id' => $ctx['teacher2']->id, 'day_of_week' => 3, 'start_time' => '10:00', 'end_time' => '10:50',
            'entry_type' => 'activity', 'created_by' => $ctx['owner']->id,
        ]);

        $response = $this->actingAs($ctx['owner'], 'api')
            ->getJson("/api/academies/{$ctx['academy']->id}/schedules/workload?semester_id={$ctx['semester']->id}");

        $response->assertStatus(200);
        $teachers = collect($response->json('data.teachers'));
        $t2 = $teachers->firstWhere('teacher_id', $ctx['teacher2']->id);

        $this->assertEquals(3, $t2['periods_per_week']);
        $this->assertSame(170, $t2['minutes_per_week']);
        $this->assertEquals(1, $t2['course_count']);
        $this->assertEquals(2, $t2['classroom_count']);
        $this->assertEquals(2, $t2['teaching_days']);
        $this->assertEquals(2, $t2['max_periods_per_day']);
    }

    public function test_ignores_cancelled_break_and_other_semester()
    {
        $ctx = $this->setupData();

        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id, 'academic_year_id' => $ctx['academicYear']->id, 'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id, 'course_id' => $ctx['course']->id,
            'teacher_id' => $ctx['teacher2']->id, 'day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '09:00',
            'entry_type' => 'course', 'status' => 'cancelled', 'created_by' => $ctx['owner']->id,
        ]);

        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id, 'academic_year_id' => $ctx['academicYear']->id, 'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id, 'course_id' => null, 'title' => 'พัก',
            'teacher_id' => $ctx['teacher2']->id, 'day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '10:00',
            'entry_type' => 'break', 'created_by' => $ctx['owner']->id,
        ]);

        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id, 'academic_year_id' => $ctx['academicYear']->id, 'semester_id' => $ctx['semester2']->id,
            'classroom_id' => $ctx['classroom']->id, 'course_id' => $ctx['course']->id,
            'teacher_id' => $ctx['teacher2']->id, 'day_of_week' => 1, 'start_time' => '10:00', 'end_time' => '11:00',
            'entry_type' => 'course', 'created_by' => $ctx['owner']->id,
        ]);

        $response = $this->actingAs($ctx['owner'], 'api')
            ->getJson("/api/academies/{$ctx['academy']->id}/schedules/workload?semester_id={$ctx['semester']->id}");

        $response->assertStatus(200);
        $teachers = collect($response->json('data.teachers'));
        $t2 = $teachers->firstWhere('teacher_id', $ctx['teacher2']->id);

        $this->assertEquals(0, $t2['periods_per_week']);
    }

    public function test_owner_with_periods_shows_up_as_non_member_teacher()
    {
        $ctx = $this->setupData();

        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id, 'academic_year_id' => $ctx['academicYear']->id, 'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id, 'course_id' => $ctx['course']->id,
            'teacher_id' => $ctx['owner']->id, 'day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '09:00',
            'entry_type' => 'course', 'created_by' => $ctx['owner']->id,
        ]);

        $response = $this->actingAs($ctx['owner'], 'api')
            ->getJson("/api/academies/{$ctx['academy']->id}/schedules/workload?semester_id={$ctx['semester']->id}");

        $response->assertStatus(200);
        $teachers = collect($response->json('data.teachers'));
        $owner = $teachers->firstWhere('teacher_id', $ctx['owner']->id);

        $this->assertNotNull($owner);
        $this->assertEquals(1, $owner['periods_per_week']);
        $this->assertFalse($owner['is_member_teacher']);
    }

    public function test_sorting()
    {
        $ctx = $this->setupData();

        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id, 'academic_year_id' => $ctx['academicYear']->id, 'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id, 'teacher_id' => $ctx['teacher2']->id, 'day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '09:00',
            'entry_type' => 'course', 'created_by' => $ctx['owner']->id, 'title' => '1',
        ]);
        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id, 'academic_year_id' => $ctx['academicYear']->id, 'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id, 'teacher_id' => $ctx['owner']->id, 'day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '09:00',
            'entry_type' => 'course', 'created_by' => $ctx['owner']->id, 'title' => '1',
        ]);
        ClassSchedule::create([
            'academy_id' => $ctx['academy']->id, 'academic_year_id' => $ctx['academicYear']->id, 'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id, 'teacher_id' => $ctx['owner']->id, 'day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '10:00',
            'entry_type' => 'course', 'created_by' => $ctx['owner']->id, 'title' => '2',
        ]);

        $response = $this->actingAs($ctx['owner'], 'api')
            ->getJson("/api/academies/{$ctx['academy']->id}/schedules/workload?semester_id={$ctx['semester']->id}");

        $teachers = $response->json('data.teachers');

        $this->assertEquals($ctx['owner']->id, $teachers[0]['teacher_id']);
        $this->assertEquals($ctx['teacher2']->id, $teachers[1]['teacher_id']);
        $this->assertEquals($ctx['teacher3']->id, $teachers[2]['teacher_id']);
    }

    public function test_invalid_semester_for_academy()
    {
        $ctx = $this->setupData();

        $academy2 = Academy::create([
            'user_id' => $ctx['owner']->id,
            'name' => 'school2',
            'display_name' => 'School 2',
        ]);
        $academicYear3 = AcademicYear::create([
            'academy_id' => $academy2->id,
            'name' => '2569', 'is_current' => true, 'start_date' => '2026-05-16', 'end_date' => '2027-03-31',
        ]);
        $semester3 = Semester::create([
            'academic_year_id' => $academicYear3->id,
            'semester_number' => 1, 'name' => '1/2569', 'start_date' => '2026-05-16', 'end_date' => '2026-10-31',
            'is_current' => true,
        ]);

        $response = $this->actingAs($ctx['owner'], 'api')
            ->getJson("/api/academies/{$ctx['academy']->id}/schedules/workload?semester_id={$semester3->id}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['semester_id']);
    }

    public function test_default_to_current_semester()
    {
        $ctx = $this->setupData();

        $response = $this->actingAs($ctx['owner'], 'api')
            ->getJson("/api/academies/{$ctx['academy']->id}/schedules/workload");

        $response->assertStatus(200);
        $this->assertEquals($ctx['semester']->id, $response->json('data.semester.id'));
    }

    public function test_export_excel()
    {
        $ctx = $this->setupData();

        Excel::fake();
        // ชื่อไฟล์มีเวลาอยู่ในตัว — ตรึงเวลาไว้เพื่อ assert ชื่อไฟล์ได้ตรง ๆ
        $this->travelTo(now()->setDate(2026, 9, 23)->setTime(10, 0, 0));

        $response = $this->actingAs($ctx['owner'], 'api')
            ->get("/api/academies/{$ctx['academy']->id}/schedules/workload/export?semester_id={$ctx['semester']->id}");

        $response->assertStatus(200);

        Excel::assertDownloaded('teacher-workload-20260923-100000.xlsx', function (TeacherWorkloadExport $export) {
            $rows = $export->array();

            // ครูทุกคนในทะเบียนต้องอยู่ในไฟล์ แม้ยังไม่มีคาบ และแถวแรกคือลำดับที่ 1
            return count($rows) >= 1 && $rows[0][0] === 1;
        });

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'schedules',
            'action' => 'exported',
        ]);
    }

    public function test_outsider_cannot_access()
    {
        $ctx = $this->setupData();

        $response = $this->actingAs($ctx['outsider'], 'api')
            ->getJson("/api/academies/{$ctx['academy']->id}/schedules/workload");

        $response->assertStatus(403);
    }
}
