<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ขอบเขตสิทธิ์ของหน้าจัดการบัตรนักเรียนฝั่งโรงเรียน
 *
 * กติกา: ผู้จัดการระดับโรงเรียน (students.manage / แอดมิน) แก้ได้ทุกห้อง
 * ส่วนครูประจำชั้นแก้ได้เฉพาะนักเรียนที่กำลังเรียนอยู่ในห้องของตัวเอง
 *
 * เทสต์ test_every_card_write_route_is_guarded คือด่านที่กัน regression สำคัญที่สุด
 * ของชุดนี้ — route เขียนที่เพิ่มใหม่แล้วลืมเรียก StudentCardAccessService จะตกที่นั่น
 */
class AcademyStudentCardAccessTest extends TestCase
{
    use RefreshDatabase;

    private Academy $academy;

    private User $owner;

    private User $manager;

    private User $homeroomTeacher;

    private User $otherTeacher;

    private AcademicYear $year;

    private Classroom $roomA;

    private Classroom $roomB;

    private Student $studentInRoomA;

    private StudentCard $cardInRoomA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->makeUser('owner');
        $this->manager = $this->makeUser('manager');
        $this->homeroomTeacher = $this->makeUser('homeroom');
        $this->otherTeacher = $this->makeUser('other');

        $this->academy = Academy::create([
            'name' => 'CardAcademy_'.uniqid(),
            'user_id' => $this->owner->id,
        ]);

        $viewRole = $this->makeRole('teacher-local', ['students.view']);
        $manageRole = $this->makeRole('manager-local', ['students.view', 'students.manage']);

        $this->joinAcademy($this->manager, $manageRole);
        $this->joinAcademy($this->homeroomTeacher, $viewRole);
        $this->joinAcademy($this->otherTeacher, $viewRole);

        $this->year = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2569',
            'start_date' => '2026-05-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
        ]);

        $this->roomA = $this->makeClassroom('ม.6', '1', $this->homeroomTeacher->id);
        $this->roomB = $this->makeClassroom('ม.6', '2', $this->otherTeacher->id);

        $this->studentInRoomA = $this->makeStudent('STU-A1');
        $this->enroll($this->studentInRoomA, $this->roomA, 1);
        $this->cardInRoomA = $this->makeCard($this->studentInRoomA, '6', '1');
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function makeUser(string $tag): User
    {
        return User::create([
            'name' => 'U'.$tag,
            'email' => $tag.uniqid().'@x.test',
            'password' => bcrypt('x'),
            'username' => $tag.uniqid(),
            'reference_code' => 'R'.uniqid(),
            'personal_code' => 'P'.uniqid(),
        ]);
    }

    private function makeRole(string $name, array $permissions): AcademyRole
    {
        return AcademyRole::create([
            'academy_id' => $this->academy->id,
            'name' => $name.'-'.uniqid(),
            'display_name_th' => $name,
            'permissions' => $permissions,
            'is_active' => true,
        ]);
    }

    private function joinAcademy(User $user, AcademyRole $role): void
    {
        AcademyMember::create([
            'user_id' => $user->id,
            'academy_id' => $this->academy->id,
            'role' => 'teacher',
            'academy_role_id' => $role->id,
            'status' => AcademyMember::STATUS_APPROVED,
        ]);
    }

    private function makeClassroom(string $gradeLevel, string $section, ?int $homeroomTeacherId): Classroom
    {
        return Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year->id,
            'grade_level' => $gradeLevel,
            'section' => $section,
            'name' => $gradeLevel.'/'.$section,
            'homeroom_teacher_id' => $homeroomTeacherId,
            'status' => Classroom::STATUS_ACTIVE,
            'is_active' => true,
        ]);
    }

    private function makeStudent(string $code): Student
    {
        return Student::create([
            'academy_id' => $this->academy->id,
            'student_id' => $code,
            'title_prefix_th' => 'นาย',
            'first_name_th' => 'ทดสอบ',
            'last_name_th' => $code,
            'status' => 'active',
        ]);
    }

    private function enroll(Student $student, Classroom $classroom, int $number): ClassroomStudent
    {
        return ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->year->id,
            'student_number' => $number,
            'status' => ClassroomStudent::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);
    }

    private function makeCard(Student $student, string $level, string $section): StudentCard
    {
        return StudentCard::create([
            'academy_id' => $this->academy->id,
            'student_id' => $student->id,
            'student_number' => $student->student_id,
            'full_name_thai' => 'นาย ทดสอบ '.$student->student_id,
            'class_level' => $level,
            'class_section' => $section,
            'student_status' => 'active',
        ]);
    }

    private function cardUrl(string $suffix = ''): string
    {
        return "/api/academies/{$this->academy->id}/student-cards".$suffix;
    }

    // ── การแก้ไขบัตร ─────────────────────────────────────────────────

    public function test_homeroom_teacher_can_update_a_card_in_their_own_room(): void
    {
        $this->actingAs($this->homeroomTeacher, 'api')
            ->putJson($this->cardUrl("/{$this->cardInRoomA->id}"), ['first_name_thai' => 'ชื่อใหม่'])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSame('ชื่อใหม่', $this->studentInRoomA->fresh()->first_name_th);
    }

    public function test_homeroom_teacher_cannot_update_a_card_from_another_room(): void
    {
        $studentB = $this->makeStudent('STU-B1');
        $this->enroll($studentB, $this->roomB, 1);
        $cardB = $this->makeCard($studentB, '6', '2');

        $this->actingAs($this->homeroomTeacher, 'api')
            ->putJson($this->cardUrl("/{$cardB->id}"), ['first_name_thai' => 'ห้ามแก้'])
            ->assertStatus(403);

        $this->assertSame('ทดสอบ', $studentB->fresh()->first_name_th);
    }

    public function test_teacher_without_a_homeroom_cannot_update_any_card(): void
    {
        $plainTeacher = $this->makeUser('plain');
        $this->joinAcademy($plainTeacher, $this->makeRole('plain-local', ['students.view']));

        $this->actingAs($plainTeacher, 'api')
            ->putJson($this->cardUrl("/{$this->cardInRoomA->id}"), ['first_name_thai' => 'ห้ามแก้'])
            ->assertStatus(403);
    }

    public function test_manager_and_owner_can_update_any_card(): void
    {
        $this->actingAs($this->manager, 'api')
            ->putJson($this->cardUrl("/{$this->cardInRoomA->id}"), ['first_name_thai' => 'ผู้จัดการแก้'])
            ->assertStatus(200);

        $this->actingAs($this->owner, 'api')
            ->putJson($this->cardUrl("/{$this->cardInRoomA->id}"), ['first_name_thai' => 'เจ้าของแก้'])
            ->assertStatus(200);

        $this->assertSame('เจ้าของแก้', $this->studentInRoomA->fresh()->first_name_th);
    }

    public function test_homeroom_teacher_loses_access_once_the_student_leaves_the_room(): void
    {
        ClassroomStudent::where('student_id', $this->studentInRoomA->id)
            ->update(['status' => ClassroomStudent::STATUS_REMOVED]);

        $this->actingAs($this->homeroomTeacher, 'api')
            ->putJson($this->cardUrl("/{$this->cardInRoomA->id}"), ['first_name_thai' => 'ห้ามแก้'])
            ->assertStatus(403);
    }

    /**
     * ทุก route ที่เขียนข้อมูลบัตรต้องมีด่านตรวจ ไม่ใช่แค่ตัวที่นึกออก
     */
    public function test_every_card_write_route_is_guarded(): void
    {
        $id = $this->cardInRoomA->id;
        $writes = [
            ['putJson', $this->cardUrl("/{$id}"), ['first_name_thai' => 'x']],
            ['deleteJson', $this->cardUrl("/{$id}/photo"), []],
            ['postJson', $this->cardUrl("/admin/upload-photo/{$id}"), []],
            ['patchJson', $this->cardUrl("/admin/update-code/{$id}"), ['student_number' => 'HACK']],
            ['patchJson', $this->cardUrl("/admin/update-name-th/{$id}"), ['first_name_thai' => 'x']],
            ['patchJson', $this->cardUrl("/admin/update-name-en/{$id}"), ['first_name_english' => 'x']],
        ];

        foreach ($writes as [$method, $url, $payload]) {
            $this->actingAs($this->otherTeacher, 'api')
                ->{$method}($url, $payload)
                ->assertStatus(403, "route ไม่มีด่านตรวจ: {$method} {$url}");
        }

        $this->assertSame('STU-A1', $this->studentInRoomA->fresh()->student_id);
    }

    public function test_academy_wide_routes_still_require_students_manage(): void
    {
        $this->actingAs($this->homeroomTeacher, 'api')
            ->getJson($this->cardUrl('/admin/students'))
            ->assertStatus(403);

        $this->actingAs($this->homeroomTeacher, 'api')
            ->postJson($this->cardUrl('/admin/'), [])
            ->assertStatus(403);
    }

    /**
     * ก่อนแก้ /{level}/{room} กลืน /admin/students ทำให้โหมดรายชื่อของแอดมิน
     * วิ่งเข้า getStudentByRoom(level=admin, room=students) แล้วคืนรายการว่าง
     */
    public function test_admin_students_route_is_not_shadowed_by_the_room_route(): void
    {
        $this->actingAs($this->manager, 'api')
            ->getJson($this->cardUrl('/admin/students'))
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'students' => ['data', 'current_page']]);
    }

    // ── การจัดการรายชื่อในห้อง ────────────────────────────────────────

    public function test_context_reports_what_each_role_may_do(): void
    {
        $this->actingAs($this->homeroomTeacher, 'api')
            ->getJson($this->cardUrl('/6/1/context'))
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'can_manage_roster' => true,
                'can_edit_card' => true,
                'is_homeroom_teacher' => true,
                'classroom_id' => $this->roomA->id,
            ]);

        $this->actingAs($this->homeroomTeacher, 'api')
            ->getJson($this->cardUrl('/6/2/context'))
            ->assertStatus(200)
            ->assertJson([
                'can_manage_roster' => false,
                'is_homeroom_teacher' => false,
            ]);

        $this->actingAs($this->manager, 'api')
            ->getJson($this->cardUrl('/6/2/context'))
            ->assertStatus(200)
            ->assertJson(['can_manage_roster' => true, 'is_homeroom_teacher' => false]);
    }

    public function test_homeroom_teacher_can_add_transfer_and_remove_in_their_own_room(): void
    {
        $newStudent = $this->makeStudent('STU-NEW');

        $this->actingAs($this->homeroomTeacher, 'api')
            ->postJson($this->cardUrl('/6/1/students'), ['student_id' => $newStudent->id, 'student_number' => 12])
            ->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('classroom_students', [
            'classroom_id' => $this->roomA->id,
            'student_id' => $newStudent->id,
            'status' => ClassroomStudent::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->homeroomTeacher, 'api')
            ->postJson($this->cardUrl("/6/1/students/{$newStudent->id}/transfer"), [
                'to_classroom_id' => $this->roomB->id,
            ])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('classroom_students', [
            'classroom_id' => $this->roomB->id,
            'student_id' => $newStudent->id,
            'status' => ClassroomStudent::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->homeroomTeacher, 'api')
            ->deleteJson($this->cardUrl("/6/1/students/{$this->studentInRoomA->id}"), ['reason' => 'ย้ายโรงเรียน'])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSame('active', $this->studentInRoomA->fresh()->status);
    }

    /**
     * เคสจริงที่ต้องรองรับ: มีนักเรียน "แฝง" อยู่ในห้อง ครูประจำชั้นไม่รู้จักและ
     * ไม่รู้ว่าเจ้าตัวควรอยู่ห้องไหน — ต้องนำออกได้โดยไม่ต้องระบุห้องปลายทาง
     * แล้วครูประจำชั้นห้องที่ถูกต้องค้นหาเจอและดึงเข้าห้องตัวเองได้
     */
    public function test_a_student_removed_without_a_destination_can_be_picked_up_by_another_homeroom_teacher(): void
    {
        // ครูห้อง ม.6/1 นำนักเรียนที่แฝงเข้ามาออก โดยไม่ระบุห้องปลายทาง
        $this->actingAs($this->homeroomTeacher, 'api')
            ->deleteJson($this->cardUrl("/6/1/students/{$this->studentInRoomA->id}"), [
                'reason' => 'ไม่ใช่นักเรียนห้องนี้',
            ])
            ->assertStatus(200)
            ->assertJson(['success' => true, 'student_count' => 0]);

        // ยังเป็นนักเรียนของโรงเรียนอยู่ แค่ไม่มีห้อง
        $this->assertSame('active', $this->studentInRoomA->fresh()->status);
        $this->assertDatabaseHas('classroom_students', [
            'classroom_id' => $this->roomA->id,
            'student_id' => $this->studentInRoomA->id,
            'status' => ClassroomStudent::STATUS_REMOVED,
        ]);

        // ครูห้อง ม.6/2 ค้นเจอ และเห็นว่ายังไม่มีห้อง
        $byName = $this->actingAs($this->otherTeacher, 'api')
            ->getJson($this->cardUrl('/6/2/available-students?search=ทดสอบ'))
            ->assertStatus(200)
            ->json('students');

        $found = collect($byName)->firstWhere('id', $this->studentInRoomA->id);
        $this->assertNotNull($found, 'ค้นด้วยชื่อแล้วต้องเจอนักเรียนที่ยังไม่มีห้อง');
        $this->assertSame('unassigned', $found['enrollment_status']);

        // ค้นด้วยรหัสนักเรียนก็ต้องเจอเหมือนกัน
        $byCode = $this->actingAs($this->otherTeacher, 'api')
            ->getJson($this->cardUrl('/6/2/available-students?search=STU-A1'))
            ->assertStatus(200)
            ->json('students');
        $this->assertSame($this->studentInRoomA->id, collect($byCode)->firstWhere('id', $this->studentInRoomA->id)['id']);

        // แล้วดึงเข้าห้องตัวเองได้
        $this->actingAs($this->otherTeacher, 'api')
            ->postJson($this->cardUrl('/6/2/students'), [
                'student_id' => $this->studentInRoomA->id,
                'student_number' => 7,
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('classroom_students', [
            'classroom_id' => $this->roomB->id,
            'student_id' => $this->studentInRoomA->id,
            'student_number' => 7,
            'status' => ClassroomStudent::STATUS_ACTIVE,
        ]);
    }

    /**
     * ครั้งแรกที่ยังไม่ยืนยัน ต้องบอกว่านักเรียนอยู่ห้องไหน เพื่อให้หน้าจอถามก่อน
     * ไม่ใช่ย้ายห้องของครูอีกคนแบบเงียบ ๆ
     */
    public function test_pulling_a_student_from_another_room_asks_for_confirmation_first(): void
    {
        $response = $this->actingAs($this->otherTeacher, 'api')
            ->postJson($this->cardUrl('/6/2/students'), ['student_id' => $this->studentInRoomA->id])
            ->assertStatus(422);

        $this->assertSame('in_other_room', $response->json('error'));
        $this->assertSame($this->roomA->id, $response->json('current_classroom_id'));
        $this->assertSame($this->roomA->display_name, $response->json('current_classroom_name'));

        // ยังอยู่ห้องเดิม ไม่มีอะไรถูกแตะ
        $this->assertDatabaseHas('classroom_students', [
            'classroom_id' => $this->roomA->id,
            'student_id' => $this->studentInRoomA->id,
            'status' => ClassroomStudent::STATUS_ACTIVE,
        ]);
    }

    /**
     * เมื่อยืนยันแล้ว ครูประจำชั้นดึงนักเรียนข้ามห้องเข้ามาได้เลย ไม่ต้องรอ
     * ครูห้องเดิมนำออกก่อน — ห้องเดิมถูกปิดเป็น transferred ให้อัตโนมัติ
     */
    public function test_confirmed_cross_room_pull_moves_the_student_in_one_step(): void
    {
        $response = $this->actingAs($this->otherTeacher, 'api')
            ->postJson($this->cardUrl('/6/2/students'), [
                'student_id' => $this->studentInRoomA->id,
                'student_number' => 4,
                'confirm_transfer' => true,
            ])
            ->assertStatus(201)
            ->assertJson([
                'success' => true,
                'moved_from_classroom_id' => $this->roomA->id,
            ]);

        $this->assertStringContainsString('ย้าย', $response->json('message'));
        $this->assertStringContainsString($this->roomA->display_name, $response->json('message'));

        $this->assertDatabaseHas('classroom_students', [
            'classroom_id' => $this->roomB->id,
            'student_id' => $this->studentInRoomA->id,
            'student_number' => 4,
            'status' => ClassroomStudent::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseHas('classroom_students', [
            'classroom_id' => $this->roomA->id,
            'student_id' => $this->studentInRoomA->id,
            'status' => ClassroomStudent::STATUS_TRANSFERRED,
        ]);

        // ต้องเหลือแถว active แถวเดียวเสมอ
        $this->assertSame(1, ClassroomStudent::where('student_id', $this->studentInRoomA->id)
            ->where('status', ClassroomStudent::STATUS_ACTIVE)->count());
    }

    public function test_confirmed_pull_still_respects_duplicate_student_number(): void
    {
        $occupier = $this->makeStudent('STU-OCC');
        $this->enroll($occupier, $this->roomB, 4);

        $response = $this->actingAs($this->otherTeacher, 'api')
            ->postJson($this->cardUrl('/6/2/students'), [
                'student_id' => $this->studentInRoomA->id,
                'student_number' => 4,
                'confirm_transfer' => true,
            ])
            ->assertStatus(422);

        $this->assertSame('duplicate_student_number', $response->json('error'));
        $this->assertDatabaseHas('classroom_students', [
            'classroom_id' => $this->roomA->id,
            'student_id' => $this->studentInRoomA->id,
            'status' => ClassroomStudent::STATUS_ACTIVE,
        ]);
    }

    /**
     * ยืนยันแล้วก็ยังดึงเข้าห้องที่ตัวเองไม่ได้ดูแลไม่ได้ — confirm_transfer
     * ปลดล็อกเรื่อง "นักเรียนอยู่ห้องอื่น" เท่านั้น ไม่ได้ปลดล็อกเรื่องสิทธิ์
     */
    public function test_confirm_transfer_does_not_bypass_the_permission_check(): void
    {
        $newStudent = $this->makeStudent('STU-Y');

        $this->actingAs($this->homeroomTeacher, 'api')
            ->postJson($this->cardUrl('/6/2/students'), [
                'student_id' => $newStudent->id,
                'confirm_transfer' => true,
            ])
            ->assertStatus(403);
    }

    public function test_homeroom_teacher_cannot_touch_another_rooms_roster(): void
    {
        $newStudent = $this->makeStudent('STU-X');
        $studentB = $this->makeStudent('STU-B2');
        $this->enroll($studentB, $this->roomB, 5);

        $this->actingAs($this->homeroomTeacher, 'api')
            ->getJson($this->cardUrl('/6/2/available-students?search=ทดสอบ'))
            ->assertStatus(403);

        $this->actingAs($this->homeroomTeacher, 'api')
            ->postJson($this->cardUrl('/6/2/students'), ['student_id' => $newStudent->id])
            ->assertStatus(403);

        $this->actingAs($this->homeroomTeacher, 'api')
            ->deleteJson($this->cardUrl("/6/2/students/{$studentB->id}"), [])
            ->assertStatus(403);

        $this->assertDatabaseHas('classroom_students', [
            'student_id' => $studentB->id,
            'status' => ClassroomStudent::STATUS_ACTIVE,
        ]);
    }

    public function test_roster_routes_reject_rooms_from_another_academy(): void
    {
        $otherAcademy = Academy::create([
            'name' => 'Other_'.uniqid(),
            'user_id' => $this->makeUser('other-owner')->id,
        ]);

        $this->actingAs($this->manager, 'api')
            ->getJson("/api/academies/{$otherAcademy->id}/student-cards/6/1/context")
            ->assertStatus(403);
    }
}
