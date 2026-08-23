<?php

namespace Tests\Feature\Course;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\CourseGroupMember;
use App\Models\CourseMember;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseGroupClassroomImportTest extends TestCase
{
    use RefreshDatabase;

    private function setupContext(): array
    {
        $owner = User::factory()->create();
        $academy = Academy::create(['user_id' => $owner->id, 'name' => 'Test', 'slug' => 'test-'.uniqid()]);
        $year = AcademicYear::create(['academy_id' => $academy->id, 'name' => '2569',
            'start_date' => '2026-05-16', 'end_date' => '2027-03-31', 'is_current' => true]);
        $course = Course::factory()->create(['user_id' => $owner->id, 'academy_id' => $academy->id]);

        return [$owner, $academy, $year, $course];
    }

    private function createRoom($academy, $year, $name = 'ม.5/1', $teacherId = null)
    {
        $section = explode('/', $name)[1] ?? (string) rand(1, 9999);

        return Classroom::create([
            'academy_id' => $academy->id, 'academic_year_id' => $year->id,
            'grade_level' => 'ม.5', 'section' => $section, 'name' => $name, 'capacity' => 50,
            'is_active' => true, 'status' => 'active',
            'homeroom_teacher_id' => $teacherId,
        ]);
    }

    private function createStudent($academy, $u, $studentId = 'S1')
    {
        return Student::create([
            'user_id' => $u ? $u->id : null, 'academy_id' => $academy->id,
            'first_name_th' => 'ทดสอบ', 'last_name_th' => 'นักเรียน', 'student_id' => $studentId,
            'citizen_id' => str_pad(rand(1, 99999), 13, '0', STR_PAD_LEFT),
        ]);
    }

    private function enrollStudent($academy, $year, $room, $student, $number = 1)
    {
        return ClassroomStudent::create([
            'academy_id' => $academy->id, 'classroom_id' => $room->id,
            'student_id' => $student->id, 'academic_year_id' => $year->id,
            'student_number' => $number, 'status' => 'active',
        ]);
    }

    public function test_import_per_classroom_creates_tables_correctly()
    {
        [$owner, $academy, $year, $course] = $this->setupContext();
        $room = $this->createRoom($academy, $year);

        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $u3 = User::factory()->create();

        $s1 = $this->createStudent($academy, $u1, 'S1');
        $s2 = $this->createStudent($academy, $u2, 'S2');
        $s3 = $this->createStudent($academy, $u3, 'S3');

        $this->enrollStudent($academy, $year, $room, $s1, 1);
        $this->enrollStudent($academy, $year, $room, $s2, 2);
        $this->enrollStudent($academy, $year, $room, $s3, 3);

        $response = $this->actingAs($owner, 'api')
            ->postJson("/api/courses/{$course->id}/groups/import-from-classrooms", [
                'classroom_ids' => [$room->id],
                'mode' => 'per_classroom',
                'dry_run' => false,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('summary.to_add', 3);

        $this->assertDatabaseCount('course_groups', 1);
        $this->assertDatabaseHas('course_groups', ['name' => 'ม.5/1']);

        $group = CourseGroup::first();
        $this->assertNotNull($group->classroom_synced_at);

        $this->assertDatabaseCount('course_members', 3);
        $this->assertDatabaseHas('course_members', ['group_id' => $group->id, 'user_id' => $u1->id]);

        $this->assertDatabaseCount('course_group_members', 3);
        $this->assertDatabaseHas('course_group_members', ['group_id' => $group->id, 'user_id' => $u1->id]);

        $this->assertDatabaseCount('course_group_classrooms', 1);
        $this->assertDatabaseHas('course_group_classrooms', [
            'course_group_id' => $group->id,
            'classroom_id' => $room->id,
            'academic_year_id' => $year->id,
        ]);
    }

    public function test_import_sets_order_number_from_student_number()
    {
        [$owner, $academy, $year, $course] = $this->setupContext();
        $room = $this->createRoom($academy, $year);
        $u1 = User::factory()->create();
        $s1 = $this->createStudent($academy, $u1, 'S1');
        $this->enrollStudent($academy, $year, $room, $s1, 7);

        $this->actingAs($owner, 'api')
            ->postJson("/api/courses/{$course->id}/groups/import-from-classrooms", [
                'classroom_ids' => [$room->id],
                'mode' => 'per_classroom',
                'dry_run' => false,
            ])->assertStatus(200);

        $this->assertDatabaseHas('course_members', ['user_id' => $u1->id, 'order_number' => 7]);
    }

    public function test_import_skips_students_without_user_account()
    {
        [$owner, $academy, $year, $course] = $this->setupContext();
        $room = $this->createRoom($academy, $year);
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();

        $s1 = $this->createStudent($academy, $u1, 'S1');
        $s2 = $this->createStudent($academy, $u2, 'S2');
        $s3 = $this->createStudent($academy, null, 'S3');

        $this->enrollStudent($academy, $year, $room, $s1, 1);
        $this->enrollStudent($academy, $year, $room, $s2, 2);
        $this->enrollStudent($academy, $year, $room, $s3, 3);

        $response = $this->actingAs($owner, 'api')
            ->postJson("/api/courses/{$course->id}/groups/import-from-classrooms", [
                'classroom_ids' => [$room->id],
                'mode' => 'per_classroom',
                'dry_run' => false,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('summary.no_user_account', 1);
        $this->assertNotEmpty($response->json('items.0.no_user_account'));
        $this->assertDatabaseCount('course_members', 2);
    }

    public function test_import_is_idempotent()
    {
        [$owner, $academy, $year, $course] = $this->setupContext();
        $room = $this->createRoom($academy, $year);
        $u1 = User::factory()->create();
        $s1 = $this->createStudent($academy, $u1, 'S1');
        $this->enrollStudent($academy, $year, $room, $s1, 1);

        $payload = [
            'classroom_ids' => [$room->id],
            'mode' => 'per_classroom',
            'dry_run' => false,
        ];

        $this->actingAs($owner, 'api')->postJson("/api/courses/{$course->id}/groups/import-from-classrooms", $payload)->assertStatus(200);
        $this->assertDatabaseCount('course_members', 1);
        $this->assertDatabaseCount('course_groups', 1);
        $this->assertDatabaseCount('course_group_classrooms', 1);

        $this->actingAs($owner, 'api')->postJson("/api/courses/{$course->id}/groups/import-from-classrooms", $payload)->assertStatus(200);
        $this->assertDatabaseCount('course_members', 1);
        $this->assertDatabaseCount('course_groups', 1);
        $this->assertDatabaseCount('course_group_classrooms', 1);
    }

    public function test_import_dry_run_does_not_write_to_db()
    {
        [$owner, $academy, $year, $course] = $this->setupContext();
        $room = $this->createRoom($academy, $year);
        $u1 = User::factory()->create();
        $s1 = $this->createStudent($academy, $u1, 'S1');
        $this->enrollStudent($academy, $year, $room, $s1, 1);

        $response = $this->actingAs($owner, 'api')
            ->postJson("/api/courses/{$course->id}/groups/import-from-classrooms", [
                'classroom_ids' => [$room->id],
                'mode' => 'per_classroom',
                'dry_run' => true,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseCount('course_members', 0);
        $this->assertDatabaseCount('course_groups', 0);
        $this->assertDatabaseCount('course_group_classrooms', 0);
    }

    public function test_import_single_group_mode_combines_classrooms()
    {
        [$owner, $academy, $year, $course] = $this->setupContext();
        $room1 = $this->createRoom($academy, $year, 'Room A');
        $room2 = $this->createRoom($academy, $year, 'Room B');

        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $u3 = User::factory()->create();
        $u4 = User::factory()->create();

        $s1 = $this->createStudent($academy, $u1, 'S1');
        $s2 = $this->createStudent($academy, $u2, 'S2');
        $s3 = $this->createStudent($academy, $u3, 'S3');
        $s4 = $this->createStudent($academy, $u4, 'S4');

        $this->enrollStudent($academy, $year, $room1, $s1, 1);
        $this->enrollStudent($academy, $year, $room1, $s2, 2);

        $this->enrollStudent($academy, $year, $room2, $s3, 1);
        $this->enrollStudent($academy, $year, $room2, $s4, 2);

        $response = $this->actingAs($owner, 'api')
            ->postJson("/api/courses/{$course->id}/groups/import-from-classrooms", [
                'classroom_ids' => [$room1->id, $room2->id],
                'mode' => 'single_group',
                'group_name' => 'กลุ่มบ่าย',
                'dry_run' => false,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseCount('course_groups', 1);
        $this->assertDatabaseHas('course_groups', ['name' => 'กลุ่มบ่าย']);

        $group = CourseGroup::first();

        $this->assertDatabaseCount('course_members', 4);
        $this->assertDatabaseCount('course_group_members', 4);
        $this->assertDatabaseCount('course_group_classrooms', 2);
        $this->assertDatabaseHas('course_group_classrooms', ['course_group_id' => $group->id, 'classroom_id' => $room1->id]);
        $this->assertDatabaseHas('course_group_classrooms', ['course_group_id' => $group->id, 'classroom_id' => $room2->id]);
    }

    public function test_import_moves_student_from_other_group()
    {
        [$owner, $academy, $year, $course] = $this->setupContext();
        $room = $this->createRoom($academy, $year, 'Room B');
        $u1 = User::factory()->create();
        $s1 = $this->createStudent($academy, $u1, 'S1');
        $this->enrollStudent($academy, $year, $room, $s1, 1);

        $groupA = CourseGroup::create([
            'course_id' => $course->id, 'user_id' => $owner->id, 'name' => 'Group A',
        ]);
        $cm = CourseMember::create([
            'course_id' => $course->id, 'user_id' => $u1->id, 'group_id' => $groupA->id,
            'role' => 1, 'status' => 1,
        ]);
        CourseGroupMember::create([
            'course_id' => $course->id, 'group_id' => $groupA->id, 'user_id' => $u1->id,
        ]);

        $response = $this->actingAs($owner, 'api')
            ->postJson("/api/courses/{$course->id}/groups/import-from-classrooms", [
                'classroom_ids' => [$room->id],
                'mode' => 'per_classroom',
                'dry_run' => false,
            ]);

        $response->assertStatus(200);
        $groupB = CourseGroup::where('name', 'Room B')->first();

        $this->assertDatabaseHas('course_members', ['id' => $cm->id, 'group_id' => $groupB->id]);
        $this->assertDatabaseCount('course_group_members', 1);
        $this->assertDatabaseHas('course_group_members', ['group_id' => $groupB->id, 'user_id' => $u1->id]);
        $this->assertNotEmpty($response->json('items.0.moving_from_other_group'));
    }

    public function test_course_member_without_group_appears_in_to_add()
    {
        [$owner, $academy, $year, $course] = $this->setupContext();
        $room = $this->createRoom($academy, $year, 'Room B');
        $u1 = User::factory()->create();
        $s1 = $this->createStudent($academy, $u1, 'S1');
        $this->enrollStudent($academy, $year, $room, $s1, 1);

        $cm = CourseMember::create([
            'course_id' => $course->id, 'user_id' => $u1->id, 'group_id' => null,
            'role' => 1, 'status' => 1,
        ]);

        $response = $this->actingAs($owner, 'api')
            ->postJson("/api/courses/{$course->id}/groups/import-from-classrooms", [
                'classroom_ids' => [$room->id],
                'mode' => 'per_classroom',
                'dry_run' => true,
            ]);

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('summary.to_add'));
        $this->assertEquals(0, $response->json('summary.moving_from_other_group'));
    }

    public function test_sync_classroom_shows_diff_and_does_not_remove_automatically()
    {
        [$owner, $academy, $year, $course] = $this->setupContext();
        $room = $this->createRoom($academy, $year, 'Room A');

        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $u3 = User::factory()->create();
        $u4 = User::factory()->create();

        $s1 = $this->createStudent($academy, $u1, 'S1');
        $s2 = $this->createStudent($academy, $u2, 'S2');
        $s3 = $this->createStudent($academy, $u3, 'S3');
        $s4 = $this->createStudent($academy, $u4, 'S4');

        $en1 = $this->enrollStudent($academy, $year, $room, $s1, 1);
        $this->enrollStudent($academy, $year, $room, $s2, 2);
        $this->enrollStudent($academy, $year, $room, $s3, 3);

        $this->actingAs($owner, 'api')
            ->postJson("/api/courses/{$course->id}/groups/import-from-classrooms", [
                'classroom_ids' => [$room->id],
                'mode' => 'per_classroom',
                'dry_run' => false,
            ]);

        $group = CourseGroup::where('name', 'Room A')->first();

        $this->enrollStudent($academy, $year, $room, $s4, 4);
        $en1->update(['status' => 'removed']);

        $res = $this->actingAs($owner, 'api')
            ->postJson("/api/courses/{$course->id}/groups/{$group->id}/sync-classroom", [
                'dry_run' => true,
            ]);

        $res->assertStatus(200);
        $this->assertCount(1, $res->json('to_add'));
        $this->assertCount(1, $res->json('missing'));
        $this->assertEquals(2, $res->json('unchanged_count'));

        $this->actingAs($owner, 'api')
            ->postJson("/api/courses/{$course->id}/groups/{$group->id}/sync-classroom", [
                'dry_run' => false,
            ])->assertStatus(200);

        $this->assertDatabaseHas('course_members', ['user_id' => $u4->id, 'group_id' => $group->id]);
        $this->assertDatabaseHas('course_members', ['user_id' => $u1->id, 'group_id' => $group->id]);
    }

    public function test_sync_classroom_detach_removes_from_group_but_keeps_course_member()
    {
        [$owner, $academy, $year, $course] = $this->setupContext();
        $room = $this->createRoom($academy, $year, 'Room A');

        $u1 = User::factory()->create();
        $s1 = $this->createStudent($academy, $u1, 'S1');
        $en1 = $this->enrollStudent($academy, $year, $room, $s1, 1);

        $this->actingAs($owner, 'api')
            ->postJson("/api/courses/{$course->id}/groups/import-from-classrooms", [
                'classroom_ids' => [$room->id],
                'mode' => 'per_classroom',
                'dry_run' => false,
            ]);

        $group = CourseGroup::first();
        $cm1 = CourseMember::where('user_id', $u1->id)->first();

        $en1->update(['status' => 'removed']);

        $this->actingAs($owner, 'api')
            ->postJson("/api/courses/{$course->id}/groups/{$group->id}/sync-classroom", [
                'dry_run' => false,
                'detach_member_ids' => [$u1->id],
            ])->assertStatus(200);

        $this->assertDatabaseHas('course_members', ['id' => $cm1->id, 'group_id' => $group->id]);

        $this->actingAs($owner, 'api')
            ->postJson("/api/courses/{$course->id}/groups/{$group->id}/sync-classroom", [
                'dry_run' => false,
                'detach_member_ids' => [$cm1->id],
            ])->assertStatus(200);

        $this->assertDatabaseHas('course_members', ['id' => $cm1->id, 'group_id' => null]);
        $this->assertDatabaseMissing('course_group_members', ['user_id' => $u1->id, 'group_id' => $group->id]);
    }

    public function test_permissions_for_classroom_import()
    {
        [$owner, $academy, $year, $course] = $this->setupContext();
        $roomA = $this->createRoom($academy, $year, 'Room A');
        $roomB = $this->createRoom($academy, $year, 'Room B');

        $randomUser = User::factory()->create();
        $this->actingAs($randomUser, 'api')
            ->postJson("/api/courses/{$course->id}/groups/import-from-classrooms", [
                'classroom_ids' => [$roomA->id], 'mode' => 'per_classroom', 'dry_run' => true,
            ])->assertStatus(403);

        $courseNoAcademy = Course::factory()->create(['user_id' => $owner->id, 'academy_id' => null]);
        $this->actingAs($owner, 'api')
            ->postJson("/api/courses/{$courseNoAcademy->id}/groups/import-from-classrooms", [
                'classroom_ids' => [$roomA->id], 'mode' => 'per_classroom', 'dry_run' => true,
            ])->assertStatus(422);

        $teacher = User::factory()->create();
        AcademyMember::create([
            'academy_id' => $academy->id, 'user_id' => $teacher->id, 'status' => 2,
        ]);
        CourseMember::create([
            'course_id' => $course->id, 'user_id' => $teacher->id, 'role' => 4, 'status' => 1,
        ]);
        $roomA->update(['homeroom_teacher_id' => $teacher->id]);

        $this->actingAs($teacher, 'api')
            ->postJson("/api/courses/{$course->id}/groups/import-from-classrooms", [
                'classroom_ids' => [$roomB->id], 'mode' => 'per_classroom', 'dry_run' => true,
            ])->assertStatus(403);

        $this->actingAs($teacher, 'api')
            ->postJson("/api/courses/{$course->id}/groups/import-from-classrooms", [
                'classroom_ids' => [$roomA->id], 'mode' => 'per_classroom', 'dry_run' => true,
            ])->assertStatus(200);

        $role = AcademyRole::create([
            'academy_id' => $academy->id, 'name' => 'Staff', 'display_name_th' => 'เจ้าหน้าที่',
            'permissions' => ['students.view'],
        ]);
        $staff = User::factory()->create();
        AcademyMember::create([
            'academy_id' => $academy->id, 'user_id' => $staff->id, 'status' => 2, 'academy_role_id' => $role->id,
        ]);
        CourseMember::create([
            'course_id' => $course->id, 'user_id' => $staff->id, 'role' => 4, 'status' => 1,
        ]);

        $this->actingAs($staff, 'api')
            ->postJson("/api/courses/{$course->id}/groups/import-from-classrooms", [
                'classroom_ids' => [$roomB->id], 'mode' => 'per_classroom', 'dry_run' => true,
            ])->assertStatus(200);
    }
}
