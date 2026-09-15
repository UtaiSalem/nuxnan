<?php

namespace Tests\Feature\Academy;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CL-S5 — เมนู #10 ห้องเรียน: happy-path ฟีเจอร์ที่ยังไม่มีเทสต์เดิม
 * (roster/transfer/renumber มี ClassroomManagementTest + ClassroomRenumberTest ครอบแล้ว)
 * รอบนี้เก็บ: ClassroomGroup CRUD · ClassroomInvitation · promoteClassroom
 */
class ClassroomFeaturesHappyPathTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $tag = ''): User
    {
        return User::create([
            'name' => 'U'.$tag,
            'email' => 'u'.$tag.uniqid().'@x.test',
            'password' => bcrypt('x'),
            'username' => 'u'.$tag.uniqid(),
            'reference_code' => 'R'.uniqid(),
            'personal_code' => 'P'.uniqid(),
        ]);
    }

    /** @return array{0: Academy, 1: User, 2: AcademicYear} */
    private function makeAcademy(): array
    {
        $owner = $this->makeUser('own');
        $academy = Academy::create([
            'name' => 'TestAcademy_'.uniqid(),
            'user_id' => $owner->id,
        ]);
        $year = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2569',
            'is_current' => true,
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ]);

        return [$academy, $owner, $year];
    }

    private function makeClassroom(Academy $academy, AcademicYear $year, string $level = 'ม.1', string $section = '1'): Classroom
    {
        return Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'grade_level' => $level,
            'section' => $section,
            'name' => $level.'/'.$section,
        ]);
    }

    private function makeStudent(Academy $academy, string $code): Student
    {
        return Student::create([
            'academy_id' => $academy->id,
            'user_id' => $this->makeUser('stu')->id,
            'student_id' => $code,
            'first_name_th' => 'สมปอง',
            'last_name_th' => 'ใจดี'.uniqid(),
            'status' => 'active',
        ]);
    }

    private function enroll(Student $student, Classroom $classroom, int $number): ClassroomStudent
    {
        return ClassroomStudent::create([
            'academy_id' => $classroom->academy_id,
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $classroom->academic_year_id,
            'status' => ClassroomStudent::STATUS_ACTIVE,
            'student_number' => $number,
        ]);
    }

    public function test_owner_can_crud_classroom_group(): void
    {
        [$academy, $owner, $year] = $this->makeAcademy();
        $classroom = $this->makeClassroom($academy, $year);

        $create = $this->actingAs($owner, 'api')->postJson(
            "/api/academies/{$academy->id}/classrooms/{$classroom->id}/groups",
            ['group_name' => 'กลุ่ม A', 'group_type' => 'static']
        );
        $create->assertCreated()->assertJsonPath('data.group_name', 'กลุ่ม A');
        $groupId = $create->json('data.id');

        $this->actingAs($owner, 'api')->getJson(
            "/api/academies/{$academy->id}/classrooms/{$classroom->id}/groups"
        )->assertOk()->assertJsonFragment(['group_name' => 'กลุ่ม A']);

        $this->actingAs($owner, 'api')->patchJson(
            "/api/academies/{$academy->id}/classrooms/{$classroom->id}/groups/{$groupId}",
            ['group_name' => 'กลุ่ม B']
        )->assertOk()->assertJsonPath('data.group_name', 'กลุ่ม B');

        $this->actingAs($owner, 'api')->deleteJson(
            "/api/academies/{$academy->id}/classrooms/{$classroom->id}/groups/{$groupId}"
        )->assertOk();

        $this->assertDatabaseMissing('classroom_groups', ['id' => $groupId]);
    }

    public function test_owner_can_create_and_cancel_invitation(): void
    {
        [$academy, $owner, $year] = $this->makeAcademy();
        $classroom = $this->makeClassroom($academy, $year);

        $create = $this->actingAs($owner, 'api')->postJson(
            "/api/academies/{$academy->id}/classrooms/{$classroom->id}/invitations",
            ['invited_email' => 'teacher@x.test', 'role_to_assign' => 'teacher']
        );
        $create->assertCreated();
        $invitationId = $create->json('data.id');
        $this->assertNotNull($invitationId);

        $this->actingAs($owner, 'api')->deleteJson(
            "/api/academies/{$academy->id}/classrooms/{$classroom->id}/invitations/{$invitationId}"
        )->assertOk();
    }

    public function test_owner_can_promote_classroom(): void
    {
        [$academy, $owner, $year] = $this->makeAcademy();

        // promoteClassroom เป็นการเลื่อนชั้น "ข้ามปีการศึกษา" (ปีเดียวกันใช้ transferStudent)
        $nextYear = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2570',
            'is_current' => false,
            'start_date' => '2027-05-16',
            'end_date' => '2028-03-31',
        ]);

        $from = $this->makeClassroom($academy, $year, 'ม.1', '1');
        $to = $this->makeClassroom($academy, $nextYear, 'ม.2', '1');

        $s1 = $this->makeStudent($academy, 'STU1');
        $s2 = $this->makeStudent($academy, 'STU2');
        $this->enroll($s1, $from, 1);
        $this->enroll($s2, $from, 2);

        $response = $this->actingAs($owner, 'api')->postJson(
            "/api/academies/{$academy->id}/classrooms/promote",
            ['from_classroom_id' => $from->id, 'to_classroom_id' => $to->id]
        );

        $response->assertOk()->assertJsonPath('meta.promoted', 2);

        $this->assertDatabaseHas('classroom_students', [
            'student_id' => $s1->id,
            'classroom_id' => $to->id,
            'status' => ClassroomStudent::STATUS_ACTIVE,
        ]);
    }

    /**
     * ตัวกรอง ?classroom_id= ของ getAllStudents — เดิมเรียก relation ผิดชื่อ
     * (`classroomStudents` ที่ไม่มีบน Student) ⇒ 500 · ไม่มีใครเรียกเลยไม่มีใครเห็น
     * จนกระทั่ง CL-S3 ทำให้หน้ารายการห้องเรียนเริ่มใช้ตัวกรองนี้จริง
     */
    public function test_get_all_students_can_filter_by_classroom_id(): void
    {
        [$academy, $owner, $year] = $this->makeAcademy();
        $roomA = $this->makeClassroom($academy, $year, 'ม.1', '1');
        $roomB = $this->makeClassroom($academy, $year, 'ม.1', '2');

        $inA = $this->makeStudent($academy, 'INA');
        $inB = $this->makeStudent($academy, 'INB');
        $this->enroll($inA, $roomA, 1);
        $this->enroll($inB, $roomB, 1);

        $response = $this->actingAs($owner, 'api')->getJson(
            "/api/academies/{$academy->id}/classrooms/students?classroom_id={$roomA->id}&per_page=200"
        );

        $response->assertOk();

        $ids = collect($response->json('students'))->pluck('id')->all();
        $this->assertContains($inA->id, $ids);
        $this->assertNotContains($inB->id, $ids);
    }
}
