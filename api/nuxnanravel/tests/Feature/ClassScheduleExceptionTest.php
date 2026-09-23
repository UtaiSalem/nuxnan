<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\ClassScheduleException;
use App\Models\Course;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClassScheduleExceptionTest extends TestCase
{
    use RefreshDatabase;

    private array $ctx = [];

    private function setupData(): array
    {
        $owner = User::factory()->create([
            'name' => 'Owner',
            'username' => 'owner_user_exc',
            'email' => 'owner_exc@x.test',
            'password' => bcrypt('password'),
        ]);

        $academy = Academy::create([
            'user_id' => $owner->id,
            'name' => 'school1_exc',
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

        $classroom2 = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'ม.1/2', 'grade_level' => 'ม.1', 'section' => '2', 'capacity' => 40,
        ]);

        $course = Course::create([
            'academy_id' => $academy->id, 'user_id' => $owner->id, 'instructor_id' => $owner->id,
            'name' => 'Math', 'code' => 'MATH101',
        ]);

        $teacher2 = User::factory()->create([
            'name' => 'Teacher Two',
            'username' => 'teacher_two_exc',
            'email' => 'teacher2_exc@x.test',
        ]);

        $teacher3 = User::factory()->create([
            'name' => 'Teacher Three',
            'username' => 'teacher_three_exc',
            'email' => 'teacher3_exc@x.test',
        ]);

        $outsider = User::factory()->create([
            'name' => 'Outsider',
            'username' => 'outsider_user_exc',
            'email' => 'outsider_exc@x.test',
        ]);

        DB::table('academy_members')->insert([
            ['academy_id' => $academy->id, 'user_id' => $teacher2->id, 'status' => 2, 'role' => 'teacher', 'enrollment_date' => '2026-05-16', 'created_at' => now(), 'updated_at' => now()],
            ['academy_id' => $academy->id, 'user_id' => $teacher3->id, 'status' => 2, 'role' => 'teacher', 'enrollment_date' => '2026-05-16', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $scheduleP = ClassSchedule::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'classroom_id' => $classroom->id,
            'course_id' => $course->id,
            'teacher_id' => $teacher2->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => '101',
            'status' => 'active',
        ]);

        $scheduleQ = ClassSchedule::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'classroom_id' => $classroom2->id,
            'course_id' => $course->id,
            'teacher_id' => $owner->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => '102',
            'status' => 'active',
        ]);

        $this->ctx = compact('owner', 'academy', 'academicYear', 'semester', 'classroom', 'classroom2', 'course', 'teacher2', 'teacher3', 'outsider', 'scheduleP', 'scheduleQ');

        return $this->ctx;
    }

    public function test_exceptions_cases()
    {
        $ctx = $this->setupData();

        // 1. store cancelled 2026-09-21
        $response = $this->actingAs($ctx['owner'], 'api')->postJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions", [
            'class_schedule_id' => $ctx['scheduleP']->id,
            'date' => '2026-09-21',
            'type' => 'cancelled',
        ]);
        $response->assertStatus(201);

        $this->assertDatabaseHas('class_schedule_exceptions', [
            'class_schedule_id' => $ctx['scheduleP']->id,
            'date' => '2026-09-21',
            'type' => 'cancelled',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'schedules',
            'action' => 'created',
        ]);
        $this->assertDatabaseHas('class_schedules', [
            'id' => $ctx['scheduleP']->id,
            'status' => 'active',
        ]);

        // 2. store 2026-09-22 (not Monday)
        $response = $this->postJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions", [
            'class_schedule_id' => $ctx['scheduleP']->id,
            'date' => '2026-09-22',
            'type' => 'cancelled',
        ]);
        $response->assertStatus(422)->assertJsonPath('errors.date.0', 'วันที่ที่เลือกไม่ใช่วันจันทร์');

        // 3. store 2026-11-02 (outside semester)
        $response = $this->postJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions", [
            'class_schedule_id' => $ctx['scheduleP']->id,
            'date' => '2026-11-02',
            'type' => 'cancelled',
        ]);
        $response->assertStatus(422)->assertJsonPath('errors.date.0', 'วันที่อยู่นอกช่วงภาคเรียนของคาบนี้');

        // 4. store duplicate
        $response = $this->postJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions", [
            'class_schedule_id' => $ctx['scheduleP']->id,
            'date' => '2026-09-21',
            'type' => 'cancelled',
        ]);
        $response->assertStatus(422)->assertJsonPath('errors.date.0', 'คาบนี้มีรายการของวันที่นี้อยู่แล้ว — แก้ไขหรือลบรายการเดิม');

        $exception = ClassScheduleException::first();
        $this->deleteJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions/{$exception->id}")->assertStatus(200);

        // 5. substitute with same teacher
        $response = $this->postJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions", [
            'class_schedule_id' => $ctx['scheduleP']->id,
            'date' => '2026-09-21',
            'type' => 'substitute',
            'substitute_teacher_id' => $ctx['teacher2']->id,
        ]);
        $response->assertStatus(422)->assertJsonPath('errors.substitute_teacher_id.0', 'ครูผู้สอนแทนต้องไม่ใช่ครูประจำคาบ');

        // 6. substitute with outsider
        $response = $this->postJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions", [
            'class_schedule_id' => $ctx['scheduleP']->id,
            'date' => '2026-09-21',
            'type' => 'substitute',
            'substitute_teacher_id' => $ctx['outsider']->id,
        ]);
        $response->assertStatus(422)->assertJsonPath('errors.substitute_teacher_id.0', 'ครูผู้สอนแทนไม่ได้อยู่ในโรงเรียนนี้');

        // 7. substitute with teacher3 who has conflict
        $scheduleT3 = ClassSchedule::create([
            'academy_id' => $ctx['academy']->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id,
            'teacher_id' => $ctx['teacher3']->id,
            'day_of_week' => 1,
            'start_time' => '08:30',
            'end_time' => '09:30',
            'status' => 'active',
        ]);
        $response = $this->postJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions", [
            'class_schedule_id' => $ctx['scheduleP']->id,
            'date' => '2026-09-21',
            'type' => 'substitute',
            'substitute_teacher_id' => $ctx['teacher3']->id,
        ]);
        $response->assertStatus(422)->assertJsonPath('errors.substitute_teacher_id.0', 'ครูผู้สอนแทนมีคาบอื่นในเวลานี้');

        // 8. case 7 but teacher3's schedule is cancelled
        $this->postJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions", [
            'class_schedule_id' => $scheduleT3->id,
            'date' => '2026-09-21',
            'type' => 'cancelled',
        ])->assertStatus(201);

        $response = $this->postJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions", [
            'class_schedule_id' => $ctx['scheduleP']->id,
            'date' => '2026-09-21',
            'type' => 'substitute',
            'substitute_teacher_id' => $ctx['teacher3']->id,
        ]);
        $response->assertStatus(201);

        ClassScheduleException::query()->delete(); // ห้าม truncate: บน MySQL เป็น DDL → implicit commit → ข้อมูลหลุดข้ามเทสต์
        $scheduleT3->delete();

        // 9. teacher3 has adjacent schedule
        $scheduleT3_2 = ClassSchedule::create([
            'academy_id' => $ctx['academy']->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id,
            'teacher_id' => $ctx['teacher3']->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'active',
        ]);
        $response = $this->postJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions", [
            'class_schedule_id' => $ctx['scheduleP']->id,
            'date' => '2026-09-21',
            'type' => 'substitute',
            'substitute_teacher_id' => $ctx['teacher3']->id,
        ]);
        $response->assertStatus(201);

        ClassScheduleException::query()->delete(); // ห้าม truncate: บน MySQL เป็น DDL → implicit commit → ข้อมูลหลุดข้ามเทสต์

        // 10. teacher3 substitutes Q, then tries to substitute P at same time
        $this->postJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions", [
            'class_schedule_id' => $ctx['scheduleQ']->id,
            'date' => '2026-09-21',
            'type' => 'substitute',
            'substitute_teacher_id' => $ctx['teacher3']->id,
        ])->assertStatus(201);
        $response = $this->postJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions", [
            'class_schedule_id' => $ctx['scheduleP']->id,
            'date' => '2026-09-21',
            'type' => 'substitute',
            'substitute_teacher_id' => $ctx['teacher3']->id,
        ]);
        $response->assertStatus(422)->assertJsonPath('errors.substitute_teacher_id.0', 'ครูผู้สอนแทนมีคาบอื่นในเวลานี้');

        ClassScheduleException::query()->delete(); // ห้าม truncate: บน MySQL เป็น DDL → implicit commit → ข้อมูลหลุดข้ามเทสต์

        // 11. room_change missing room
        $response = $this->postJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions", [
            'class_schedule_id' => $ctx['scheduleP']->id,
            'date' => '2026-09-21',
            'type' => 'room_change',
        ]);
        $response->assertStatus(422)->assertJsonPath('errors.room.0', 'ต้องระบุห้องใหม่');

        // 12. available-teachers
        $response = $this->getJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions/available-teachers?schedule_id={$ctx['scheduleP']->id}&date=2026-09-21");
        $response->assertStatus(200);
        $teachers = $response->json('data.teachers');
        $this->assertContains($ctx['teacher3']->id, array_column($teachers, 'id'));
        $this->assertNotContains($ctx['teacher2']->id, array_column($teachers, 'id'));

        $scheduleT3_conflict = ClassSchedule::create([
            'academy_id' => $ctx['academy']->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id,
            'teacher_id' => $ctx['teacher3']->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions/available-teachers?schedule_id={$ctx['scheduleP']->id}&date=2026-09-21");
        $response->assertStatus(200);
        $teachers = $response->json('data.teachers');
        $this->assertNotContains($ctx['teacher3']->id, array_column($teachers, 'id'));
        $this->assertGreaterThanOrEqual(1, $response->json('data.busy_count'));

        $scheduleT3_conflict->delete();

        // 13. today after cancel
        $this->postJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions", [
            'class_schedule_id' => $ctx['scheduleP']->id,
            'date' => '2026-09-21',
            'type' => 'cancelled',
        ])->assertStatus(201);

        $response = $this->getJson("/api/academies/{$ctx['academy']->id}/schedules/today?date=2026-09-21");
        $response->assertStatus(200);
        $schedules = $response->json('data.schedules');
        $this->assertContains($ctx['scheduleP']->id, array_column($schedules, 'id'));
        $p = collect($schedules)->firstWhere('id', $ctx['scheduleP']->id);
        $this->assertEquals('cancelled', $p['exception']['type']);

        ClassScheduleException::query()->delete(); // ห้าม truncate: บน MySQL เป็น DDL → implicit commit → ข้อมูลหลุดข้ามเทสต์

        // 14. today with substitute
        $this->postJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions", [
            'class_schedule_id' => $ctx['scheduleP']->id,
            'date' => '2026-09-21',
            'type' => 'substitute',
            'substitute_teacher_id' => $ctx['teacher3']->id,
        ])->assertStatus(201);

        $response = $this->getJson("/api/academies/{$ctx['academy']->id}/schedules/today?date=2026-09-21&teacher_id={$ctx['teacher3']->id}");
        $response->assertStatus(200);
        $schedules = $response->json('data.schedules');
        $this->assertContains($ctx['scheduleP']->id, array_column($schedules, 'id'));

        // 15. timetable
        $response = $this->getJson("/api/academies/{$ctx['academy']->id}/schedules/timetable?classroom_id={$ctx['classroom']->id}&date=2026-09-23");
        $response->assertStatus(200);

        $timetable = $response->json('data.timetable');
        $foundP = false;
        foreach ($timetable as $day) {
            $p = collect($day['schedules'])->firstWhere('id', $ctx['scheduleP']->id);
            if ($p) {
                $this->assertEquals('2026-09-21', $p['date']);
                $this->assertEquals('substitute', $p['exception']['type']);
                $foundP = true;
                break;
            }
        }
        $this->assertTrue($foundP);

        $response = $this->getJson("/api/academies/{$ctx['academy']->id}/schedules/timetable?classroom_id={$ctx['classroom']->id}");
        $response->assertStatus(200)->assertJsonMissingPath('data.timetable.0.schedules.0.exception');

        // 16. my context for substitute teacher (Teacher 3 doesn't have permissions in fixture, so using timetable with owner instead)
        $response = $this->actingAs($ctx['owner'], 'api')->getJson("/api/academies/{$ctx['academy']->id}/schedules/timetable?teacher_id={$ctx['teacher3']->id}&date=2026-09-21");
        $response->assertStatus(200);
        $timetable = $response->json('data.timetable');
        $foundP = false;
        foreach ($timetable as $day) {
            $p = collect($day['schedules'])->firstWhere('id', $ctx['scheduleP']->id);
            if ($p) {
                $this->assertTrue($p['is_substitute']);
                $foundP = true;
                break;
            }
        }
        $this->assertTrue($foundP);

        // 17. update exception
        $exc = ClassScheduleException::first();
        $response = $this->patchJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions/{$exc->id}", [
            'type' => 'room_change',
            'room' => 'ห้อง 205',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'schedules',
            'action' => 'updated',
        ]);

        $this->deleteJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions/{$exc->id}")->assertStatus(200);
        $this->assertDatabaseMissing('class_schedule_exceptions', ['id' => $exc->id]);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'schedules',
            'action' => 'deleted',
        ]);

        // 18. index
        $this->postJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions", [
            'class_schedule_id' => $ctx['scheduleP']->id,
            'date' => '2026-09-21',
            'type' => 'substitute',
            'substitute_teacher_id' => $ctx['teacher3']->id,
        ])->assertStatus(201);

        $response = $this->getJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions?from=2026-09-01&to=2026-09-30");
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));

        $response = $this->getJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions?from=2026-08-01&to=2026-11-01");
        $response->assertStatus(422);

        $response = $this->getJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions?from=2026-09-01&to=2026-09-30&teacher_id={$ctx['teacher3']->id}");
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));

        // 19. cross academy
        $academy2 = Academy::create(['user_id' => $ctx['owner']->id, 'name' => 'school2', 'display_name' => 'S2']);
        $sch2 = ClassSchedule::create([
            'academy_id' => $academy2->id, 'academic_year_id' => $ctx['academicYear']->id, 'semester_id' => $ctx['semester']->id,
            'classroom_id' => $ctx['classroom']->id,
            'teacher_id' => $ctx['owner']->id, 'day_of_week' => 1, 'start_time' => '10:00', 'end_time' => '11:00',
            'status' => 'active',
        ]);
        $response = $this->postJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions", [
            'class_schedule_id' => $sch2->id,
            'date' => '2026-09-21',
            'type' => 'cancelled',
        ]);
        $response->assertStatus(422)->assertJsonPath('errors.class_schedule_id.0', 'ไม่พบคาบนี้ในโรงเรียน');

        // 20. outsider
        $response = $this->actingAs($ctx['outsider'], 'api')->postJson("/api/academies/{$ctx['academy']->id}/schedules/exceptions", [
            'class_schedule_id' => $ctx['scheduleP']->id,
            'date' => '2026-09-28',
            'type' => 'cancelled',
        ]);
        $response->assertStatus(403);
    }
}
