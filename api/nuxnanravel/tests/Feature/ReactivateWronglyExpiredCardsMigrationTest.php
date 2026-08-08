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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReactivateWronglyExpiredCardsMigrationTest extends TestCase
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
            'status' => 2,
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

        $studentRole = AcademyRole::create([
            'academy_id' => $academy->id,
            'name' => 'student-local',
            'display_name_th' => 'นักเรียน',
            'permissions' => ['students.view'],
            'is_active' => true,
        ]);

        AcademyMember::create([
            'user_id' => $owner->id,
            'academy_id' => $academy->id,
            'role' => 'student',
            'academy_role_id' => $studentRole->id,
            'status' => 2,
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

        return [$owner, $admin, $academy, $student, $enrollment, $year, $classroom];
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_08_09_100000_reactivate_wrongly_expired_student_cards.php');
    }

    public function test_reactivates_card_of_student_who_is_still_enrolled()
    {
        [$owner, $admin, $academy, $student, $enrollment, $year, $classroom] = $this->setupStudentAndAcademy();

        $card = StudentCard::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'student_number' => 'STU1001',
            'national_id' => '1234567890123',
            'full_name_thai' => 'เด็กชาย สมปอง ใจดี',
            'class_level' => '1',
            'class_section' => '1',
            'student_status' => 'expired',
        ]);

        $this->migration()->up();

        $this->assertEquals('active', $card->fresh()->student_status);
    }

    public function test_leaves_card_alone_when_student_has_no_active_enrollment()
    {
        [$owner, $admin, $academy, $student, $enrollment, $year, $classroom] = $this->setupStudentAndAcademy();

        $enrollment->update(['status' => 'transferred']);

        $card = StudentCard::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'student_number' => 'STU1001',
            'national_id' => '1234567890123',
            'full_name_thai' => 'เด็กชาย สมปอง ใจดี',
            'class_level' => '1',
            'class_section' => '1',
            'student_status' => 'expired',
        ]);

        $this->migration()->up();

        $this->assertEquals('expired', $card->fresh()->student_status);
    }

    public function test_does_not_create_a_second_active_card()
    {
        [$owner, $admin, $academy, $student, $enrollment, $year, $classroom] = $this->setupStudentAndAcademy();

        $card1 = StudentCard::create([
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

        $card2 = StudentCard::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'student_number' => 'STU1001',
            'national_id' => '1234567890123',
            'full_name_thai' => 'เด็กชาย สมปอง ใจดี',
            'class_level' => '1',
            'class_section' => '1',
            'student_status' => 'expired',
        ]);

        $this->migration()->up();

        $this->assertEquals('active', $card1->fresh()->student_status);
        $this->assertEquals('expired', $card2->fresh()->student_status);

        $activeCount = StudentCard::where('student_id', $student->id)
            ->where('academy_id', $academy->id)
            ->where('student_status', 'active')
            ->count();
        $this->assertEquals(1, $activeCount);
    }

    public function test_picks_only_one_card_when_several_expired_cards_qualify()
    {
        [$owner, $admin, $academy, $student, $enrollment, $yearOld, $classroom] = $this->setupStudentAndAcademy();

        $yearOld->update(['is_current' => false, 'name' => '2568']);

        $yearNew = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2569',
            'is_current' => true,
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ]);

        $classroomNew = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $yearNew->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
        ]);

        $enrollment->update([
            'classroom_id' => $classroomNew->id,
            'academic_year_id' => $yearNew->id,
        ]);

        $cardOld = StudentCard::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'academic_year_id' => $yearOld->id,
            'student_number' => 'STU1001',
            'national_id' => '1234567890123',
            'full_name_thai' => 'เด็กชาย สมปอง ใจดี',
            'class_level' => '1',
            'class_section' => '1',
            'student_status' => 'expired',
        ]);

        $cardNew = StudentCard::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'academic_year_id' => $yearNew->id,
            'student_number' => 'STU1001',
            'national_id' => '1234567890123',
            'full_name_thai' => 'เด็กชาย สมปอง ใจดี',
            'class_level' => '1',
            'class_section' => '1',
            'student_status' => 'expired',
        ]);

        $this->migration()->up();

        $activeCount = StudentCard::where('student_id', $student->id)
            ->where('academy_id', $academy->id)
            ->where('student_status', 'active')
            ->count();

        $this->assertEquals(1, $activeCount);
        $this->assertEquals('active', $cardNew->fresh()->student_status);
        $this->assertEquals('expired', $cardOld->fresh()->student_status);
    }

    public function test_down_restores_previous_status()
    {
        [$owner, $admin, $academy, $student, $enrollment, $year, $classroom] = $this->setupStudentAndAcademy();

        $card = StudentCard::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'student_number' => 'STU1001',
            'national_id' => '1234567890123',
            'full_name_thai' => 'เด็กชาย สมปอง ใจดี',
            'class_level' => '1',
            'class_section' => '1',
            'student_status' => 'expired',
        ]);

        $this->migration()->up();
        $this->assertEquals('active', $card->fresh()->student_status);

        $this->migration()->down();
        $this->assertEquals('expired', $card->fresh()->student_status);

        $backupCount = DB::table('classroom_repair_backups')
            ->where('batch', '2026_08_09_100000')
            ->count();
        $this->assertEquals(0, $backupCount);
    }
}
