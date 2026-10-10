<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['student-card.public_management' => true]);
    }

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

    private function makeAcademy(): array
    {
        $admin = $this->makeUser('adm');

        $academy = Academy::create([
            'name' => 'TestAcademy_'.uniqid(),
            'user_id' => $admin->id,
        ]);

        $year = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2569',
            'is_current' => true,
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ]);

        return [$academy, $year];
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

    private function makeStudent(Academy $academy, string $code = 'STU1001'): Student
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

    private function enroll(Student $student, Classroom $classroom, int $number = 1): ClassroomStudent
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

    // ── manage-context ──

    public function test_manage_context_returns_classroom_info(): void
    {
        [$academy, $year] = $this->makeAcademy();
        $classroom = $this->makeClassroom($academy, $year);
        $this->makeClassroom($academy, $year, 'ม.1', '2');

        $response = $this->getJson('/api/student-card/1/1/manage-context');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'can_manage' => true,
                'classroom_id' => $classroom->id,
                'academy_id' => $academy->id,
            ]);

        $this->assertCount(1, $response->json('available_classrooms'));
    }

    public function test_manage_context_reports_disabled_when_config_off(): void
    {
        config(['student-card.public_management' => false]);
        [$academy, $year] = $this->makeAcademy();
        $this->makeClassroom($academy, $year);

        $response = $this->getJson('/api/student-card/1/1/manage-context');

        $response->assertOk()->assertJson(['can_manage' => false]);
    }

    public function test_unknown_room_returns_404(): void
    {
        [$academy, $year] = $this->makeAcademy();
        $this->makeClassroom($academy, $year);

        $this->getJson('/api/student-card/3/9/manage-context')->assertNotFound();
    }

    public function test_ambiguous_room_across_academies_returns_409(): void
    {
        [$academyA, $yearA] = $this->makeAcademy();
        $this->makeClassroom($academyA, $yearA);
        [$academyB, $yearB] = $this->makeAcademy();
        $this->makeClassroom($academyB, $yearB);

        $this->getJson('/api/student-card/1/1/manage-context')->assertStatus(409);
    }

    // ── add student ──

    public function test_add_unassigned_student_succeeds(): void
    {
        [$academy, $year] = $this->makeAcademy();
        $classroom = $this->makeClassroom($academy, $year);
        $student = $this->makeStudent($academy);

        $response = $this->postJson('/api/student-card/1/1/students', [
            'student_id' => $student->id,
            'student_number' => 5,
        ]);

        $response->assertCreated()->assertJson(['success' => true, 'student_count' => 1]);

        $this->assertDatabaseHas('classroom_students', [
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'status' => 'active',
            'student_number' => 5,
        ]);
    }

    public function test_add_student_in_other_room_is_rejected(): void
    {
        [$academy, $year] = $this->makeAcademy();
        $roomA = $this->makeClassroom($academy, $year, 'ม.1', '1');
        $roomB = $this->makeClassroom($academy, $year, 'ม.1', '2');
        $student = $this->makeStudent($academy);
        $this->enroll($student, $roomB);

        $response = $this->postJson('/api/student-card/1/1/students', [
            'student_id' => $student->id,
        ]);

        $response->assertUnprocessable()->assertJson(['error' => 'in_other_room']);
    }

    public function test_add_student_already_in_room_is_rejected(): void
    {
        [$academy, $year] = $this->makeAcademy();
        $classroom = $this->makeClassroom($academy, $year);
        $student = $this->makeStudent($academy);
        $this->enroll($student, $classroom);

        $response = $this->postJson('/api/student-card/1/1/students', [
            'student_id' => $student->id,
        ]);

        $response->assertUnprocessable()->assertJson(['error' => 'already_in_room']);
    }

    public function test_add_student_from_other_academy_is_rejected(): void
    {
        [$academy, $year] = $this->makeAcademy();
        $this->makeClassroom($academy, $year);

        $otherAdmin = $this->makeUser('other');
        $otherAcademy = Academy::create(['name' => 'Other_'.uniqid(), 'user_id' => $otherAdmin->id]);
        $outsider = $this->makeStudent($otherAcademy, 'STU9999');

        $response = $this->postJson('/api/student-card/1/1/students', [
            'student_id' => $outsider->id,
        ]);

        $response->assertUnprocessable()->assertJson(['error' => 'different_academy']);
    }

    public function test_add_student_with_duplicate_number_is_rejected(): void
    {
        [$academy, $year] = $this->makeAcademy();
        $classroom = $this->makeClassroom($academy, $year);
        $existing = $this->makeStudent($academy, 'STU1001');
        $this->enroll($existing, $classroom, 5);
        $newcomer = $this->makeStudent($academy, 'STU1002');

        $response = $this->postJson('/api/student-card/1/1/students', [
            'student_id' => $newcomer->id,
            'student_number' => 5,
        ]);

        $response->assertUnprocessable()->assertJson(['error' => 'duplicate_student_number']);
    }

    public function test_add_student_when_management_disabled_returns_403(): void
    {
        config(['student-card.public_management' => false]);
        [$academy, $year] = $this->makeAcademy();
        $this->makeClassroom($academy, $year);
        $student = $this->makeStudent($academy);

        $this->postJson('/api/student-card/1/1/students', [
            'student_id' => $student->id,
        ])->assertForbidden();
    }

    // ── transfer ──

    public function test_transfer_within_same_year_succeeds(): void
    {
        [$academy, $year] = $this->makeAcademy();
        $roomA = $this->makeClassroom($academy, $year, 'ม.1', '1');
        $roomB = $this->makeClassroom($academy, $year, 'ม.1', '2');
        $student = $this->makeStudent($academy);
        $this->enroll($student, $roomA);

        $response = $this->postJson("/api/student-card/1/1/students/{$student->id}/transfer", [
            'to_classroom_id' => $roomB->id,
            'reason' => 'ปรับสมดุลจำนวนนักเรียน',
        ]);

        $response->assertOk()->assertJson(['success' => true, 'student_count' => 0]);

        $this->assertDatabaseHas('classroom_students', [
            'student_id' => $student->id,
            'classroom_id' => $roomA->id,
            'status' => 'transferred',
        ]);
        $this->assertDatabaseHas('classroom_students', [
            'student_id' => $student->id,
            'classroom_id' => $roomB->id,
            'status' => 'active',
        ]);
    }

    public function test_transfer_across_years_is_rejected(): void
    {
        [$academy, $year] = $this->makeAcademy();
        $roomA = $this->makeClassroom($academy, $year, 'ม.1', '1');
        $oldYear = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2568',
            'is_current' => false,
            'start_date' => '2025-05-16',
            'end_date' => '2026-03-31',
        ]);
        $oldRoom = $this->makeClassroom($academy, $oldYear, 'ม.1', '3');
        $student = $this->makeStudent($academy);
        $this->enroll($student, $roomA);

        $response = $this->postJson("/api/student-card/1/1/students/{$student->id}/transfer", [
            'to_classroom_id' => $oldRoom->id,
        ]);

        $response->assertUnprocessable()->assertJson(['error' => 'different_academic_year']);
    }

    public function test_transfer_to_same_room_is_rejected(): void
    {
        [$academy, $year] = $this->makeAcademy();
        $classroom = $this->makeClassroom($academy, $year);
        $student = $this->makeStudent($academy);
        $this->enroll($student, $classroom);

        $response = $this->postJson("/api/student-card/1/1/students/{$student->id}/transfer", [
            'to_classroom_id' => $classroom->id,
        ]);

        $response->assertUnprocessable()->assertJson(['error' => 'same_classroom']);
    }

    public function test_transfer_student_not_in_room_is_rejected(): void
    {
        [$academy, $year] = $this->makeAcademy();
        $this->makeClassroom($academy, $year, 'ม.1', '1');
        $roomB = $this->makeClassroom($academy, $year, 'ม.1', '2');
        $student = $this->makeStudent($academy);

        $response = $this->postJson("/api/student-card/1/1/students/{$student->id}/transfer", [
            'to_classroom_id' => $roomB->id,
        ]);

        $response->assertUnprocessable()->assertJson(['error' => 'not_in_room']);
    }

    public function test_transfer_keeps_card_active(): void
    {
        [$academy, $year] = $this->makeAcademy();
        $roomA = $this->makeClassroom($academy, $year, 'ม.1', '1');
        $roomB = $this->makeClassroom($academy, $year, 'ม.1', '2');
        $student = $this->makeStudent($academy);
        $this->enroll($student, $roomA);

        $card = StudentCard::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'student_number' => $student->student_id,
            'full_name_thai' => 'เด็กชาย สมปอง ใจดี',
            'class_level' => '1',
            'class_section' => '1',
            'student_status' => 'active',
        ]);

        $this->postJson("/api/student-card/1/1/students/{$student->id}/transfer", [
            'to_classroom_id' => $roomB->id,
        ])->assertOk();

        $this->assertSame('active', $card->fresh()->student_status);
    }

    // ── remove ──

    public function test_remove_keeps_student_active_and_preserves_history(): void
    {
        [$academy, $year] = $this->makeAcademy();
        $classroom = $this->makeClassroom($academy, $year);
        $student = $this->makeStudent($academy);
        $enrollment = $this->enroll($student, $classroom);

        $response = $this->deleteJson("/api/student-card/1/1/students/{$student->id}", [
            'reason' => 'จัดห้องใหม่',
        ]);

        $response->assertOk()->assertJson(['success' => true, 'student_count' => 0]);

        $enrollment->refresh();
        $this->assertSame(ClassroomStudent::STATUS_REMOVED, $enrollment->status);
        $this->assertSame('จัดห้องใหม่', $enrollment->leave_reason);
        $this->assertNotNull($enrollment->left_at);

        $student->refresh();
        $this->assertSame('active', $student->status);
        $this->assertNull($student->class_level);
        $this->assertNull($student->class_section);
    }

    public function test_remove_student_not_in_room_is_rejected(): void
    {
        [$academy, $year] = $this->makeAcademy();
        $this->makeClassroom($academy, $year);
        $student = $this->makeStudent($academy);

        $this->deleteJson("/api/student-card/1/1/students/{$student->id}")
            ->assertUnprocessable()
            ->assertJson(['error' => 'not_in_room']);
    }

    public function test_removed_student_can_be_added_back_and_card_reactivates(): void
    {
        [$academy, $year] = $this->makeAcademy();
        $classroom = $this->makeClassroom($academy, $year);
        $student = $this->makeStudent($academy);
        $this->enroll($student, $classroom);

        $card = StudentCard::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'student_number' => $student->student_id,
            'full_name_thai' => 'เด็กชาย สมปอง ใจดี',
            'class_level' => '1',
            'class_section' => '1',
            'student_status' => 'active',
        ]);

        $this->deleteJson("/api/student-card/1/1/students/{$student->id}")->assertOk();
        $this->assertSame('expired', $card->fresh()->student_status);

        $this->postJson('/api/student-card/1/1/students', [
            'student_id' => $student->id,
        ])->assertCreated();

        $this->assertSame('active', $card->fresh()->student_status);
        $this->assertDatabaseHas('classroom_students', [
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'status' => 'active',
        ]);
    }

    // ── available students ──

    public function test_available_students_reports_enrollment_status(): void
    {
        [$academy, $year] = $this->makeAcademy();
        $classroom = $this->makeClassroom($academy, $year);
        $roomB = $this->makeClassroom($academy, $year, 'ม.1', '2');

        $unassigned = $this->makeStudent($academy, 'STU2001');
        $inThisRoom = $this->makeStudent($academy, 'STU2002');
        $this->enroll($inThisRoom, $classroom);
        $inOtherRoom = $this->makeStudent($academy, 'STU2003');
        $this->enroll($inOtherRoom, $roomB);

        $response = $this->getJson('/api/student-card/1/1/available-students?search=สมปอง');

        $response->assertOk();
        $students = collect($response->json('students'));

        $this->assertSame('unassigned', $students->firstWhere('id', $unassigned->id)['enrollment_status']);
        $this->assertSame('already_in_room', $students->firstWhere('id', $inThisRoom->id)['enrollment_status']);
        $this->assertSame('in_other_room', $students->firstWhere('id', $inOtherRoom->id)['enrollment_status']);
    }
}
