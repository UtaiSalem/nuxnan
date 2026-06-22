<?php

namespace Tests\Feature;

use App\Events\Enrollment\RolloverCommitted;
use App\Events\Enrollment\RolloverUndone;
use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyAdmin;
use App\Models\Classroom;
use App\Models\Notification;
use App\Models\RolloverBatch;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentNotificationListenerTest extends TestCase
{
    use RefreshDatabase;

    private StudentEnrollmentService $service;

    private Academy $academy;

    private AcademicYear $year2568;

    private AcademicYear $year2569;

    private Classroom $classroomA;

    private Classroom $classroomB;

    private Classroom $classroomC;

    private Student $student;

    private User $owner;

    private User $academyAdmin;

    private User $teacherA;

    private User $teacherB;

    private User $teacherC;

    private User $studentUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(StudentEnrollmentService::class);

        $this->owner = User::factory()->create(['name' => 'Owner User']);
        $this->academyAdmin = User::factory()->create(['name' => 'Academy Admin']);
        $this->teacherA = User::factory()->create(['name' => 'Teacher A']);
        $this->teacherB = User::factory()->create(['name' => 'Teacher B']);
        $this->teacherC = User::factory()->create(['name' => 'Teacher C']);
        $this->studentUser = User::factory()->create(['name' => 'Student User']);

        $this->academy = Academy::create([
            'user_id' => $this->owner->id,
            'name' => 'Notification Academy',
        ]);

        AcademyAdmin::create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->academyAdmin->id,
        ]);

        $this->year2568 = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2568',
            'start_date' => '2025-05-16',
            'end_date' => '2026-03-31',
        ]);

        $this->year2569 = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2569',
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ]);

        $this->classroomA = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year2568->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
            'homeroom_teacher_id' => $this->teacherA->id,
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->classroomB = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year2568->id,
            'grade_level' => 'ม.1',
            'section' => '2',
            'name' => 'ม.1/2',
            'homeroom_teacher_id' => $this->teacherB->id,
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->classroomC = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year2569->id,
            'grade_level' => 'ม.2',
            'section' => '1',
            'name' => 'ม.2/1',
            'homeroom_teacher_id' => $this->teacherC->id,
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'student_id' => 'NTF001',
            'citizen_id' => '1234567890123',
            'first_name_th' => 'สมชาย',
            'last_name_th' => 'ใจดี',
        ]);
    }

    public function test_enroll_student_creates_notifications_for_student_and_homeroom_teacher(): void
    {
        $enrollment = $this->service->enrollStudent($this->student, $this->classroomA, 7, null, $this->owner->id);

        $this->assertSame(2, Notification::count());
        $this->assertEqualsCanonicalizing(
            [$this->studentUser->id, $this->teacherA->id],
            Notification::pluck('user_id')->all()
        );

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->studentUser->id,
            'type' => Notification::TYPE_STUDENT_ENROLLED,
            'sender_id' => $this->owner->id,
            'related_id' => $enrollment->id,
        ]);
    }

    public function test_transfer_student_creates_only_transfer_notifications_without_nested_enroll_notifications(): void
    {
        $this->service->enrollStudent($this->student, $this->classroomA, 5, null, $this->owner->id);
        Notification::query()->delete();

        $opened = $this->service->transferStudent(
            $this->student,
            $this->classroomA,
            $this->classroomB,
            'ย้ายห้องทดสอบ',
            null,
            $this->owner->id
        );

        $this->assertSame(3, Notification::count());
        $this->assertEqualsCanonicalizing(
            [$this->studentUser->id, $this->teacherA->id, $this->teacherB->id],
            Notification::pluck('user_id')->all()
        );
        $this->assertSame(
            [Notification::TYPE_STUDENT_TRANSFERRED],
            Notification::query()->distinct()->pluck('type')->all()
        );
        $this->assertDatabaseMissing('notifications', [
            'type' => Notification::TYPE_STUDENT_ENROLLED,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->teacherB->id,
            'type' => Notification::TYPE_STUDENT_TRANSFERRED,
            'related_id' => $opened->id,
        ]);
    }

    public function test_promote_student_notifies_student_teachers_and_academy_admins(): void
    {
        $this->service->enrollStudent($this->student, $this->classroomA, 3, null, $this->owner->id);
        Notification::query()->delete();

        $opened = $this->service->promoteStudent(
            $this->student,
            $this->classroomA,
            $this->classroomC,
            'เลื่อนชั้นปกติ',
            null,
            null,
            $this->owner->id
        );

        $this->assertSame(5, Notification::count());
        $this->assertEqualsCanonicalizing(
            [$this->studentUser->id, $this->teacherA->id, $this->teacherC->id, $this->owner->id, $this->academyAdmin->id],
            Notification::pluck('user_id')->all()
        );
        $this->assertSame(
            [Notification::TYPE_STUDENT_PROMOTED],
            Notification::query()->distinct()->pluck('type')->all()
        );
        $this->assertDatabaseMissing('notifications', [
            'type' => Notification::TYPE_STUDENT_ENROLLED,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->academyAdmin->id,
            'type' => Notification::TYPE_STUDENT_PROMOTED,
            'related_id' => $opened->id,
        ]);
    }

    public function test_repeat_student_notifies_student_teachers_and_academy_admins(): void
    {
        $this->service->enrollStudent($this->student, $this->classroomA, 8, null, $this->owner->id);
        Notification::query()->delete();

        $opened = $this->service->repeatStudent(
            $this->student,
            $this->classroomB,
            11,
            'ซ้ำชั้น',
            null,
            $this->owner->id
        );

        $this->assertSame(5, Notification::count());
        $this->assertEqualsCanonicalizing(
            [$this->studentUser->id, $this->teacherA->id, $this->teacherB->id, $this->owner->id, $this->academyAdmin->id],
            Notification::pluck('user_id')->all()
        );
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->teacherB->id,
            'type' => Notification::TYPE_STUDENT_REPEATED,
            'related_id' => $opened->id,
        ]);
    }

    public function test_graduate_student_notifies_student_homeroom_and_admins(): void
    {
        $this->service->enrollStudent($this->student, $this->classroomA, 1, null, $this->owner->id);
        Notification::query()->delete();

        $closed = $this->service->graduateStudent(
            $this->student,
            $this->classroomA,
            'จบการศึกษา',
            null,
            $this->owner->id
        );

        $this->assertNotNull($closed);
        $this->assertSame(4, Notification::count());
        $this->assertEqualsCanonicalizing(
            [$this->studentUser->id, $this->teacherA->id, $this->owner->id, $this->academyAdmin->id],
            Notification::pluck('user_id')->all()
        );
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->studentUser->id,
            'type' => Notification::TYPE_STUDENT_GRADUATED,
            'related_id' => $closed->id,
        ]);
    }

    public function test_drop_student_notifies_student_homeroom_and_admins_with_reason_metadata(): void
    {
        $this->service->enrollStudent($this->student, $this->classroomA, 2, null, $this->owner->id);
        Notification::query()->delete();

        $closed = $this->service->dropStudent(
            $this->student,
            $this->classroomA,
            'ลาออกกลางคัน',
            null,
            $this->owner->id
        );

        $this->assertNotNull($closed);
        $this->assertSame(4, Notification::count());

        $notification = Notification::query()
            ->where('user_id', $this->academyAdmin->id)
            ->where('type', Notification::TYPE_STUDENT_DROPPED)
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame('ลาออกกลางคัน', $notification->metadata['reason'] ?? null);
    }

    public function test_rollover_commit_and_undo_notify_academy_admins(): void
    {
        $batch = RolloverBatch::create([
            'id' => 'batch-phase-7-test',
            'academy_id' => $this->academy->id,
            'from_academic_year_id' => $this->year2568->id,
            'to_academic_year_id' => $this->year2569->id,
            'status' => 'committed',
            'committed_at' => now(),
            'committed_by_user_id' => $this->owner->id,
            'totals' => ['promote' => 1, 'graduate' => 0],
        ]);

        event(new RolloverCommitted($batch->fresh()));

        $this->assertSame(2, Notification::count());
        $this->assertEqualsCanonicalizing(
            [$this->owner->id, $this->academyAdmin->id],
            Notification::pluck('user_id')->all()
        );
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->academyAdmin->id,
            'type' => Notification::TYPE_ROLLOVER_COMMITTED,
        ]);

        Notification::query()->delete();

        $batch->update([
            'status' => 'undone',
            'undone_at' => now(),
            'undone_by_user_id' => $this->academyAdmin->id,
        ]);

        event(new RolloverUndone($batch->fresh()));

        $this->assertSame(2, Notification::count());
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->owner->id,
            'type' => Notification::TYPE_ROLLOVER_UNDONE,
            'sender_id' => $this->academyAdmin->id,
        ]);
    }
}
