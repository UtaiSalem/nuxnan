<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademySetting;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCardRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'student-card.public_requests' => true,
        ]);
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

    private function makeAcademy(bool $requestFlowEnabled = true): array
    {
        $admin = $this->makeUser('adm');

        $academy = Academy::create([
            'name' => 'TestAcademy_'.uniqid(),
            'user_id' => $admin->id,
        ]);

        AcademySetting::create([
            'academy_id' => $academy->id,
            'card_request_flow_enabled' => $requestFlowEnabled,
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

    private function makeClassroom(Academy $academy, AcademicYear $year, string $level = 'ม.1', string $section = '1', ?User $homeroomTeacher = null): Classroom
    {
        $classroom = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'grade_level' => $level,
            'section' => $section,
            'name' => $level.'/'.$section,
        ]);

        if ($homeroomTeacher) {
            $classroom->forceFill(['homeroom_teacher_id' => $homeroomTeacher->id])->save();
        }

        return $classroom;
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

    private function makeActiveCard(Academy $academy, AcademicYear $year, Student $student): StudentCard
    {
        return StudentCard::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'student_number' => $student->student_id,
            'full_name_thai' => 'เด็กชาย สมปอง ใจดี',
            'class_level' => '1',
            'class_section' => '1',
            'student_status' => 'active',
        ]);
    }

    // ── gating ──

    public function test_public_request_fails_if_global_flag_is_disabled(): void
    {
        config(['student-card.public_requests' => false]);
        [$academy, $year] = $this->makeAcademy(true);
        $classroom = $this->makeClassroom($academy, $year);
        $student = $this->makeStudent($academy);
        $this->enroll($student, $classroom);

        $this->postJson('/api/student-card/1/1/requests', [
            'student_id' => $student->id,
            'reason_code' => 'damaged',
        ])->assertForbidden();
    }

    public function test_public_request_fails_if_academy_flag_is_disabled(): void
    {
        [$academy, $year] = $this->makeAcademy(false);
        $classroom = $this->makeClassroom($academy, $year);
        $student = $this->makeStudent($academy);
        $this->enroll($student, $classroom);

        $this->postJson('/api/student-card/1/1/requests', [
            'student_id' => $student->id,
            'reason_code' => 'damaged',
        ])->assertForbidden();
    }

    // ── validation ──

    public function test_public_request_requires_reason_code(): void
    {
        [$academy, $year] = $this->makeAcademy(true);
        $classroom = $this->makeClassroom($academy, $year);
        $student = $this->makeStudent($academy);
        $this->enroll($student, $classroom);

        $this->postJson('/api/student-card/1/1/requests', [
            'student_id' => $student->id,
        ])->assertUnprocessable()->assertJsonValidationErrors(['reason_code']);
    }

    public function test_public_request_requires_detail_when_reason_is_other(): void
    {
        [$academy, $year] = $this->makeAcademy(true);
        $classroom = $this->makeClassroom($academy, $year);
        $student = $this->makeStudent($academy);
        $this->enroll($student, $classroom);

        $this->postJson('/api/student-card/1/1/requests', [
            'student_id' => $student->id,
            'reason_code' => 'other',
        ])->assertUnprocessable()->assertJsonValidationErrors(['reason']);
    }

    // ── request type derivation ──

    public function test_student_without_card_gets_first_issue_request(): void
    {
        [$academy, $year] = $this->makeAcademy(true);
        $classroom = $this->makeClassroom($academy, $year);
        $student = $this->makeStudent($academy);
        $this->enroll($student, $classroom);

        $this->postJson('/api/student-card/1/1/requests', [
            'student_id' => $student->id,
            'reason_code' => 'new_student',
        ])->assertCreated();

        $this->assertDatabaseHas('student_card_requests', [
            'student_id' => $student->id,
            'request_type' => 'first_issue',
            'reason_code' => 'new_student',
        ]);
    }

    public function test_replacement_without_card_is_coerced_to_first_issue(): void
    {
        [$academy, $year] = $this->makeAcademy(true);
        $classroom = $this->makeClassroom($academy, $year);
        $student = $this->makeStudent($academy);
        $this->enroll($student, $classroom);

        $this->postJson('/api/student-card/1/1/requests', [
            'student_id' => $student->id,
            'request_type' => 'replacement',
            'reason_code' => 'lost',
        ])->assertCreated();

        $this->assertDatabaseHas('student_card_requests', [
            'student_id' => $student->id,
            'request_type' => 'first_issue',
        ]);
    }

    public function test_expired_reason_with_card_becomes_renewal(): void
    {
        [$academy, $year] = $this->makeAcademy(true);
        $classroom = $this->makeClassroom($academy, $year);
        $student = $this->makeStudent($academy);
        $this->enroll($student, $classroom);
        $this->makeActiveCard($academy, $year, $student);

        $this->postJson('/api/student-card/1/1/requests', [
            'student_id' => $student->id,
            'reason_code' => 'expired',
        ])->assertCreated();

        $this->assertDatabaseHas('student_card_requests', [
            'student_id' => $student->id,
            'request_type' => 'renewal',
        ]);
    }

    // ── submit + defaults ──

    public function test_public_request_submits_successfully_with_requester_info(): void
    {
        [$academy, $year] = $this->makeAcademy(true);
        $classroom = $this->makeClassroom($academy, $year);
        $student = $this->makeStudent($academy);
        $this->enroll($student, $classroom);
        $this->makeActiveCard($academy, $year, $student);

        $response = $this->postJson('/api/student-card/1/1/requests', [
            'student_id' => $student->id,
            'reason_code' => 'damaged',
            'reason' => 'แถบแม่เหล็กพัง',
            'requester_name' => 'ครูสมเจต',
            'requester_phone' => '0812345678',
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'status' => 'pending',
            ]);

        $this->assertDatabaseHas('student_card_requests', [
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'origin' => 'public',
            'requested_by' => null,
            'request_type' => 'replacement',
            'reason_code' => 'damaged',
            'reason' => 'แถบแม่เหล็กพัง',
            'requester_name' => 'ครูสมเจต',
            'requester_phone' => '0812345678',
        ]);
    }

    public function test_requester_name_defaults_to_homeroom_teacher(): void
    {
        [$academy, $year] = $this->makeAcademy(true);
        $teacher = $this->makeUser('teacher');
        $classroom = $this->makeClassroom($academy, $year, 'ม.1', '1', $teacher);
        $student = $this->makeStudent($academy);
        $this->enroll($student, $classroom);

        $this->postJson('/api/student-card/1/1/requests', [
            'student_id' => $student->id,
            'reason_code' => 'new_student',
        ])->assertCreated();

        $this->assertDatabaseHas('student_card_requests', [
            'student_id' => $student->id,
            'requester_name' => $teacher->name,
        ]);
    }

    // ── duplicates ──

    public function test_public_request_prevents_duplicates(): void
    {
        [$academy, $year] = $this->makeAcademy(true);
        $classroom = $this->makeClassroom($academy, $year);
        $student = $this->makeStudent($academy);
        $this->enroll($student, $classroom);
        $this->makeActiveCard($academy, $year, $student);

        $this->postJson('/api/student-card/1/1/requests', [
            'student_id' => $student->id,
            'reason_code' => 'lost',
        ])->assertCreated();

        $this->postJson('/api/student-card/1/1/requests', [
            'student_id' => $student->id,
            'reason_code' => 'lost',
        ])->assertUnprocessable();
    }

    // ── bulk ──

    public function test_bulk_request_reports_per_student_results(): void
    {
        [$academy, $year] = $this->makeAcademy(true);
        $teacher = $this->makeUser('teacher');
        $classroom = $this->makeClassroom($academy, $year, 'ม.1', '1', $teacher);
        $studentA = $this->makeStudent($academy, 'STU1001');
        $studentB = $this->makeStudent($academy, 'STU1002');
        $studentC = $this->makeStudent($academy, 'STU1003');
        $this->enroll($studentA, $classroom, 1);
        $this->enroll($studentB, $classroom, 2);
        $this->enroll($studentC, $classroom, 3);

        // studentB มีคำร้องค้างอยู่แล้ว → ต้อง fail รายคน ไม่ล้มทั้งชุด
        $this->postJson('/api/student-card/1/1/requests', [
            'student_id' => $studentB->id,
            'reason_code' => 'new_student',
        ])->assertCreated();

        $response = $this->postJson('/api/student-card/1/1/requests/bulk', [
            'student_ids' => [$studentA->id, $studentB->id, $studentC->id],
            'reason_code' => 'new_student',
        ]);

        $response->assertCreated();
        $results = collect($response->json('results'));

        $this->assertTrue($results->firstWhere('student_id', $studentA->id)['success']);
        $this->assertFalse($results->firstWhere('student_id', $studentB->id)['success']);
        $this->assertTrue($results->firstWhere('student_id', $studentC->id)['success']);

        // ทุกคำร้องที่สร้างจาก bulk ต้องได้ requester default เป็นครูประจำชั้น
        $this->assertDatabaseHas('student_card_requests', [
            'student_id' => $studentA->id,
            'request_type' => 'first_issue',
            'requester_name' => $teacher->name,
        ]);
    }

    public function test_bulk_request_requires_student_ids(): void
    {
        [$academy, $year] = $this->makeAcademy(true);
        $this->makeClassroom($academy, $year);

        $this->postJson('/api/student-card/1/1/requests/bulk', [
            'reason_code' => 'new_student',
        ])->assertUnprocessable()->assertJsonValidationErrors(['student_ids']);
    }

    // ── room list exposes open request status ──

    public function test_room_listing_includes_active_card_request(): void
    {
        [$academy, $year] = $this->makeAcademy(true);
        $classroom = $this->makeClassroom($academy, $year);
        $student = $this->makeStudent($academy);
        $this->enroll($student, $classroom);
        $this->makeActiveCard($academy, $year, $student);

        $this->postJson('/api/student-card/1/1/requests', [
            'student_id' => $student->id,
            'reason_code' => 'lost',
        ])->assertCreated();

        $response = $this->getJson('/api/student-card/1/1');

        $response->assertOk();
        $row = collect($response->json('students'))->firstWhere('student_id', $student->id);
        $this->assertNotNull($row);
        $this->assertSame('pending', $row['active_card_request']['status']);
        $this->assertSame('lost', $row['active_card_request']['reason_code']);
    }
}
