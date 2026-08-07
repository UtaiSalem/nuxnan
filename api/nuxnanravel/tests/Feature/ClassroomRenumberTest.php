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

class ClassroomRenumberTest extends TestCase
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

        return [$academy, $year, $admin];
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

    private function enroll(Student $student, Classroom $classroom, int $number = 1, string $status = ClassroomStudent::STATUS_ACTIVE): ClassroomStudent
    {
        return ClassroomStudent::create([
            'academy_id' => $classroom->academy_id,
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $classroom->academic_year_id,
            'status' => $status,
            'student_number' => $number,
        ]);
    }

    private function makeCard(Student $student, Classroom $classroom, int $number = 1): StudentCard
    {
        return StudentCard::create([
            'academy_id' => $classroom->academy_id,
            'student_id' => $student->id,
            'academic_year_id' => $classroom->academic_year_id,
            'student_number' => $student->student_id,
            'full_name_thai' => 'สมปอง ใจดี',
            'class_level' => '1',
            'class_section' => '1',
            'student_status' => 'active',
            'order_no' => $number,
        ]);
    }

    public function test_renumber_orders_by_numeric_student_code(): void
    {
        [$academy, $year, $admin] = $this->makeAcademy();
        $classroom = $this->makeClassroom($academy, $year);

        $s1 = $this->makeStudent($academy, '11149');
        $this->enroll($s1, $classroom, 1);

        $s2 = $this->makeStudent($academy, '6436');
        $this->enroll($s2, $classroom, 2);

        $s3 = $this->makeStudent($academy, '7887');
        $this->enroll($s3, $classroom, 3);

        $response = $this->actingAs($admin, 'api')
            ->postJson("/api/academies/{$academy->id}/classrooms/{$classroom->id}/renumber");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'dry_run' => false,
            ]);

        $preview = collect($response->json('preview'));

        $this->assertSame(1, $preview->firstWhere('student_code', '6436')['to']);
        $this->assertSame(2, $preview->firstWhere('student_code', '7887')['to']);
        $this->assertSame(3, $preview->firstWhere('student_code', '11149')['to']);

        $this->assertDatabaseHas('classroom_students', [
            'student_id' => $s2->id,
            'student_number' => 1,
        ]);
        $this->assertDatabaseHas('classroom_students', [
            'student_id' => $s3->id,
            'student_number' => 2,
        ]);
        $this->assertDatabaseHas('classroom_students', [
            'student_id' => $s1->id,
            'student_number' => 3,
        ]);
    }

    public function test_renumber_closes_gaps(): void
    {
        [$academy, $year, $admin] = $this->makeAcademy();
        $classroom = $this->makeClassroom($academy, $year);

        $s1 = $this->makeStudent($academy, '1');
        $this->enroll($s1, $classroom, 1);

        $s2 = $this->makeStudent($academy, '2');
        $this->enroll($s2, $classroom, 2);

        $s3 = $this->makeStudent($academy, '3');
        $this->enroll($s3, $classroom, 4);

        $s4 = $this->makeStudent($academy, '4');
        $this->enroll($s4, $classroom, 9);

        $response = $this->actingAs($admin, 'api')
            ->postJson("/api/academies/{$academy->id}/classrooms/{$classroom->id}/renumber");

        $response->assertOk();

        $this->assertDatabaseHas('classroom_students', ['student_id' => $s1->id, 'student_number' => 1]);
        $this->assertDatabaseHas('classroom_students', ['student_id' => $s2->id, 'student_number' => 2]);
        $this->assertDatabaseHas('classroom_students', ['student_id' => $s3->id, 'student_number' => 3]);
        $this->assertDatabaseHas('classroom_students', ['student_id' => $s4->id, 'student_number' => 4]);
    }

    public function test_dry_run_writes_nothing(): void
    {
        [$academy, $year, $admin] = $this->makeAcademy();
        $classroom = $this->makeClassroom($academy, $year);

        $s1 = $this->makeStudent($academy, '11149');
        $this->enroll($s1, $classroom, 1);

        $s2 = $this->makeStudent($academy, '6436');
        $this->enroll($s2, $classroom, 2);

        $response = $this->actingAs($admin, 'api')
            ->postJson("/api/academies/{$academy->id}/classrooms/{$classroom->id}/renumber", [
                'dry_run' => true,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'dry_run' => true,
            ]);

        $preview = collect($response->json('preview'));
        $this->assertCount(2, $preview);

        $this->assertDatabaseHas('classroom_students', ['student_id' => $s1->id, 'student_number' => 1]);
        $this->assertDatabaseHas('classroom_students', ['student_id' => $s2->id, 'student_number' => 2]);
    }

    public function test_non_active_enrollments_are_untouched(): void
    {
        [$academy, $year, $admin] = $this->makeAcademy();
        $classroom = $this->makeClassroom($academy, $year);

        $s1 = $this->makeStudent($academy, '1');
        $this->enroll($s1, $classroom, 1, 'transferred');

        $s2 = $this->makeStudent($academy, '2');
        $this->enroll($s2, $classroom, 2);

        $response = $this->actingAs($admin, 'api')
            ->postJson("/api/academies/{$academy->id}/classrooms/{$classroom->id}/renumber");

        $response->assertOk();

        $preview = collect($response->json('preview'));
        $this->assertCount(1, $preview);
        $this->assertSame('2', $preview->first()['student_code']);

        $this->assertDatabaseHas('classroom_students', ['student_id' => $s1->id, 'student_number' => 1]);
        $this->assertDatabaseHas('classroom_students', ['student_id' => $s2->id, 'student_number' => 1]);
    }

    public function test_student_card_order_no_follows_the_new_number(): void
    {
        [$academy, $year, $admin] = $this->makeAcademy();
        $classroom = $this->makeClassroom($academy, $year);

        $s1 = $this->makeStudent($academy, '11149');
        $this->enroll($s1, $classroom, 1);
        $card1 = $this->makeCard($s1, $classroom, 1);

        $s2 = $this->makeStudent($academy, '6436');
        $this->enroll($s2, $classroom, 2);
        $card2 = $this->makeCard($s2, $classroom, 2);

        $response = $this->actingAs($admin, 'api')
            ->postJson("/api/academies/{$academy->id}/classrooms/{$classroom->id}/renumber");

        $response->assertOk();

        $this->assertDatabaseHas('student_cards', [
            'id' => $card1->id,
            'order_no' => 2,
            'student_number' => '11149',
        ]);

        $this->assertDatabaseHas('student_cards', [
            'id' => $card2->id,
            'order_no' => 1,
            'student_number' => '6436',
        ]);
    }

    public function test_renumber_requires_manage_permission(): void
    {
        [$academy, $year, $admin] = $this->makeAcademy();
        $classroom = $this->makeClassroom($academy, $year);

        $s1 = $this->makeStudent($academy, '11149');
        $this->enroll($s1, $classroom, 1);

        $stranger = $this->makeUser('stranger');

        $response = $this->actingAs($stranger, 'api')
            ->postJson("/api/academies/{$academy->id}/classrooms/{$classroom->id}/renumber");

        $response->assertForbidden();
    }

    public function test_bulk_renumber_fixes_every_classroom_in_one_call(): void
    {
        [$academy, $year, $admin] = $this->makeAcademy();
        $room1 = $this->makeClassroom($academy, $year, 'ม.1', '1');
        $room2 = $this->makeClassroom($academy, $year, 'ม.1', '2');

        $s1 = $this->makeStudent($academy, '111');
        $this->enroll($s1, $room1, 1);

        $s2 = $this->makeStudent($academy, '110');
        $this->enroll($s2, $room1, 2);

        $s3 = $this->makeStudent($academy, '113');
        $this->enroll($s3, $room2, 5);

        $response = $this->actingAs($admin, 'api')
            ->postJson("/api/academies/{$academy->id}/classrooms/renumber");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'affected_classroom_count' => 2,
            ]);

        $classrooms = collect($response->json('classrooms'));
        $this->assertCount(2, $classrooms);

        $c1 = $classrooms->firstWhere('classroom_id', $room1->id);
        $this->assertSame(2, $c1['changed_count']);

        $c2 = $classrooms->firstWhere('classroom_id', $room2->id);
        $this->assertSame(1, $c2['changed_count']);

        $this->assertDatabaseHas('classroom_students', ['student_id' => $s2->id, 'student_number' => 1]);
        $this->assertDatabaseHas('classroom_students', ['student_id' => $s1->id, 'student_number' => 2]);
        $this->assertDatabaseHas('classroom_students', ['student_id' => $s3->id, 'student_number' => 1]);
    }

    public function test_bulk_dry_run_writes_nothing(): void
    {
        [$academy, $year, $admin] = $this->makeAcademy();
        $room = $this->makeClassroom($academy, $year);

        $s1 = $this->makeStudent($academy, '111');
        $this->enroll($s1, $room, 2);

        $response = $this->actingAs($admin, 'api')
            ->postJson("/api/academies/{$academy->id}/classrooms/renumber", ['dry_run' => true]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'dry_run' => true,
                'changed_count' => 1,
            ]);

        $this->assertDatabaseHas('classroom_students', ['student_id' => $s1->id, 'student_number' => 2]);
    }

    public function test_bulk_skips_empty_classrooms(): void
    {
        [$academy, $year, $admin] = $this->makeAcademy();
        $room = $this->makeClassroom($academy, $year); // empty
        $room2 = $this->makeClassroom($academy, $year, 'ม.1', '2');

        $s1 = $this->makeStudent($academy, '111');
        $this->enroll($s1, $room2, 1);

        $response = $this->actingAs($admin, 'api')
            ->postJson("/api/academies/{$academy->id}/classrooms/renumber");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'classroom_count' => 1,
            ]);

        $classrooms = collect($response->json('classrooms'));
        $this->assertCount(1, $classrooms);
        $this->assertSame($room2->id, $classrooms->first()['classroom_id']);
    }

    public function test_bulk_respects_grade_level_filter(): void
    {
        [$academy, $year, $admin] = $this->makeAcademy();
        $room1 = $this->makeClassroom($academy, $year, 'ม.1', '1');
        $room2 = $this->makeClassroom($academy, $year, 'ม.2', '1');

        $s1 = $this->makeStudent($academy, '111');
        $this->enroll($s1, $room1, 2);

        $s2 = $this->makeStudent($academy, '112');
        $this->enroll($s2, $room2, 2);

        $response = $this->actingAs($admin, 'api')
            ->postJson("/api/academies/{$academy->id}/classrooms/renumber", ['grade_level' => 'ม.1']);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'classroom_count' => 1,
            ]);

        $classrooms = collect($response->json('classrooms'));
        $this->assertSame($room1->id, $classrooms->first()['classroom_id']);

        $this->assertDatabaseHas('classroom_students', ['student_id' => $s1->id, 'student_number' => 1]);
        $this->assertDatabaseHas('classroom_students', ['student_id' => $s2->id, 'student_number' => 2]);
    }

    public function test_bulk_only_touches_the_resolved_academic_year(): void
    {
        [$academy, $year, $admin] = $this->makeAcademy();

        $pastYear = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2568',
            'is_current' => false,
            'start_date' => '2025-05-16',
            'end_date' => '2026-03-31',
        ]);

        $roomCurrent = $this->makeClassroom($academy, $year);
        $roomPast = $this->makeClassroom($academy, $pastYear);

        $s1 = $this->makeStudent($academy, '111');
        $this->enroll($s1, $roomCurrent, 2);

        $s2 = $this->makeStudent($academy, '112');
        $this->enroll($s2, $roomPast, 2);

        $response = $this->actingAs($admin, 'api')
            ->postJson("/api/academies/{$academy->id}/classrooms/renumber");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'academic_year_id' => $year->id,
            ]);

        $this->assertDatabaseHas('classroom_students', ['student_id' => $s1->id, 'student_number' => 1]);
        $this->assertDatabaseHas('classroom_students', ['student_id' => $s2->id, 'student_number' => 2]);
    }

    public function test_bulk_requires_manage_permission(): void
    {
        [$academy, $year, $admin] = $this->makeAcademy();
        $stranger = $this->makeUser('stranger');

        $response = $this->actingAs($stranger, 'api')
            ->postJson("/api/academies/{$academy->id}/classrooms/renumber");

        $response->assertForbidden();
    }

    public function test_bulk_orders_classrooms_naturally_by_section(): void
    {
        [$academy, $year, $admin] = $this->makeAcademy();
        $room1 = $this->makeClassroom($academy, $year, 'ม.1', '1');
        $room2 = $this->makeClassroom($academy, $year, 'ม.1', '10');
        $room3 = $this->makeClassroom($academy, $year, 'ม.1', '2');

        $s1 = $this->makeStudent($academy, '101');
        $this->enroll($s1, $room1, 2);

        $s2 = $this->makeStudent($academy, '102');
        $this->enroll($s2, $room2, 2);

        $s3 = $this->makeStudent($academy, '103');
        $this->enroll($s3, $room3, 2);

        $response = $this->actingAs($admin, 'api')
            ->postJson("/api/academies/{$academy->id}/classrooms/renumber", ['dry_run' => true]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'dry_run' => true,
            ]);

        $classrooms = $response->json('classrooms');
        $this->assertCount(3, $classrooms);

        $this->assertSame('1', $classrooms[0]['section']);
        $this->assertSame('2', $classrooms[1]['section']);
        $this->assertSame('10', $classrooms[2]['section']);
    }

    public function test_bulk_rejects_an_academic_year_from_another_academy(): void
    {
        [$academyA, $yearA, $adminA] = $this->makeAcademy();
        $roomA = $this->makeClassroom($academyA, $yearA);
        $sA = $this->makeStudent($academyA, '111');
        $this->enroll($sA, $roomA, 2);

        [$academyB, $yearB, $adminB] = $this->makeAcademy();

        $response = $this->actingAs($adminA, 'api')
            ->postJson("/api/academies/{$academyA->id}/classrooms/renumber", [
                'academic_year_id' => $yearB->id,
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('classroom_students', [
            'student_id' => $sA->id,
            'student_number' => 2,
        ]);
    }
}
