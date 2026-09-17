<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\SchedulePeriod;
use App\Models\SchedulePeriodSet;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * SC-S6a — ชุดโครงคาบเรียน (schedule period sets)
 *
 * เป้าหมายของสเตปนี้: โรงเรียนกำหนดโครงคาบของตัวเองได้ และมีได้ "หลายชุด"
 * (ประถม/มัธยมคนละโครง · วันศุกร์เลิกเร็ว) ซึ่งสคีมาเดิมทำไม่ได้เพราะติด
 * unique(academy_id, period_number)
 */
class SchedulePeriodSetTest extends TestCase
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

        $classroom = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'ม.1/1', 'grade_level' => 'ม.1', 'section' => '1', 'capacity' => 40,
        ]);

        $course = Course::create([
            'academy_id' => $academy->id, 'user_id' => $owner->id, 'instructor_id' => $owner->id,
            'name' => 'คณิตศาสตร์', 'code' => 'MATH101',
        ]);

        $teacher = User::factory()->create([
            'name' => 'Teacher',
            'username' => 'teacher_one',
            'email' => 'teacher@x.test',
            'password' => bcrypt('password'),
        ]);

        DB::table('academy_members')->insert([
            'academy_id' => $academy->id,
            'user_id' => $teacher->id,
            'status' => 2,
            'role' => 'teacher',
            'enrollment_date' => '2026-05-16',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->ctx = compact('owner', 'academy', 'academicYear', 'semester', 'classroom', 'course', 'teacher');

        return $this->ctx;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'โครงปกติ',
            'periods' => [
                ['period_number' => 1, 'name' => 'คาบ 1', 'start_time' => '08:30', 'end_time' => '09:20'],
                ['period_number' => 2, 'name' => 'คาบ 2', 'start_time' => '09:20', 'end_time' => '10:10'],
                ['period_number' => 3, 'name' => 'พักกลางวัน', 'start_time' => '11:50', 'end_time' => '12:40', 'period_type' => 'lunch'],
            ],
        ], $overrides);
    }

    private function createSet(array $overrides = [])
    {
        $c = $this->ctx;

        return $this->actingAs($c['owner'], 'api')
            ->postJson("/api/academies/{$c['academy']->id}/schedule-period-sets", $this->payload($overrides));
    }

    // ---------------------------------------------------------------
    // สร้าง / อ่าน
    // ---------------------------------------------------------------

    public function test_owner_can_create_period_set_with_periods()
    {
        $c = $this->setupData();

        $response = $this->createSet(['days' => [1, 2, 3, 4], 'is_default' => true]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'โครงปกติ');
        $response->assertJsonCount(3, 'data.periods');

        $set = SchedulePeriodSet::first();
        $this->assertSame([1, 2, 3, 4], $set->days);
        $this->assertNull($set->grade_levels);
        $this->assertTrue($set->is_default);
        $this->assertSame($c['academy']->id, $set->academy_id);
        $this->assertSame(3, $set->periods()->count());

        $this->assertDatabaseHas('schedule_periods', [
            'set_id' => $set->id,
            'academy_id' => $c['academy']->id,
            'period_number' => 3,
            'period_type' => 'lunch',
        ]);
    }

    public function test_index_lists_sets_with_their_periods()
    {
        $c = $this->setupData();
        $this->createSet();

        $response = $this->actingAs($c['owner'], 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedule-period-sets");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonCount(3, 'data.0.periods');
        $response->assertJsonPath('data.0.periods.0.period_number', 1);
    }

    public function test_two_sets_can_share_the_same_period_number()
    {
        $this->setupData();

        // นี่คือเหตุผลทั้งหมดของ migration นี้ — สคีมาเดิม unique(academy_id, period_number) ทำแบบนี้ไม่ได้
        $this->createSet(['name' => 'โครง ม.ต้น', 'grade_levels' => ['ม.1', 'ม.2', 'ม.3']])->assertStatus(201);
        $this->createSet(['name' => 'โครง ม.ปลาย', 'grade_levels' => ['ม.4', 'ม.5', 'ม.6']])->assertStatus(201);

        $this->assertSame(2, SchedulePeriodSet::count());
        $this->assertSame(2, SchedulePeriod::where('period_number', 1)->count());
    }

    public function test_second_default_set_clears_the_first_one()
    {
        $this->setupData();

        $this->createSet(['name' => 'ชุดแรก', 'is_default' => true])->assertStatus(201);
        $this->createSet(['name' => 'ชุดสอง', 'is_default' => true])->assertStatus(201);

        $this->assertSame(1, SchedulePeriodSet::where('is_default', true)->count());
        $this->assertSame('ชุดสอง', SchedulePeriodSet::where('is_default', true)->first()->name);
    }

    // ---------------------------------------------------------------
    // การตรวจความถูกต้อง
    // ---------------------------------------------------------------

    public function test_rejects_periods_that_overlap_each_other()
    {
        $this->setupData();

        $response = $this->createSet(['periods' => [
            ['period_number' => 1, 'name' => 'คาบ 1', 'start_time' => '08:30', 'end_time' => '09:30'],
            ['period_number' => 2, 'name' => 'คาบ 2', 'start_time' => '09:00', 'end_time' => '10:00'],
        ]]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['periods']);
        $this->assertSame(0, SchedulePeriodSet::count());
    }

    public function test_adjacent_periods_are_accepted()
    {
        $this->setupData();

        $response = $this->createSet(['periods' => [
            ['period_number' => 1, 'name' => 'คาบ 1', 'start_time' => '08:30', 'end_time' => '09:20'],
            ['period_number' => 2, 'name' => 'คาบ 2', 'start_time' => '09:20', 'end_time' => '10:10'],
        ]]);

        $response->assertStatus(201);
    }

    public function test_rejects_duplicate_period_numbers()
    {
        $this->setupData();

        $response = $this->createSet(['periods' => [
            ['period_number' => 1, 'name' => 'คาบ 1', 'start_time' => '08:30', 'end_time' => '09:20'],
            ['period_number' => 1, 'name' => 'คาบ 1 ซ้ำ', 'start_time' => '09:20', 'end_time' => '10:10'],
        ]]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['periods']);
        $this->assertSame(0, SchedulePeriodSet::count());
    }

    public function test_rejects_inverted_period_time_range()
    {
        $this->setupData();

        $response = $this->createSet(['periods' => [
            ['period_number' => 1, 'name' => 'คาบ 1', 'start_time' => '09:20', 'end_time' => '08:30'],
        ]]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['periods.0.end_time']);
    }

    public function test_rejects_day_outside_one_to_seven()
    {
        $this->setupData();

        $response = $this->createSet(['days' => [1, 9]]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['days.1']);
    }

    public function test_set_without_periods_is_rejected()
    {
        $this->setupData();

        $response = $this->createSet(['periods' => []]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['periods']);
    }

    // ---------------------------------------------------------------
    // สิทธิ์ / ขอบเขตโรงเรียน
    // ---------------------------------------------------------------

    public function test_member_can_read_but_cannot_write()
    {
        $c = $this->setupData();
        $this->createSet();

        $read = $this->actingAs($c['teacher'], 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedule-period-sets");
        $read->assertStatus(200);

        $write = $this->actingAs($c['teacher'], 'api')
            ->postJson("/api/academies/{$c['academy']->id}/schedule-period-sets", $this->payload());
        $write->assertStatus(403);
    }

    public function test_non_member_cannot_read()
    {
        $c = $this->setupData();

        $outsider = User::factory()->create([
            'name' => 'Outsider',
            'username' => 'outsider_user',
            'email' => 'outsider@x.test',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($outsider, 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedule-period-sets")
            ->assertStatus(403);
    }

    public function test_cannot_touch_another_academy_set()
    {
        $c = $this->setupData();
        $this->createSet();
        $set = SchedulePeriodSet::first();

        $otherOwner = User::factory()->create([
            'name' => 'Other',
            'username' => 'other_owner',
            'email' => 'other@x.test',
            'password' => bcrypt('password'),
        ]);
        $otherAcademy = Academy::create([
            'user_id' => $otherOwner->id, 'name' => 'school2', 'display_name' => 'School 2',
        ]);

        $this->actingAs($otherOwner, 'api')
            ->patchJson("/api/academies/{$otherAcademy->id}/schedule-period-sets/{$set->id}", ['name' => 'ยึด'])
            ->assertStatus(404);

        $this->actingAs($otherOwner, 'api')
            ->deleteJson("/api/academies/{$otherAcademy->id}/schedule-period-sets/{$set->id}")
            ->assertStatus(404);

        $this->assertDatabaseHas('schedule_period_sets', ['id' => $set->id, 'name' => 'โครงปกติ']);
        $this->assertSame($c['academy']->id, $set->fresh()->academy_id);
    }

    // ---------------------------------------------------------------
    // แก้ไข / ลบ — id ของคาบต้องนิ่ง และ period_id ที่อ้างอยู่ต้องไม่กลายเป็นขยะ
    // ---------------------------------------------------------------

    public function test_update_keeps_period_ids_when_matched_by_number()
    {
        $c = $this->setupData();
        $this->createSet();
        $set = SchedulePeriodSet::first();
        $firstPeriodId = $set->periods()->where('period_number', 1)->value('id');

        $response = $this->actingAs($c['owner'], 'api')->patchJson(
            "/api/academies/{$c['academy']->id}/schedule-period-sets/{$set->id}",
            ['periods' => [
                ['period_number' => 1, 'name' => 'คาบ 1 (แก้เวลา)', 'start_time' => '08:00', 'end_time' => '08:50'],
                ['period_number' => 2, 'name' => 'คาบ 2', 'start_time' => '09:20', 'end_time' => '10:10'],
                ['period_number' => 3, 'name' => 'พักกลางวัน', 'start_time' => '11:50', 'end_time' => '12:40', 'period_type' => 'lunch'],
            ]]
        );

        $response->assertStatus(200);
        $this->assertSame($firstPeriodId, $set->periods()->where('period_number', 1)->value('id'));
        $this->assertDatabaseHas('schedule_periods', [
            'id' => $firstPeriodId,
            'name' => 'คาบ 1 (แก้เวลา)',
        ]);
    }

    public function test_removing_a_period_clears_schedules_pointing_at_it()
    {
        $c = $this->setupData();
        $this->createSet();
        $set = SchedulePeriodSet::first();
        $doomedId = $set->periods()->where('period_number', 3)->value('id');

        $schedule = ClassSchedule::create([
            'academy_id' => $c['academy']->id,
            'academic_year_id' => $c['academicYear']->id,
            'semester_id' => $c['semester']->id,
            'classroom_id' => $c['classroom']->id,
            'course_id' => $c['course']->id,
            'teacher_id' => $c['owner']->id,
            'period_id' => $doomedId,
            'day_of_week' => 1,
            'start_time' => '11:50',
            'end_time' => '12:40',
            'created_by' => $c['owner']->id,
        ]);

        $response = $this->actingAs($c['owner'], 'api')->patchJson(
            "/api/academies/{$c['academy']->id}/schedule-period-sets/{$set->id}",
            ['periods' => [
                ['period_number' => 1, 'name' => 'คาบ 1', 'start_time' => '08:30', 'end_time' => '09:20'],
                ['period_number' => 2, 'name' => 'คาบ 2', 'start_time' => '09:20', 'end_time' => '10:10'],
            ]]
        );

        $response->assertStatus(200);
        $this->assertDatabaseMissing('schedule_periods', ['id' => $doomedId]);
        // แถวตารางเรียนต้องยังอยู่ (เวลาเก็บไว้ในแถวเองอยู่แล้ว) แต่ต้องไม่ชี้คาบที่ถูกลบ
        $this->assertDatabaseHas('class_schedules', ['id' => $schedule->id, 'period_id' => null]);
        $this->assertSame('11:50', $schedule->fresh()->start_time->format('H:i'));
    }

    public function test_destroy_removes_periods_and_clears_references()
    {
        $c = $this->setupData();
        $this->createSet();
        $set = SchedulePeriodSet::first();
        $periodId = $set->periods()->where('period_number', 1)->value('id');

        $schedule = ClassSchedule::create([
            'academy_id' => $c['academy']->id,
            'academic_year_id' => $c['academicYear']->id,
            'semester_id' => $c['semester']->id,
            'classroom_id' => $c['classroom']->id,
            'course_id' => $c['course']->id,
            'teacher_id' => $c['owner']->id,
            'period_id' => $periodId,
            'day_of_week' => 1,
            'start_time' => '08:30',
            'end_time' => '09:20',
            'created_by' => $c['owner']->id,
        ]);

        $this->actingAs($c['owner'], 'api')
            ->deleteJson("/api/academies/{$c['academy']->id}/schedule-period-sets/{$set->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('schedule_period_sets', ['id' => $set->id]);
        $this->assertSame(0, SchedulePeriod::count());
        $this->assertDatabaseHas('class_schedules', ['id' => $schedule->id, 'period_id' => null]);
    }

    // ---------------------------------------------------------------
    // การเลือกชุดที่ใช้จริง
    // ---------------------------------------------------------------

    public function test_resolve_prefers_grade_specific_set_over_general_one()
    {
        $c = $this->setupData();
        $this->createSet(['name' => 'ทั่วไป', 'is_default' => true]);
        $this->createSet(['name' => 'ม.ปลาย', 'grade_levels' => ['ม.4', 'ม.5', 'ม.6']]);

        $this->assertSame('ม.ปลาย', SchedulePeriodSet::resolveFor($c['academy']->id, 1, 'ม.5')?->name);
        $this->assertSame('ทั่วไป', SchedulePeriodSet::resolveFor($c['academy']->id, 1, 'ม.1')?->name);
    }

    public function test_resolve_respects_day_condition_and_falls_back_to_default()
    {
        $c = $this->setupData();
        $this->createSet(['name' => 'ทั่วไป', 'is_default' => true]);
        $this->createSet(['name' => 'ศุกร์เลิกเร็ว', 'days' => [5]]);

        $this->assertSame('ศุกร์เลิกเร็ว', SchedulePeriodSet::resolveFor($c['academy']->id, 5)?->name);
        $this->assertSame('ทั่วไป', SchedulePeriodSet::resolveFor($c['academy']->id, 2)?->name);
    }

    public function test_resolve_ignores_inactive_sets()
    {
        $c = $this->setupData();
        $this->createSet(['name' => 'ทั่วไป', 'is_default' => true]);
        $this->createSet(['name' => 'ปิดใช้งาน', 'days' => [3], 'is_active' => false]);

        $this->assertSame('ทั่วไป', SchedulePeriodSet::resolveFor($c['academy']->id, 3)?->name);
    }

    public function test_resolve_returns_null_when_academy_has_no_set()
    {
        $c = $this->setupData();

        $this->assertNull(SchedulePeriodSet::resolveFor($c['academy']->id, 1, 'ม.1'));
    }
}
