<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyRole;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * SC-S7 — ด่านสิทธิ์ของตารางเรียน (D4 / G10) + endpoint "ตารางของฉัน"
 *
 * ก่อนหน้านี้ด่าน GET ขอแค่ "เป็นสมาชิก" ⇒ คีย์ `schedule.view` ไม่มีความหมาย
 * และ `schedule.manage` ไม่ได้อยู่ในบทบาทใดเลย ⇒ ผูก route อย่างเดียวไม่พอ
 * ต้องเติมคีย์เข้าบทบาทจริงด้วย (migration) เทสต์ชุดนี้จึงสร้างบทบาทเองเพื่อวัดที่ "ด่าน"
 */
class SchedulePermissionTest extends TestCase
{
    use RefreshDatabase;

    private array $ctx = [];

    private function setupData(): array
    {
        $owner = User::factory()->create([
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

        $semester2 = Semester::create([
            'academic_year_id' => $academicYear->id, 'semester_number' => 2, 'name' => '2/2569',
            'start_date' => '2026-11-01', 'end_date' => '2027-03-31', 'is_current' => false,
        ]);

        $classroom = Classroom::create([
            'academy_id' => $academy->id, 'academic_year_id' => $academicYear->id,
            'name' => 'ม.1/1', 'grade_level' => 'ม.1', 'section' => '1', 'capacity' => 40,
        ]);

        $otherClassroom = Classroom::create([
            'academy_id' => $academy->id, 'academic_year_id' => $academicYear->id,
            'name' => 'ม.1/2', 'grade_level' => 'ม.1', 'section' => '2', 'capacity' => 40,
        ]);

        $course = Course::create([
            'academy_id' => $academy->id, 'user_id' => $owner->id, 'instructor_id' => $owner->id,
            'name' => 'คณิตศาสตร์', 'code' => 'MATH101',
        ]);

        $this->ctx = compact('owner', 'academy', 'academicYear', 'semester', 'semester2', 'classroom', 'otherClassroom', 'course');

        return $this->ctx;
    }

    /**
     * สร้างบทบาทระบบ + สมาชิกที่ถือบทบาทนั้น (จำลองสภาพจริงหลัง migration)
     */
    private function memberWithRole(string $roleName, array $permissions, string $username): User
    {
        $academy = $this->ctx['academy'];

        $role = AcademyRole::firstOrCreate(
            ['name' => $roleName, 'academy_id' => null],
            [
                'display_name_th' => $roleName,
                'display_name_en' => $roleName,
                'permissions' => $permissions,
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        $user = User::factory()->create([
            'name' => $username, 'username' => $username,
            'email' => $username.'@x.test', 'password' => bcrypt('password'),
        ]);

        DB::table('academy_members')->insert([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $role->id,
            'status' => 2,
            'role' => $roleName,
            'enrollment_date' => '2026-05-16',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
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
            'teacher_id' => $c['owner']->id,
            'day_of_week' => 1,
            'start_time' => '08:30',
            'end_time' => '09:20',
            'status' => ClassSchedule::STATUS_ACTIVE,
            'created_by' => $c['owner']->id,
        ], $overrides));
    }

    /**
     * ผูกผู้ใช้เข้ากับนักเรียนที่มี enrollment active ในห้องที่ระบุ
     */
    private function enrollStudent(User $user, Classroom $classroom): Student
    {
        $c = $this->ctx;

        $student = Student::create([
            'user_id' => $user->id,
            'academy_id' => $c['academy']->id,
            'student_id' => 'S'.str_pad((string) $user->id, 6, '0', STR_PAD_LEFT),
            'first_name_th' => 'เด็กชาย',
            'last_name_th' => 'ทดสอบ',
        ]);

        ClassroomStudent::create([
            'academy_id' => $c['academy']->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'academic_year_id' => $c['academicYear']->id,
            'status' => 'active',
            'enrolled_at' => '2026-05-16',
        ]);

        return $student;
    }

    // ---------------------------------------------------------------
    // ด่านอ่านตารางของทั้งโรงเรียน = schedule.view
    // ---------------------------------------------------------------

    public function test_member_without_schedule_view_cannot_read_school_timetable()
    {
        $c = $this->setupData();
        // card_admin คือบทบาทจริงในระบบที่ไม่มีคีย์ schedule ใด ๆ เลย
        $user = $this->memberWithRole('card_admin', ['academy.view', 'members.view'], 'card_admin_user');

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedules")
            ->assertStatus(403);

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedules/timetable?classroom_id={$c['classroom']->id}")
            ->assertStatus(403);
    }

    public function test_teacher_role_can_read_school_timetable()
    {
        $c = $this->setupData();
        $user = $this->memberWithRole('teacher', ['academy.view', 'schedule.view'], 'teacher_user');

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedules")
            ->assertStatus(200);
    }

    public function test_student_role_can_read_school_timetable_after_grant()
    {
        $c = $this->setupData();
        // สภาพหลัง migration: นักเรียนถือทั้งสองคีย์
        $user = $this->memberWithRole('student', ['academy.view', 'schedule.view', 'schedule.view.own'], 'student_user');

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedules")
            ->assertStatus(200);
    }

    public function test_only_own_key_blocks_school_timetable_but_not_my_schedule()
    {
        $c = $this->setupData();
        // สภาพก่อน migration / โรงเรียนที่ถอด schedule.view ออกจากนักเรียนเอง
        $user = $this->memberWithRole('student', ['academy.view', 'schedule.view.own'], 'own_only_user');

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedules")
            ->assertStatus(403);

        $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedules/my")
            ->assertStatus(200);
    }

    public function test_non_member_cannot_reach_my_schedule()
    {
        $c = $this->setupData();

        $outsider = User::factory()->create([
            'name' => 'Outsider', 'username' => 'outsider_user',
            'email' => 'outsider@x.test', 'password' => bcrypt('password'),
        ]);

        $this->actingAs($outsider, 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedules/my")
            ->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // ด่านจัดตาราง = schedule.manage (เหตุผลที่ migration ต้องมี)
    // ---------------------------------------------------------------

    public function test_admin_role_without_manage_key_cannot_create_schedule()
    {
        $c = $this->setupData();
        $user = $this->memberWithRole('admin', ['academy.view', 'schedule.view'], 'admin_no_manage');

        $this->actingAs($user, 'api')->postJson("/api/academies/{$c['academy']->id}/schedules", [
            'semester_id' => $c['semester']->id,
            'classroom_id' => $c['classroom']->id,
            'course_id' => $c['course']->id,
            'teacher_id' => $c['owner']->id,
            'day_of_week' => 2,
            'start_time' => '08:30',
            'end_time' => '09:20',
        ])->assertStatus(403);
    }

    public function test_role_with_manage_key_can_create_schedule()
    {
        $c = $this->setupData();
        $user = $this->memberWithRole('admin', ['academy.view', 'schedule.view', 'schedule.manage'], 'admin_with_manage');

        $this->actingAs($user, 'api')->postJson("/api/academies/{$c['academy']->id}/schedules", [
            'semester_id' => $c['semester']->id,
            'classroom_id' => $c['classroom']->id,
            'course_id' => $c['course']->id,
            'teacher_id' => $user->id,
            'day_of_week' => 2,
            'start_time' => '08:30',
            'end_time' => '09:20',
        ])->assertStatus(201);
    }

    public function test_default_roles_template_carries_the_new_keys()
    {
        $roles = AcademyRole::SYSTEM_ROLES;

        $this->assertContains('schedule.manage', $roles['director']['permissions']);
        $this->assertContains('schedule.manage', $roles['admin']['permissions']);
        $this->assertContains('schedule.view', $roles['student']['permissions']);
        $this->assertContains('schedule.view', $roles['parent']['permissions']);
        // ของเดิมต้องไม่หาย
        $this->assertContains('schedule.view.own', $roles['student']['permissions']);
        $this->assertContains('children.schedule.view', $roles['parent']['permissions']);
        $this->assertContains('schedule.view', $roles['teacher']['permissions']);
    }

    // ---------------------------------------------------------------
    // ตารางของฉัน
    // ---------------------------------------------------------------

    public function test_my_returns_teacher_context_with_only_own_periods()
    {
        $c = $this->setupData();
        $teacher = $this->memberWithRole('teacher', ['academy.view', 'schedule.view'], 'my_teacher');

        $this->makeSchedule(['teacher_id' => $teacher->id, 'day_of_week' => 1, 'start_time' => '08:30', 'end_time' => '09:20']);
        $this->makeSchedule(['teacher_id' => $teacher->id, 'day_of_week' => 3, 'start_time' => '10:10', 'end_time' => '11:00']);
        // คาบของครูคนอื่น ต้องไม่ติดมา
        $this->makeSchedule(['teacher_id' => $c['owner']->id, 'day_of_week' => 5, 'start_time' => '08:30', 'end_time' => '09:20']);

        $response = $this->actingAs($teacher, 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedules/my");

        $response->assertStatus(200);
        $response->assertJsonPath('data.contexts.0.type', 'teacher');
        $response->assertJsonPath('data.contexts.0.entity.id', $teacher->id);
        $response->assertJsonPath('data.semester.id', $c['semester']->id);
        $response->assertJsonCount(2, 'data.contexts.0.timetable');   // วันจันทร์ + วันพุธ

        $days = collect($response->json('data.contexts.0.timetable'))->pluck('day')->all();
        $this->assertSame([1, 3], $days);
    }

    public function test_my_returns_classroom_context_from_active_enrollment()
    {
        $c = $this->setupData();
        $student = $this->memberWithRole('student', ['academy.view', 'schedule.view.own'], 'my_student');
        $this->enrollStudent($student, $c['classroom']);

        $this->makeSchedule(['classroom_id' => $c['classroom']->id, 'day_of_week' => 2]);
        // คาบของห้องอื่น ต้องไม่ติดมา
        $this->makeSchedule(['classroom_id' => $c['otherClassroom']->id, 'day_of_week' => 4]);

        $response = $this->actingAs($student, 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedules/my");

        $response->assertStatus(200);
        $response->assertJsonPath('data.contexts.0.type', 'classroom');
        $response->assertJsonPath('data.contexts.0.entity.id', $c['classroom']->id);
        $response->assertJsonPath('data.contexts.0.entity.name', 'ม.1/1');
        $response->assertJsonCount(1, 'data.contexts.0.timetable');
        $response->assertJsonPath('data.contexts.0.timetable.0.day', 2);
    }

    public function test_my_returns_both_contexts_when_user_teaches_and_studies()
    {
        $c = $this->setupData();
        $user = $this->memberWithRole('teacher', ['academy.view', 'schedule.view'], 'both_roles');
        $this->enrollStudent($user, $c['classroom']);

        $this->makeSchedule(['teacher_id' => $user->id, 'day_of_week' => 1]);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedules/my");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data.contexts');
        $this->assertSame(['teacher', 'classroom'], collect($response->json('data.contexts'))->pluck('type')->all());
    }

    public function test_my_gives_teacher_an_empty_timetable_instead_of_nothing()
    {
        $c = $this->setupData();
        $teacher = $this->memberWithRole('teacher', ['academy.view', 'schedule.view'], 'fresh_teacher');

        $response = $this->actingAs($teacher, 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedules/my");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.contexts');
        $response->assertJsonPath('data.contexts.0.type', 'teacher');
        $response->assertJsonCount(0, 'data.contexts.0.timetable');
    }

    public function test_my_returns_no_context_for_member_who_neither_teaches_nor_studies()
    {
        $c = $this->setupData();
        $user = $this->memberWithRole('staff', ['academy.view', 'schedule.view'], 'plain_staff');

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedules/my");

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data.contexts');
    }

    public function test_my_filters_by_semester()
    {
        $c = $this->setupData();
        $teacher = $this->memberWithRole('teacher', ['academy.view', 'schedule.view'], 'semester_teacher');

        $this->makeSchedule(['teacher_id' => $teacher->id, 'day_of_week' => 1, 'semester_id' => $c['semester']->id]);
        $this->makeSchedule(['teacher_id' => $teacher->id, 'day_of_week' => 4, 'semester_id' => $c['semester2']->id]);

        $current = $this->actingAs($teacher, 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedules/my");
        $current->assertJsonCount(1, 'data.contexts.0.timetable');
        $current->assertJsonPath('data.contexts.0.timetable.0.day', 1);

        $second = $this->actingAs($teacher, 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedules/my?semester_id={$c['semester2']->id}");
        $second->assertJsonCount(1, 'data.contexts.0.timetable');
        $second->assertJsonPath('data.contexts.0.timetable.0.day', 4);
        $second->assertJsonPath('data.semester.id', $c['semester2']->id);
    }

    public function test_my_rejects_semester_of_another_academy()
    {
        $c = $this->setupData();
        $teacher = $this->memberWithRole('teacher', ['academy.view', 'schedule.view'], 'tenant_teacher');

        $otherOwner = User::factory()->create([
            'name' => 'Other', 'username' => 'other_owner',
            'email' => 'other@x.test', 'password' => bcrypt('password'),
        ]);
        $otherAcademy = Academy::create(['user_id' => $otherOwner->id, 'name' => 'school2', 'display_name' => 'School 2']);
        $otherYear = AcademicYear::create([
            'academy_id' => $otherAcademy->id, 'name' => '2569', 'is_current' => true,
            'start_date' => '2026-05-16', 'end_date' => '2027-03-31',
        ]);
        $otherSemester = Semester::create([
            'academic_year_id' => $otherYear->id, 'semester_number' => 1, 'name' => '1/2569',
            'start_date' => '2026-05-16', 'end_date' => '2026-10-31', 'is_current' => false,
        ]);

        $this->actingAs($teacher, 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedules/my?semester_id={$otherSemester->id}")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['semester_id']);
    }

    public function test_my_ignores_students_who_left_the_classroom()
    {
        $c = $this->setupData();
        $student = $this->memberWithRole('student', ['academy.view', 'schedule.view.own'], 'left_student');
        $studentRow = $this->enrollStudent($student, $c['classroom']);
        $studentRow->classroomEnrollments()->update(['status' => 'transferred']);

        $this->makeSchedule(['classroom_id' => $c['classroom']->id, 'day_of_week' => 2]);

        $response = $this->actingAs($student, 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedules/my");

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data.contexts');
    }

    // ---------------------------------------------------------------
    // กันถอยหลัง: timetable เดิมต้องไม่เปลี่ยนรูปหลังแยกตัวช่วยออกมา
    // ---------------------------------------------------------------

    public function test_timetable_response_shape_is_unchanged()
    {
        $c = $this->setupData();
        $this->makeSchedule(['day_of_week' => 1, 'room' => 'ห้อง 101']);

        $response = $this->actingAs($c['owner'], 'api')
            ->getJson("/api/academies/{$c['academy']->id}/schedules/timetable?classroom_id={$c['classroom']->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.view_type', 'classroom');
        $response->assertJsonPath('data.timetable.0.day', 1);
        $response->assertJsonPath('data.timetable.0.day_name', 'วันจันทร์');
        $response->assertJsonPath('data.timetable.0.day_name_short', 'จ.');
        $response->assertJsonPath('data.timetable.0.schedules.0.start_time', '08:30');
        $response->assertJsonPath('data.timetable.0.schedules.0.room', 'ห้อง 101');
        $this->assertArrayHasKey('teacher', $response->json('data.timetable.0.schedules.0'));
    }
}
