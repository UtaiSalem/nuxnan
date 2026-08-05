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

/**
 * uq_student_card_active ยอมให้นักเรียนมีบัตร active ได้ใบเดียวต่อ academy
 * การเพิ่มนักเรียนเข้าห้องจึงต้องปลุกบัตรที่หมดอายุกลับมาได้ทีละใบเท่านั้น
 */
class ClassroomStudentCardReactivationTest extends TestCase
{
    use RefreshDatabase;

    private Academy $academy;

    private AcademicYear $year;

    private Classroom $room;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::create([
            'name' => 'Card owner',
            'email' => uniqid().'@test.local',
            'password' => bcrypt('x'),
            'username' => uniqid(),
            'reference_code' => uniqid(),
            'personal_code' => uniqid(),
        ]);
        $this->academy = Academy::create(['name' => 'Card test '.uniqid(), 'user_id' => $user->id]);
        $this->year = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2569',
            'is_current' => true,
            'start_date' => '2026-05-01',
            'end_date' => '2027-03-31',
        ]);
        $this->room = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year->id,
            'grade_level' => 'ม.4',
            'section' => '8',
            'name' => 'ม.4/8',
        ]);
    }

    private function makeStudent(): Student
    {
        return Student::create([
            'academy_id' => $this->academy->id,
            'student_id' => 'S'.uniqid(),
            'first_name_th' => 'Test',
            'last_name_th' => 'Student',
            'status' => 'active',
        ]);
    }

    private function makeCard(Student $student, string $status, array $attributes = []): StudentCard
    {
        return StudentCard::create(array_merge([
            'academy_id' => $this->academy->id,
            'student_id' => $student->id,
            'student_number' => $student->student_id,
            'class_level' => '4',
            'class_section' => '8',
            'student_status' => $status,
        ], $attributes));
    }

    private function enroll(Student $student): ClassroomStudent
    {
        return ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'student_id' => $student->id,
            'classroom_id' => $this->room->id,
            'academic_year_id' => $this->year->id,
            'status' => 'active',
            'student_number' => 1,
        ]);
    }

    public function test_student_with_several_expired_cards_can_be_enrolled(): void
    {
        $student = $this->makeStudent();
        $old = $this->makeCard($student, 'expired', ['card_issue_date' => '2025-06-18 15:41:09']);
        $recent = $this->makeCard($student, 'expired', [
            'academic_year_id' => $this->year->id,
            'card_issue_date' => '2026-07-07 20:07:07',
        ]);

        $this->enroll($student);

        // ปลุกได้ใบเดียว และต้องเป็นใบล่าสุด
        $this->assertSame('active', $recent->fresh()->student_status);
        $this->assertSame('expired', $old->fresh()->student_status);
    }

    public function test_enrolling_a_student_who_already_holds_an_active_card_leaves_expired_cards_alone(): void
    {
        $student = $this->makeStudent();
        $active = $this->makeCard($student, 'active', ['academic_year_id' => $this->year->id]);
        $expired = $this->makeCard($student, 'expired', ['card_issue_date' => '2025-06-18 15:41:09']);

        $this->enroll($student);

        $this->assertSame('active', $active->fresh()->student_status);
        $this->assertSame('expired', $expired->fresh()->student_status);
    }

    public function test_leaving_the_room_expires_the_active_card(): void
    {
        $student = $this->makeStudent();
        $card = $this->makeCard($student, 'active', ['academic_year_id' => $this->year->id]);
        $enrollment = $this->enroll($student);

        $enrollment->update(['status' => ClassroomStudent::STATUS_REMOVED, 'left_at' => today()]);

        $this->assertSame('expired', $card->fresh()->student_status);
    }

    public function test_returning_to_a_room_reactivates_exactly_one_card(): void
    {
        $student = $this->makeStudent();
        $stale = $this->makeCard($student, 'expired', ['card_issue_date' => '2025-06-18 15:41:09']);
        $card = $this->makeCard($student, 'active', [
            'academic_year_id' => $this->year->id,
            'card_issue_date' => '2026-07-07 20:07:07',
        ]);
        $enrollment = $this->enroll($student);

        $enrollment->update(['status' => ClassroomStudent::STATUS_REMOVED, 'left_at' => today()]);
        $enrollment->update(['status' => ClassroomStudent::STATUS_ACTIVE, 'left_at' => null]);

        $this->assertSame('active', $card->fresh()->student_status);
        $this->assertSame('expired', $stale->fresh()->student_status);
    }

    public function test_graduating_only_touches_the_active_card(): void
    {
        $student = $this->makeStudent();
        $expired = $this->makeCard($student, 'expired', ['card_issue_date' => '2025-06-18 15:41:09']);
        $card = $this->makeCard($student, 'active', ['academic_year_id' => $this->year->id]);
        $enrollment = $this->enroll($student);

        $enrollment->update(['status' => 'graduated', 'left_at' => today()]);

        $this->assertSame('graduated', $card->fresh()->student_status);
        $this->assertSame('expired', $expired->fresh()->student_status);
    }
}
