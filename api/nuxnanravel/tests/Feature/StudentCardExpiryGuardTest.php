<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademySetting;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\User;
use App\Services\StudentCardSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentCardExpiryGuardTest extends TestCase
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

        if (class_exists(AcademySetting::class)) {
            AcademySetting::updateOrCreate(
                ['academy_id' => $academy->id],
                ['card_request_flow_enabled' => false]
            );
        }

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
            'academy_id' => $academy->id,
        ]);

        return [$owner, $admin, $academy, $student, $enrollment, $year, $classroom];
    }

    public function test_observer_keeps_card_when_student_still_has_another_active_room()
    {
        [$owner, $admin, $academy, $student, $enrollment, $year, $classroom] = $this->setupStudentAndAcademy();

        $classroom2 = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'grade_level' => 'ม.1',
            'section' => '2',
            'name' => 'ม.1/2',
        ]);

        $enrollment2 = ClassroomStudent::create([
            'student_id' => $student->id,
            'classroom_id' => $classroom2->id,
            'academic_year_id' => $year->id,
            'status' => 'active',
            'student_number' => '1',
            'academy_id' => $academy->id,
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

        $enrollment->update(['status' => 'transferred']);

        $this->assertEquals('active', $card->fresh()->student_status);
    }

    public function test_observer_expires_card_when_last_active_room_closes()
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
            'student_status' => 'active',
        ]);

        $enrollment->update(['status' => 'removed']);

        $this->assertEquals('expired', $card->fresh()->student_status);
    }

    public function test_sync_does_not_expire_card_of_student_who_is_still_enrolled()
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
            'student_status' => 'active',
        ]);

        $yearOther = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2570',
            'is_current' => false,
            'start_date' => '2027-05-16',
            'end_date' => '2028-03-31',
        ]);

        $service = app(StudentCardSyncService::class);
        $result = $service->commitSync($academy, $yearOther, $admin);

        $this->assertEquals('active', $card->fresh()->student_status);
        $this->assertEquals(0, $result['expired']);
    }

    public function test_sync_still_expires_card_of_student_without_any_active_enrollment()
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
            'student_status' => 'active',
        ]);

        $enrollment->updateQuietly(['status' => 'removed']);
        $card->updateQuietly(['student_status' => 'active']);

        $service = app(StudentCardSyncService::class);
        $result = $service->commitSync($academy, $year, $admin);

        $this->assertEquals('expired', $card->fresh()->student_status);
        $this->assertEquals(1, $result['expired']);
    }
}
