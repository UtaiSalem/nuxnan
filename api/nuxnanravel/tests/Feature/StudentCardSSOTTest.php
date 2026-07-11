<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentCardSSOTTest extends TestCase
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

    private function setupStudentAndAcademy(): array
    {
        $owner = $this->makeUser('owner');
        $admin = $this->makeUser('adm');

        $academy = Academy::create([
            'name' => 'TestAcademy_'.uniqid(),
            'user_id' => $admin->id,
        ]);

        AcademyMember::create([
            'user_id' => $admin->id,
            'academy_id' => $academy->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $owner->id,
            'student_id' => 'STU1001',
            'citizen_id' => '1234567890123',
            'date_of_birth' => '2010-01-15',
            'title_prefix_th' => 'เด็กชาย',
            'first_name_th' => 'สมปอง',
            'last_name_th' => 'ใจดี',
            'status' => 'active',
        ]);

        AcademyMember::create([
            'user_id' => $owner->id,
            'academy_id' => $academy->id,
            'role' => 'student',
            'status' => 'active',
        ]);

        $year = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2569',
            'is_current' => true,
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ]);

        $classroom = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
        ]);

        $enrollment = ClassroomStudent::create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'status' => 'active',
            'student_number' => '1',
        ]);

        $card = StudentCard::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'student_number' => 'STU1001',
            'national_id' => '1234567890123',
            'full_name_thai' => 'เด็กชาย สมปอง ใจดี',
            'class_level' => '1',
            'class_section' => '1',
            'student_status' => 'active',
        ]);

        return [$owner, $admin, $academy, $student, $enrollment, $card];
    }

    public function test_updating_student_master_reflects_on_card_api_immediately()
    {
        [$owner, $admin, $academy, $student, $enrollment, $card] = $this->setupStudentAndAcademy();

        // Update student first name
        $student->update(['first_name_th' => 'สมเกียรติ']);

        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/student-cards/by-student/{$student->id}");

        $response->assertStatus(200)
            ->assertJsonPath('student.first_name_thai', 'สมเกียรติ')
            ->assertJsonPath('student.full_name_thai', 'เด็กชาย สมเกียรติ ใจดี');
    }

    public function test_graduating_student_enrollment_auto_expires_card()
    {
        [$owner, $admin, $academy, $student, $enrollment, $card] = $this->setupStudentAndAcademy();

        // Update enrollment status to graduated
        $enrollment->update(['status' => 'graduated']);

        $card->refresh();
        $this->assertEquals('graduated', $card->student_status);
    }

    public function test_cannot_have_more_than_one_active_card_per_student_per_academy()
    {
        [$owner, $admin, $academy, $student, $enrollment, $card] = $this->setupStudentAndAcademy();

        $this->expectException(UniqueConstraintException::class);
        if (! class_exists(UniqueConstraintException::class)) {
            $this->expectException(QueryException::class);
        }

        // Try to create another active card for the same student
        StudentCard::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'student_number' => 'STU1001',
            'national_id' => '1234567890123',
            'full_name_thai' => 'เด็กชาย สมปอง ใจดี',
            'class_level' => '1',
            'class_section' => '1',
            'student_status' => 'active',
        ]);
    }

    public function test_updating_fields_via_card_admin_api_writes_to_students_table()
    {
        [$owner, $admin, $academy, $student, $enrollment, $card] = $this->setupStudentAndAcademy();

        $response = $this->actingAs($admin, 'api')
            ->patchJson("/api/academies/{$academy->id}/student-cards/admin/update-name-th/{$card->id}", [
                'first_name_thai' => 'สมชาย',
                'last_name_thai' => 'ยอดดี',
            ]);

        $response->assertStatus(200);

        $student->refresh();
        $this->assertEquals('สมชาย', $student->first_name_th);
        $this->assertEquals('ยอดดี', $student->last_name_th);
    }

    public function test_public_room_endpoint_returns_card_identity_data_from_student_master()
    {
        [$owner, $admin, $academy, $student, $enrollment, $card] = $this->setupStudentAndAcademy();

        // Access via public endpoint (no auth)
        $response = $this->getJson('/api/student-card/1/1');

        $response->assertStatus(200);

        $data = $response->json('students');
        $this->assertNotEmpty($data);

        $this->assertSame($student->citizen_id, $data[0]['national_id']);
        $this->assertSame('2010-01-15', $data[0]['birth_date']);
        $this->assertSame('15/01/2010', $data[0]['birth_date_string']);
    }

    public function test_moving_enrollment_changes_room_api_immediately(): void
    {
        [$owner, $admin, $academy, $student, $enrollment, $card] = $this->setupStudentAndAcademy();
        $newClassroom = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $enrollment->academic_year_id,
            'grade_level' => 'ม.2',
            'section' => '3',
            'name' => 'ม.2/3',
        ]);

        $enrollment->update(['classroom_id' => $newClassroom->id]);

        $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/student-cards/2/3")
            ->assertOk()
            ->assertJsonPath('students.0.id', $card->id)
            ->assertJsonPath('students.0.class_level', 2)
            ->assertJsonPath('students.0.class_section', 3);
    }

    public function test_room_collection_has_bounded_query_count(): void
    {
        [$owner, $admin, $academy, $student, $enrollment] = $this->setupStudentAndAcademy();

        foreach (range(2, 6) as $number) {
            $extra = Student::create([
                'academy_id' => $academy->id,
                'student_id' => 'STU100'.$number,
                'first_name_th' => 'Student'.$number,
                'last_name_th' => 'Query',
                'status' => 'active',
            ]);
            ClassroomStudent::create([
                'academy_id' => $academy->id,
                'student_id' => $extra->id,
                'classroom_id' => $enrollment->classroom_id,
                'academic_year_id' => $enrollment->academic_year_id,
                'status' => 'active',
                'student_number' => $number,
            ]);
            StudentCard::create([
                'academy_id' => $academy->id,
                'student_id' => $extra->id,
                'academic_year_id' => $enrollment->academic_year_id,
                'student_status' => 'active',
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($owner, 'api')->getJson("/api/academies/{$academy->id}/student-cards/1/1")->assertOk();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(12, $queryCount, "Room endpoint executed {$queryCount} queries");
    }

    public function test_reconcile_command_dry_run_does_not_mutate_card(): void
    {
        [, , $academy, $student, , $card] = $this->setupStudentAndAcademy();
        $card->update(['first_name_thai' => 'stale']);

        $this->artisan('students:reconcile-cards', [
            'academy' => $academy->id,
            '--fix' => true,
            '--dry-run' => true,
        ])->assertExitCode(0);

        $this->assertSame('stale', $card->fresh()->first_name_thai);
        $this->assertNotSame('stale', $student->first_name_th);
    }

    public function test_store_links_card_to_student_master_record(): void
    {
        [, $admin, $academy] = $this->setupStudentAndAcademy();

        $response = $this->actingAs($admin, 'api')
            ->postJson("/api/academies/{$academy->id}/student-cards/admin", [
                'student_number' => 'STU2001',
                'first_name_thai' => 'สมหญิง',
                'last_name_thai' => 'เรียนดี',
                'class_level' => '1',
                'class_section' => '1',
            ])
            ->assertCreated();

        $student = Student::where('academy_id', $academy->id)->where('student_id', 'STU2001')->firstOrFail();
        $this->assertDatabaseHas('student_cards', [
            'id' => $response->json('student.id'),
            'student_id' => $student->id,
            'academy_id' => $academy->id,
        ]);
    }
}
