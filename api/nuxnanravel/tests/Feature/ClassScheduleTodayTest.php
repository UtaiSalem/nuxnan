<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyRole;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * SC-S9 — การ์ด "ตารางสอนวันนี้" ในแดชบอร์ดครูอ่านจาก endpoint นี้
 *
 * เดิม `today()` คืนโมเดลดิบ ⇒ ไม่มี `display_title` (เป็น accessor ที่ไม่ได้ append)
 * และหลุดคอลัมน์ภายในออกไปด้วย · สเตปนี้ให้คืนรูปเดียวกับ `index()`
 */
class ClassScheduleTodayTest extends TestCase
{
    use RefreshDatabase;

    private array $ctx = [];

    private function todayDow(): int
    {
        return now()->dayOfWeek ?: 7;
    }

    private function setupData(): array
    {
        $owner = User::create([
            'name' => 'Owner', 'username' => 'owner_user',
            'email' => 'owner@x.test', 'password' => bcrypt('password'),
        ]);

        $academy = Academy::create([
            'user_id' => $owner->id, 'name' => 'school1',
            'display_name' => 'School 1', 'description' => 'Test',
        ]);

        $academicYear = AcademicYear::create([
            'academy_id' => $academy->id, 'name' => '2569', 'is_current' => true,
            'start_date' => '2026-05-16', 'end_date' => '2027-03-31',
        ]);

        $semester = Semester::create([
            'academic_year_id' => $academicYear->id, 'semester_number' => 1, 'name' => '1/2569',
            'start_date' => '2026-05-16', 'end_date' => '2026-10-31', 'is_current' => true,
        ]);

        $otherSemester = Semester::create([
            'academic_year_id' => $academicYear->id, 'semester_number' => 2, 'name' => '2/2569',
            'start_date' => '2026-11-01', 'end_date' => '2027-03-31', 'is_current' => false,
        ]);

        $classroom = Classroom::create([
            'academy_id' => $academy->id, 'academic_year_id' => $academicYear->id,
            'name' => 'ม.2/1', 'grade_level' => 'ม.2', 'section' => '1', 'capacity' => 40,
        ]);

        $course = Course::create([
            'academy_id' => $academy->id, 'user_id' => $owner->id, 'instructor_id' => $owner->id,
            'name' => 'คณิตศาสตร์', 'code' => 'MATH101',
        ]);

        $teacher = User::create([
            'name' => 'ครูสมชาย', 'username' => 'teacher_one',
            'email' => 'teacher@x.test', 'password' => bcrypt('password'),
        ]);

        // ต้องมีบทบาทจริงที่ถือ `schedule.view` ด้วย ไม่งั้นด่านของ SC-S7 จะตอบ 403
        $teacherRole = AcademyRole::create([
            'name' => 'teacher',
            'display_name_th' => 'ครู',
            'display_name_en' => 'Teacher',
            'permissions' => ['academy.view', 'schedule.view'],
            'is_system' => true,
            'is_active' => true,
            'sort_order' => 4,
        ]);

        DB::table('academy_members')->insert([
            'academy_id' => $academy->id, 'user_id' => $teacher->id, 'status' => 2,
            'academy_role_id' => $teacherRole->id,
            'role' => 'teacher', 'enrollment_date' => '2026-05-16',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->ctx = compact('owner', 'academy', 'academicYear', 'semester', 'otherSemester', 'classroom', 'course', 'teacher');

        return $this->ctx;
    }

    private function makeSchedule(array $overrides = []): ClassSchedule
    {
        $c = $this->ctx;

        return ClassSchedule::create(array_merge([
            'academy_id' => $c['academy']->id,
            'academic_year_id' => $c['academicYear']->id,
            'semester_id' => $c['semester']->id,
            'classroom_id' => $c['classroom']->id,
            'course_id' => $c['course']->id,
            'entry_type' => ClassSchedule::ENTRY_TYPE_COURSE,
            'teacher_id' => $c['teacher']->id,
            'day_of_week' => $this->todayDow(),
            'start_time' => '08:30',
            'end_time' => '09:20',
            'room' => 'ห้อง 201',
            'status' => ClassSchedule::STATUS_ACTIVE,
            'created_by' => $c['owner']->id,
        ], $overrides));
    }

    public function test_today_returns_the_same_shape_as_index()
    {
        $c = $this->setupData();
        $this->makeSchedule();

        $response = $this->actingAs($c['owner'], 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedules/today");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.schedules');

        $row = $response->json('data.schedules.0');

        // คีย์ที่หน้าเว็บใช้จริงต้องมีครบ
        foreach (['id', 'day_of_week', 'start_time', 'end_time', 'room', 'entry_type', 'title', 'display_title', 'course', 'teacher', 'classroom'] as $key) {
            $this->assertArrayHasKey($key, $row, "ขาดคีย์ {$key}");
        }

        $this->assertSame('08:30', $row['start_time']);
        $this->assertSame('09:20', $row['end_time']);
        $this->assertSame('คณิตศาสตร์', $row['display_title']);   // accessor ที่โมเดลดิบไม่ส่งมา
        $this->assertSame('MATH101', $row['course']['code']);
        $this->assertSame('ครูสมชาย', $row['teacher']['name']);
        $this->assertSame('ม.2/1', $row['classroom']['name']);

        // คอลัมน์ภายในต้องไม่หลุดออกไป
        foreach (['created_by', 'notes', 'created_at', 'updated_at', 'academy_id'] as $leak) {
            $this->assertArrayNotHasKey($leak, $row, "คีย์ภายใน {$leak} หลุดออก response");
        }

        $response->assertJsonPath('data.day_name', ClassSchedule::DAYS[$this->todayDow()]);
    }

    public function test_today_uses_title_when_there_is_no_course()
    {
        $c = $this->setupData();
        $this->makeSchedule([
            'course_id' => null,
            'title' => 'กิจกรรมหน้าเสาธง',
            'entry_type' => ClassSchedule::ENTRY_TYPE_ACTIVITY,
        ]);

        $response = $this->actingAs($c['owner'], 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedules/today");

        $response->assertStatus(200);
        $response->assertJsonPath('data.schedules.0.display_title', 'กิจกรรมหน้าเสาธง');
        $response->assertJsonPath('data.schedules.0.course', null);
    }

    public function test_today_can_be_filtered_to_one_teacher()
    {
        $c = $this->setupData();
        $this->makeSchedule(['teacher_id' => $c['teacher']->id, 'start_time' => '08:30', 'end_time' => '09:20']);
        $this->makeSchedule(['teacher_id' => $c['owner']->id, 'start_time' => '09:20', 'end_time' => '10:10']);

        $all = $this->actingAs($c['owner'], 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedules/today");
        $all->assertJsonCount(2, 'data.schedules');

        $mine = $this->actingAs($c['teacher'], 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedules/today?teacher_id={$c['teacher']->id}");
        $mine->assertStatus(200);
        $mine->assertJsonCount(1, 'data.schedules');
        $mine->assertJsonPath('data.schedules.0.teacher.id', $c['teacher']->id);
    }

    public function test_today_ignores_other_days_other_semesters_and_cancelled_periods()
    {
        $c = $this->setupData();
        $this->makeSchedule();

        $otherDay = $this->todayDow() === 7 ? 1 : $this->todayDow() + 1;
        $this->makeSchedule(['day_of_week' => $otherDay, 'start_time' => '10:10', 'end_time' => '11:00']);
        $this->makeSchedule(['semester_id' => $c['otherSemester']->id, 'start_time' => '11:00', 'end_time' => '11:50']);
        $this->makeSchedule(['status' => ClassSchedule::STATUS_CANCELLED, 'start_time' => '12:40', 'end_time' => '13:30']);

        $response = $this->actingAs($c['owner'], 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedules/today");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.schedules');
        $response->assertJsonPath('data.schedules.0.start_time', '08:30');
    }

    public function test_today_is_ordered_by_start_time()
    {
        $c = $this->setupData();
        $this->makeSchedule(['start_time' => '12:40', 'end_time' => '13:30']);
        $this->makeSchedule(['start_time' => '08:30', 'end_time' => '09:20']);
        $this->makeSchedule(['start_time' => '10:10', 'end_time' => '11:00']);

        $response = $this->actingAs($c['owner'], 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedules/today");

        $this->assertSame(
            ['08:30', '10:10', '12:40'],
            collect($response->json('data.schedules'))->pluck('start_time')->all()
        );
    }
}
