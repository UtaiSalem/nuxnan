<?php

namespace Tests\Feature;

use App\Events\Enrollment\StudentDropped;
use App\Events\Enrollment\StudentEnrolled;
use App\Events\Enrollment\StudentGraduated;
use App\Events\Enrollment\StudentPromoted;
use App\Events\Enrollment\StudentRepeated;
use App\Events\Enrollment\StudentTransferred;
use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentAcademicInfo;
use App\Models\User;
use App\Services\StudentEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class StudentEnrollmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private StudentEnrollmentService $service;

    private Academy $academy;

    private AcademicYear $academicYear2568;

    private AcademicYear $academicYear2569;

    private Classroom $classroomA;

    private Classroom $classroomB;

    private Classroom $classroomC; // Year 2569

    private Student $student;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StudentEnrollmentService::class);

        $this->user = User::factory()->create();
        $this->academy = Academy::create([
            'user_id' => $this->user->id,
            'name' => 'Test Academy',
            'slug' => 'test-academy',
        ]);

        $this->academicYear2568 = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2568',
            'start_date' => '2025-05-16',
            'end_date' => '2026-03-31',
        ]);

        $this->academicYear2569 = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2569',
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ]);

        $this->classroomA = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->academicYear2568->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
            'capacity' => 50,
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->classroomB = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->academicYear2568->id,
            'grade_level' => 'ม.1',
            'section' => '2',
            'name' => 'ม.1/2',
            'capacity' => 50,
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->classroomC = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->academicYear2569->id,
            'grade_level' => 'ม.2',
            'section' => '1',
            'name' => 'ม.2/1',
            'capacity' => 50,
            'is_active' => true,
            'status' => 'active',
        ]);

        $studentUser = User::factory()->create();
        $this->student = Student::create([
            'user_id' => $studentUser->id,
            'academy_id' => $this->academy->id,
            'first_name_th' => 'สมชาย',
            'last_name_th' => 'สายเสมอ',
            'student_id' => 'ST001',
            'citizen_id' => '1234567890123',
        ]);
    }

    public function test_enroll_student_fresh(): void
    {
        $enrollment = $this->service->enrollStudent($this->student, $this->classroomA, 12);

        $this->assertDatabaseHas('classroom_students', [
            'id' => $enrollment->id,
            'classroom_id' => $this->classroomA->id,
            'student_id' => $this->student->id,
            'status' => 'active',
            'student_number' => 12,
        ]);

        $this->student->refresh();
        $this->assertEquals('1', $this->student->class_level); // Normalized ม.1 -> 1
        $this->assertEquals('1', $this->student->class_section);

        $this->assertDatabaseHas('student_academic_info', [
            'student_id' => $this->student->id,
            'classroom_id' => $this->classroomA->id,
            'academic_year' => '2568',
            'is_current' => true,
        ]);
    }

    public function test_enroll_student_duplicate_overwrites(): void
    {
        $this->service->enrollStudent($this->student, $this->classroomA, 10);
        $this->service->enrollStudent($this->student, $this->classroomA, 15);

        $count = ClassroomStudent::where('classroom_id', $this->classroomA->id)
            ->where('student_id', $this->student->id)
            ->count();

        $this->assertEquals(1, $count);

        $enrollment = ClassroomStudent::where('classroom_id', $this->classroomA->id)
            ->where('student_id', $this->student->id)
            ->first();
        $this->assertEquals(15, $enrollment->student_number);
    }

    public function test_transfer_student_same_year(): void
    {
        $this->service->enrollStudent($this->student, $this->classroomA, 10);
        $opened = $this->service->transferStudent($this->student, $this->classroomA, $this->classroomB, 'ย้ายห้องทดสอบ');

        $this->assertDatabaseHas('classroom_students', [
            'classroom_id' => $this->classroomA->id,
            'student_id' => $this->student->id,
            'status' => 'transferred',
            'leave_reason' => 'ย้ายห้องทดสอบ',
        ]);

        $this->assertDatabaseHas('classroom_students', [
            'id' => $opened->id,
            'classroom_id' => $this->classroomB->id,
            'student_id' => $this->student->id,
            'status' => 'active',
        ]);

        $this->student->refresh();
        $this->assertEquals('1', $this->student->class_level);
        $this->assertEquals('2', $this->student->class_section);

        $this->assertDatabaseHas('student_academic_info', [
            'student_id' => $this->student->id,
            'classroom_id' => $this->classroomB->id,
            'is_current' => true,
        ]);
    }

    public function test_transfer_student_cross_year_fails(): void
    {
        $this->service->enrollStudent($this->student, $this->classroomA, 10);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->transferStudent($this->student, $this->classroomA, $this->classroomC);
    }

    public function test_graduate_student(): void
    {
        $this->service->enrollStudent($this->student, $this->classroomA, 10);
        $closed = $this->service->graduateStudent($this->student, $this->classroomA, 'เรียนจบแล้ว');

        $this->assertNotNull($closed);
        $this->assertEquals('graduated', $closed->status);

        $this->student->refresh();
        $this->assertEquals('graduated', $this->student->status);
        $this->assertNull($this->student->class_level);
        $this->assertNull($this->student->class_section);

        $this->assertDatabaseHas('student_academic_info', [
            'student_id' => $this->student->id,
            'study_status' => 'graduated',
            'is_current' => false,
        ]);
        $this->assertNotNull(StudentAcademicInfo::where('student_id', $this->student->id)->first()->graduation_date);
    }

    public function test_drop_student(): void
    {
        $this->service->enrollStudent($this->student, $this->classroomA, 10);
        $closed = $this->service->dropStudent($this->student, $this->classroomA, 'ลาออกกลางคัน');

        $this->assertNotNull($closed);
        $this->assertEquals('dropped', $closed->status);

        $this->student->refresh();
        $this->assertEquals('inactive', $this->student->status);
        $this->assertNull($this->student->class_level);
        $this->assertNull($this->student->class_section);

        $this->assertDatabaseHas('student_academic_info', [
            'student_id' => $this->student->id,
            'study_status' => 'dropped',
            'is_current' => false,
        ]);
    }

    public function test_repeat_student_new_classroom(): void
    {
        $this->service->enrollStudent($this->student, $this->classroomA, 10);
        $opened = $this->service->repeatStudent($this->student, $this->classroomB, 15, 'ซ้ำชั้นเรียนเดิม');

        $this->assertDatabaseHas('classroom_students', [
            'classroom_id' => $this->classroomA->id,
            'student_id' => $this->student->id,
            'status' => 'repeating',
            'leave_reason' => 'ซ้ำชั้นเรียนเดิม',
        ]);

        $this->assertDatabaseHas('classroom_students', [
            'id' => $opened->id,
            'classroom_id' => $this->classroomB->id,
            'status' => 'active',
            'student_number' => 15,
        ]);
    }

    public function test_repeat_student_same_classroom_fails(): void
    {
        $this->service->enrollStudent($this->student, $this->classroomA, 10);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->repeatStudent($this->student, $this->classroomA, 10);
    }

    public function test_promote_student(): void
    {
        $this->service->enrollStudent($this->student, $this->classroomA, 10);
        $opened = $this->service->promoteStudent($this->student, $this->classroomA, $this->classroomC, 'เลื่อนชั้น ม.2');

        $this->assertDatabaseHas('classroom_students', [
            'classroom_id' => $this->classroomA->id,
            'student_id' => $this->student->id,
            'status' => 'promoted',
            'leave_reason' => 'เลื่อนชั้น ม.2',
        ]);

        $this->assertDatabaseHas('classroom_students', [
            'id' => $opened->id,
            'classroom_id' => $this->classroomC->id,
            'status' => 'active',
        ]);

        $this->student->refresh();
        $this->assertEquals('2', $this->student->class_level); // ม.2 -> 2
        $this->assertEquals('1', $this->student->class_section);

        // Verify StudentAcademicInfo has year 2569 as current, 2568 is false
        $this->assertDatabaseHas('student_academic_info', [
            'student_id' => $this->student->id,
            'classroom_id' => $this->classroomA->id,
            'academic_year' => '2568',
            'is_current' => false,
        ]);

        $this->assertDatabaseHas('student_academic_info', [
            'student_id' => $this->student->id,
            'classroom_id' => $this->classroomC->id,
            'academic_year' => '2569',
            'is_current' => true,
        ]);
    }

    public function test_normalize_grade_level(): void
    {
        $this->assertEquals('1', $this->service->normalizeGradeLevel('ม.1'));
        $this->assertEquals('1', $this->service->normalizeGradeLevel('1'));
        $this->assertEquals('3', $this->service->normalizeGradeLevel('ป.3'));
        $this->assertEquals('2', $this->service->normalizeGradeLevel('อ.2'));
        $this->assertNull($this->service->normalizeGradeLevel(null));
    }

    public function test_close_active_enrollment_none_exists(): void
    {
        // Calling unenroll/drop/graduate on student with no active enrollment should not throw
        $closed = $this->service->graduateStudent($this->student, null, 'ไม่มีห้องเรียน');
        $this->assertNull($closed);
    }

    public function test_all_methods_fire_events(): void
    {
        Event::fake();

        // 1. Enroll
        $enrollment = $this->service->enrollStudent($this->student, $this->classroomA, 10);
        Event::assertDispatched(StudentEnrolled::class);

        // 2. Transfer
        $this->service->transferStudent($this->student, $this->classroomA, $this->classroomB);
        Event::assertDispatched(StudentTransferred::class);

        // 3. Promote (Requires cross-year)
        $this->service->promoteStudent($this->student, $this->classroomB, $this->classroomC);
        Event::assertDispatched(StudentPromoted::class);

        // Setup active again for graduate/drop/repeat
        $enrollment2 = $this->service->enrollStudent($this->student, $this->classroomA, 10);

        // 4. Repeat
        $this->service->repeatStudent($this->student, $this->classroomB);
        Event::assertDispatched(StudentRepeated::class);

        // 5. Graduate
        $this->service->graduateStudent($this->student);
        Event::assertDispatched(StudentGraduated::class);

        // Setup active again
        $this->service->enrollStudent($this->student, $this->classroomA, 10);

        // 6. Drop
        $this->service->dropStudent($this->student);
        Event::assertDispatched(StudentDropped::class);
    }
}
