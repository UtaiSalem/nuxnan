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

/**
 * SC-S5 — ตรรกะกันชนของตารางเรียน
 *
 * G6  ขอบคาบ: คาบที่จบพอดีตอนอีกคาบเริ่ม (08:00–09:00 กับ 09:00–10:00) ต้อง "ไม่ชน"
 * ใหม่ กันสถานที่ (`room`) ถูกจองซ้อนเวลากัน
 * G20 PATCH เคลียร์คอร์สทิ้งโดยไม่มีชื่อแทนไม่ได้
 * G21 PATCH ทำให้ช่วงเวลากลับหัวไม่ได้
 */
class ClassScheduleConflictTest extends TestCase
{
    use RefreshDatabase;

    private array $ctx = [];

    private function setupData(): array
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

        $teacher2 = User::create([
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

    /**
     * สร้างคาบตรง ๆ ในฐานข้อมูล (ข้ามด่าน API) เพื่อวางฉากทดสอบ
     */
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
            'teacher_id' => $c['owner']->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => 'ห้อง 101',
            'status' => ClassSchedule::STATUS_ACTIVE,
            'created_by' => $c['owner']->id,
        ], $overrides));
    }

    private function postSchedule(array $overrides = [])
    {
        $c = $this->ctx;

        $payload = array_merge([
            'semester_id' => $c['semester']->id,
            'classroom_id' => $c['classroom']->id,
            'course_id' => $c['course']->id,
            'teacher_id' => $c['owner']->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'room' => 'ห้อง 101',
        ], $overrides);

        return $this->actingAs($c['owner'], 'api')
            ->postJson("/api/academies/{$c['academy']->id}/schedules", $payload);
    }

    private function patchSchedule(int $id, array $payload)
    {
        $c = $this->ctx;

        return $this->actingAs($c['owner'], 'api')
            ->patchJson("/api/academies/{$c['academy']->id}/schedules/{$id}", $payload);
    }

    // ---------------------------------------------------------------
    // G6 — ขอบคาบ
    // ---------------------------------------------------------------

    public function test_back_to_back_periods_are_allowed()
    {
        $this->setupData();
        $this->makeSchedule(['start_time' => '08:00', 'end_time' => '09:00']);

        // ครูคนเดียวกัน ห้องเรียนเดียวกัน สถานที่เดียวกัน แต่เริ่มตอนคาบเดิมจบพอดี
        $response = $this->postSchedule(['start_time' => '09:00', 'end_time' => '10:00']);

        $response->assertStatus(201);
        $this->assertSame(2, ClassSchedule::count());
    }

    public function test_period_ending_exactly_when_existing_starts_is_allowed()
    {
        $this->setupData();
        $this->makeSchedule(['start_time' => '09:00', 'end_time' => '10:00']);

        $response = $this->postSchedule(['start_time' => '08:00', 'end_time' => '09:00']);

        $response->assertStatus(201);
        $this->assertSame(2, ClassSchedule::count());
    }

    public function test_identical_slot_conflicts_for_teacher()
    {
        $c = $this->setupData();
        $this->makeSchedule(['start_time' => '08:00', 'end_time' => '09:00']);

        // ห้องเรียนคนละห้อง สถานที่คนละที่ แต่ครูคนเดิมและเวลาเดียวกันเป๊ะ
        $response = $this->postSchedule([
            'classroom_id' => $c['classroom2']->id,
            'room' => 'ห้อง 202',
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'ครูผู้สอนมีตารางสอนซ้ำซ้อนในเวลานี้');
        $this->assertSame(1, ClassSchedule::count());
    }

    public function test_partial_overlap_conflicts_for_classroom()
    {
        $c = $this->setupData();
        $this->makeSchedule(['start_time' => '08:00', 'end_time' => '09:00']);

        // ครูคนละคน สถานที่คนละที่ แต่กลุ่มนักเรียนห้องเดิมถูกเรียนคร่อมเวลา
        $response = $this->postSchedule([
            'teacher_id' => $c['teacher2']->id,
            'room' => 'ห้อง 202',
            'start_time' => '08:30',
            'end_time' => '09:30',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'ห้องเรียนมีตารางเรียนซ้ำซ้อนในเวลานี้');
        $this->assertSame(1, ClassSchedule::count());
    }

    public function test_period_containing_existing_one_conflicts()
    {
        $this->setupData();
        $this->makeSchedule(['start_time' => '09:00', 'end_time' => '10:00']);

        $response = $this->postSchedule(['start_time' => '08:00', 'end_time' => '11:00']);

        $response->assertStatus(422);
        $this->assertSame(1, ClassSchedule::count());
    }

    public function test_cross_semester_never_conflicts()
    {
        $c = $this->setupData();
        $this->makeSchedule(['start_time' => '08:00', 'end_time' => '09:00']);

        // ทุกอย่างเหมือนเดิมหมด เปลี่ยนแค่ภาคเรียน
        $response = $this->postSchedule([
            'semester_id' => $c['semester2']->id,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $response->assertStatus(201);
        $this->assertSame(2, ClassSchedule::count());
    }

    /**
     * คาบที่ถูกยกเลิกไม่กันเวลาของคาบใหม่
     *
     * 🔴 เวลาเริ่มของคาบใหม่ต้องไม่ตรงกับคาบที่ยกเลิกเป๊ะ ๆ เพราะ DB มี unique index
     * `unique_teacher_schedule` / `unique_classroom_schedule` = (teacher|classroom, semester, day, start_time)
     * ซึ่งไม่สนใจ `status` ⇒ เวลาเริ่มตรงกันจะโดน DB ปฏิเสธเป็น 500 ก่อนถึงตรรกะนี้ (ดู G22 ใน 11-schedule.md)
     */
    public function test_cancelled_period_does_not_block()
    {
        $this->setupData();
        $this->makeSchedule([
            'start_time' => '08:00',
            'end_time' => '09:00',
            'status' => ClassSchedule::STATUS_CANCELLED,
        ]);

        $response = $this->postSchedule(['start_time' => '08:30', 'end_time' => '09:30']);

        $response->assertStatus(201);
        $this->assertSame(2, ClassSchedule::count());
    }

    // ---------------------------------------------------------------
    // กันสถานที่ชน
    // ---------------------------------------------------------------

    public function test_room_conflict_blocks_overlapping_booking()
    {
        $c = $this->setupData();
        $this->makeSchedule(['start_time' => '08:00', 'end_time' => '09:00', 'room' => 'ห้องปฏิบัติการ 1']);

        // คนละครู คนละกลุ่มนักเรียน แต่สถานที่จริงเดียวกันและเวลาซ้อน
        $response = $this->postSchedule([
            'teacher_id' => $c['teacher2']->id,
            'classroom_id' => $c['classroom2']->id,
            'room' => 'ห้องปฏิบัติการ 1',
            'start_time' => '08:30',
            'end_time' => '09:30',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'สถานที่นี้ถูกใช้ในเวลานี้แล้ว');
        $this->assertSame(1, ClassSchedule::count());
    }

    public function test_same_room_back_to_back_is_allowed()
    {
        $c = $this->setupData();
        $this->makeSchedule(['start_time' => '08:00', 'end_time' => '09:00', 'room' => 'ห้องปฏิบัติการ 1']);

        $response = $this->postSchedule([
            'teacher_id' => $c['teacher2']->id,
            'classroom_id' => $c['classroom2']->id,
            'room' => 'ห้องปฏิบัติการ 1',
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        $response->assertStatus(201);
        $this->assertSame(2, ClassSchedule::count());
    }

    public function test_room_comparison_ignores_extra_whitespace()
    {
        $c = $this->setupData();
        $this->makeSchedule(['start_time' => '08:00', 'end_time' => '09:00', 'room' => 'ห้อง 101']);

        $response = $this->postSchedule([
            'teacher_id' => $c['teacher2']->id,
            'classroom_id' => $c['classroom2']->id,
            'room' => '  ห้อง   101 ',
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'สถานที่นี้ถูกใช้ในเวลานี้แล้ว');
    }

    public function test_room_is_stored_normalized()
    {
        $this->setupData();

        $response = $this->postSchedule(['room' => '  ห้อง   101 ']);

        $response->assertStatus(201);
        $this->assertDatabaseHas('class_schedules', ['room' => 'ห้อง 101']);
    }

    public function test_periods_without_room_never_conflict_on_room()
    {
        $c = $this->setupData();
        $this->makeSchedule(['start_time' => '08:00', 'end_time' => '09:00', 'room' => null]);

        $response = $this->postSchedule([
            'teacher_id' => $c['teacher2']->id,
            'classroom_id' => $c['classroom2']->id,
            'room' => null,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $response->assertStatus(201);
        $this->assertSame(2, ClassSchedule::count());
    }

    // ---------------------------------------------------------------
    // update() — แก้คาบเดิม
    // ---------------------------------------------------------------

    public function test_update_keeping_same_time_is_allowed()
    {
        $this->setupData();
        $schedule = $this->makeSchedule(['start_time' => '08:00', 'end_time' => '09:00']);

        // ส่งเวลาเดิมกลับไป — ต้องไม่ถือว่าชนกับตัวเอง
        $response = $this->patchSchedule($schedule->id, [
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => 'ห้อง 101',
        ]);

        $response->assertStatus(200);
    }

    public function test_update_into_adjacent_slot_is_allowed()
    {
        $this->setupData();
        $first = $this->makeSchedule(['start_time' => '08:00', 'end_time' => '09:00']);
        $second = $this->makeSchedule(['start_time' => '10:00', 'end_time' => '11:00']);

        $response = $this->patchSchedule($second->id, [
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('class_schedules', ['id' => $second->id, 'start_time' => '09:00']);
        $this->assertSame(2, ClassSchedule::count());
        $this->assertNotNull($first->fresh());
    }

    public function test_update_into_overlapping_slot_is_rejected()
    {
        $this->setupData();
        $this->makeSchedule(['start_time' => '08:00', 'end_time' => '09:00']);
        $second = $this->makeSchedule(['start_time' => '10:00', 'end_time' => '11:00']);

        $response = $this->patchSchedule($second->id, [
            'start_time' => '08:30',
            'end_time' => '09:30',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'ครูผู้สอนมีตารางสอนซ้ำซ้อนในเวลานี้');
        $this->assertDatabaseHas('class_schedules', ['id' => $second->id, 'start_time' => '10:00']);
    }

    public function test_update_room_into_busy_place_is_rejected()
    {
        $c = $this->setupData();
        $this->makeSchedule(['start_time' => '08:00', 'end_time' => '09:00', 'room' => 'ห้องปฏิบัติการ 1']);
        $second = $this->makeSchedule([
            'teacher_id' => $c['teacher2']->id,
            'classroom_id' => $c['classroom2']->id,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => 'ห้อง 202',
        ]);

        // ย้ายสถานที่อย่างเดียว (เวลาไม่เปลี่ยน) ไปทับสถานที่ที่ถูกใช้อยู่
        $response = $this->patchSchedule($second->id, ['room' => 'ห้องปฏิบัติการ 1']);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'สถานที่นี้ถูกใช้ในเวลานี้แล้ว');
        $this->assertDatabaseHas('class_schedules', ['id' => $second->id, 'room' => 'ห้อง 202']);
    }

    public function test_update_room_to_free_place_is_allowed()
    {
        $c = $this->setupData();
        $this->makeSchedule(['start_time' => '08:00', 'end_time' => '09:00', 'room' => 'ห้องปฏิบัติการ 1']);
        $second = $this->makeSchedule([
            'teacher_id' => $c['teacher2']->id,
            'classroom_id' => $c['classroom2']->id,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => 'ห้อง 202',
        ]);

        $response = $this->patchSchedule($second->id, ['room' => 'ห้อง 303']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('class_schedules', ['id' => $second->id, 'room' => 'ห้อง 303']);
    }

    // ---------------------------------------------------------------
    // G20 — เคลียร์คอร์สทิ้งโดยไม่มีชื่อแทนไม่ได้
    // ---------------------------------------------------------------

    public function test_update_cannot_clear_course_without_title()
    {
        $c = $this->setupData();
        $schedule = $this->makeSchedule(['course_id' => $c['course']->id, 'title' => null]);

        $response = $this->patchSchedule($schedule->id, ['course_id' => null]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
        $this->assertDatabaseHas('class_schedules', [
            'id' => $schedule->id,
            'course_id' => $c['course']->id,
        ]);
    }

    public function test_update_cannot_blank_out_title_of_course_less_period()
    {
        $this->setupData();
        $schedule = $this->makeSchedule([
            'course_id' => null,
            'title' => 'ชุมนุม',
            'entry_type' => ClassSchedule::ENTRY_TYPE_ACTIVITY,
        ]);

        $response = $this->patchSchedule($schedule->id, ['title' => '   ']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
        $this->assertDatabaseHas('class_schedules', ['id' => $schedule->id, 'title' => 'ชุมนุม']);
    }

    public function test_update_can_clear_course_when_title_is_given()
    {
        $c = $this->setupData();
        $schedule = $this->makeSchedule(['course_id' => $c['course']->id, 'title' => null]);

        $response = $this->patchSchedule($schedule->id, [
            'course_id' => null,
            'title' => 'ชุมนุม',
            'entry_type' => ClassSchedule::ENTRY_TYPE_ACTIVITY,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('class_schedules', [
            'id' => $schedule->id,
            'course_id' => null,
            'title' => 'ชุมนุม',
        ]);
    }

    public function test_update_of_unrelated_field_does_not_trip_the_title_guard()
    {
        $c = $this->setupData();
        $schedule = $this->makeSchedule(['course_id' => $c['course']->id, 'title' => null]);

        $response = $this->patchSchedule($schedule->id, ['notes' => 'หมายเหตุ']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('class_schedules', ['id' => $schedule->id, 'notes' => 'หมายเหตุ']);
    }

    // ---------------------------------------------------------------
    // G21 — ช่วงเวลากลับหัว
    // ---------------------------------------------------------------

    public function test_update_rejects_end_time_before_existing_start_time()
    {
        $this->setupData();
        $schedule = $this->makeSchedule(['start_time' => '08:00', 'end_time' => '09:00']);

        $response = $this->patchSchedule($schedule->id, ['end_time' => '07:00']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_time']);
        $this->assertDatabaseHas('class_schedules', ['id' => $schedule->id, 'end_time' => '09:00']);
    }

    public function test_update_rejects_start_time_after_existing_end_time()
    {
        $this->setupData();
        $schedule = $this->makeSchedule(['start_time' => '08:00', 'end_time' => '09:00']);

        $response = $this->patchSchedule($schedule->id, ['start_time' => '10:00']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_time']);
        $this->assertDatabaseHas('class_schedules', ['id' => $schedule->id, 'start_time' => '08:00']);
    }

    public function test_update_rejects_zero_length_period()
    {
        $this->setupData();
        $schedule = $this->makeSchedule(['start_time' => '08:00', 'end_time' => '09:00']);

        $response = $this->patchSchedule($schedule->id, ['start_time' => '08:00', 'end_time' => '08:00']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_time']);
    }

    // ---------------------------------------------------------------
    // bulkStore + checkAvailability
    // ---------------------------------------------------------------

    public function test_bulk_store_allows_adjacent_rows_and_blocks_room_overlap()
    {
        $c = $this->setupData();

        $base = [
            'semester_id' => $c['semester']->id,
            'classroom_id' => $c['classroom']->id,
            'course_id' => $c['course']->id,
            'teacher_id' => $c['owner']->id,
            'day_of_week' => 1,
            'room' => 'ห้องปฏิบัติการ 1',
        ];

        $response = $this->actingAs($c['owner'], 'api')->postJson(
            "/api/academies/{$c['academy']->id}/schedules/bulk",
            ['schedules' => [
                array_merge($base, ['start_time' => '08:00', 'end_time' => '09:00']),
                array_merge($base, ['start_time' => '09:00', 'end_time' => '10:00']),
                array_merge($base, [
                    'classroom_id' => $c['classroom2']->id,
                    'teacher_id' => $c['teacher2']->id,
                    'start_time' => '09:30',
                    'end_time' => '10:30',
                ]),
            ]]
        );

        $response->assertStatus(201);
        $response->assertJsonPath('data.created_count', 2);
        $response->assertJsonPath('data.error_count', 1);
        $response->assertJsonPath('data.errors.0.index', 2);
        $response->assertJsonPath('data.errors.0.message', 'สถานที่ถูกใช้ในเวลานี้แล้ว');
        $this->assertSame(2, ClassSchedule::count());
    }

    public function test_check_availability_reports_room_state()
    {
        $c = $this->setupData();
        $this->makeSchedule(['start_time' => '08:00', 'end_time' => '09:00', 'room' => 'ห้องปฏิบัติการ 1']);

        $url = "/api/academies/{$c['academy']->id}/schedules/check-availability";

        $busy = $this->actingAs($c['owner'], 'api')->getJson($url.'?'.http_build_query([
            'semester_id' => $c['semester']->id,
            'day_of_week' => 1,
            'start_time' => '08:30',
            'end_time' => '09:30',
            'room' => 'ห้องปฏิบัติการ 1',
        ]));

        $busy->assertStatus(200);
        $busy->assertJsonPath('data.room_available', false);

        $free = $this->actingAs($c['owner'], 'api')->getJson($url.'?'.http_build_query([
            'semester_id' => $c['semester']->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'room' => 'ห้องปฏิบัติการ 1',
        ]));

        $free->assertStatus(200);
        $free->assertJsonPath('data.room_available', true);
    }

    public function test_check_availability_reports_adjacent_teacher_slot_as_free()
    {
        $c = $this->setupData();
        $this->makeSchedule(['start_time' => '08:00', 'end_time' => '09:00']);

        $response = $this->actingAs($c['owner'], 'api')->getJson(
            "/api/academies/{$c['academy']->id}/schedules/check-availability?".http_build_query([
                'semester_id' => $c['semester']->id,
                'day_of_week' => 1,
                'start_time' => '09:00',
                'end_time' => '10:00',
                'teacher_id' => $c['owner']->id,
                'classroom_id' => $c['classroom']->id,
            ])
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.teacher_available', true);
        $response->assertJsonPath('data.classroom_available', true);
    }
}
