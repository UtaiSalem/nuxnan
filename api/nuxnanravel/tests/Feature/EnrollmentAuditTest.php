<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\RolloverBatch;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Academy $academy;

    private AcademicYear $year2568;

    private AcademicYear $year2569;

    private Classroom $classroomA;

    private Classroom $classroomB;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->academy = Academy::create([
            'user_id' => $this->admin->id,
            'name' => 'Test Academy',
            'slug' => 'test-academy',
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
            'capacity' => 50,
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->classroomB = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year2568->id,
            'grade_level' => 'ม.1',
            'section' => '2',
            'name' => 'ม.1/2',
            'capacity' => 50,
            'is_active' => true,
            'status' => 'active',
        ]);

        $studentUser = User::factory()->create();
        $this->student = Student::create([
            'user_id' => $studentUser->id,
            'student_id' => 'S12345',
            'citizen_id' => '1100101234567',
            'first_name_th' => 'ทดสอบ',
            'last_name_th' => 'เรียนดี',
            'status' => 'active',
        ]);
    }

    public function test_classroom_student_creation_audits_correctly(): void
    {
        $this->actingAs($this->admin);

        $classroomStudent = ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'classroom_id' => $this->classroomA->id,
            'student_id' => $this->student->id,
            'academic_year_id' => $this->year2568->id,
            'student_number' => 1,
            'status' => ClassroomStudent::STATUS_ACTIVE,
            'enrolled_at' => '2025-05-16',
            'created_by_user_id' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => ClassroomStudent::class,
            'entity_id' => $classroomStudent->id,
            'action' => AuditLog::ACTION_CREATED,
            'user_id' => $this->admin->id,
            'module' => 'enrollment',
        ]);

        $log = AuditLog::where('entity_type', ClassroomStudent::class)
            ->where('entity_id', $classroomStudent->id)
            ->firstOrFail();

        $this->assertEquals('enrollment', $log->module);
        $this->assertNotNull($log->new_values);
        $this->assertEquals(ClassroomStudent::STATUS_ACTIVE, $log->new_values['status']);
    }

    public function test_classroom_student_graduation_audits_correctly(): void
    {
        $this->actingAs($this->admin);

        $classroomStudent = ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'classroom_id' => $this->classroomA->id,
            'student_id' => $this->student->id,
            'academic_year_id' => $this->year2568->id,
            'student_number' => 1,
            'status' => ClassroomStudent::STATUS_ACTIVE,
            'enrolled_at' => '2025-05-16',
            'created_by_user_id' => $this->admin->id,
        ]);

        AuditLog::query()->delete();

        // Simulate graduate logic: update status to graduated and set left_at and leave_reason
        $classroomStudent->update([
            'status' => ClassroomStudent::STATUS_GRADUATED,
            'left_at' => '2026-03-31',
            'leave_reason' => 'Graduated successfully',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => ClassroomStudent::class,
            'entity_id' => $classroomStudent->id,
            'action' => AuditLog::ACTION_UPDATED,
            'user_id' => $this->admin->id,
            'module' => 'enrollment',
        ]);

        $log = AuditLog::where('entity_type', ClassroomStudent::class)
            ->where('entity_id', $classroomStudent->id)
            ->firstOrFail();

        $this->assertEquals(ClassroomStudent::STATUS_ACTIVE, $log->old_values['status']);
        $this->assertEquals(ClassroomStudent::STATUS_GRADUATED, $log->new_values['status']);
        $this->assertEquals('Graduated successfully', $log->new_values['leave_reason']);
        $this->assertEquals('2026-03-31', \Illuminate\Support\Carbon::parse($log->new_values['left_at'])->setTimezone(config('app.timezone'))->format('Y-m-d'));
    }

    public function test_classroom_student_transfer_audits_correctly(): void
    {
        $this->actingAs($this->admin);

        $classroomStudent = ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'classroom_id' => $this->classroomA->id,
            'student_id' => $this->student->id,
            'academic_year_id' => $this->year2568->id,
            'student_number' => 1,
            'status' => ClassroomStudent::STATUS_ACTIVE,
            'enrolled_at' => '2025-05-16',
            'created_by_user_id' => $this->admin->id,
        ]);

        AuditLog::query()->delete();

        // Simulate transfer: update old row to TRANSFERRED, create new row with ACTIVE
        $classroomStudent->update([
            'status' => ClassroomStudent::STATUS_TRANSFERRED,
            'left_at' => '2025-10-01',
            'leave_reason' => 'Transferred to section 2',
        ]);

        $newRow = ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'classroom_id' => $this->classroomB->id,
            'student_id' => $this->student->id,
            'academic_year_id' => $this->year2568->id,
            'student_number' => 2,
            'status' => ClassroomStudent::STATUS_ACTIVE,
            'enrolled_at' => '2025-10-01',
            'created_by_user_id' => $this->admin->id,
        ]);

        // Verify update audit on the old row
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => ClassroomStudent::class,
            'entity_id' => $classroomStudent->id,
            'action' => AuditLog::ACTION_UPDATED,
            'module' => 'enrollment',
        ]);

        // Verify create audit on the new row
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => ClassroomStudent::class,
            'entity_id' => $newRow->id,
            'action' => AuditLog::ACTION_CREATED,
            'module' => 'enrollment',
        ]);

        $updateLog = AuditLog::where('entity_type', ClassroomStudent::class)
            ->where('entity_id', $classroomStudent->id)
            ->where('action', AuditLog::ACTION_UPDATED)
            ->firstOrFail();

        $this->assertEquals(ClassroomStudent::STATUS_ACTIVE, $updateLog->old_values['status']);
        $this->assertEquals(ClassroomStudent::STATUS_TRANSFERRED, $updateLog->new_values['status']);
        $this->assertEquals('Transferred to section 2', $updateLog->new_values['leave_reason']);
        $this->assertEquals('2025-10-01', \Illuminate\Support\Carbon::parse($updateLog->new_values['left_at'])->setTimezone(config('app.timezone'))->format('Y-m-d'));
    }

    public function test_rollover_batch_creation_and_update_audits_correctly(): void
    {
        $this->actingAs($this->admin);

        $batch = RolloverBatch::create([
            'id' => 'test-batch-uuid-1234',
            'academy_id' => $this->academy->id,
            'from_academic_year_id' => $this->year2568->id,
            'to_academic_year_id' => $this->year2569->id,
            'status' => 'planned',
            'plan_summary' => ['promoted' => 1],
            'totals' => ['promoted' => 1],
            'notes' => 'Test batch note',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => RolloverBatch::class,
            'entity_id' => $batch->id,
            'action' => AuditLog::ACTION_CREATED,
            'user_id' => $this->admin->id,
            'module' => 'enrollment',
        ]);

        $createLog = AuditLog::where('entity_type', RolloverBatch::class)
            ->where('entity_id', $batch->id)
            ->where('action', AuditLog::ACTION_CREATED)
            ->firstOrFail();

        // plan_summary should be redacted
        $this->assertEquals('[REDACTED]', $createLog->new_values['plan_summary']);

        // Update rollover batch status
        AuditLog::query()->where('action', AuditLog::ACTION_CREATED)->delete();

        $batch->update([
            'status' => 'committed',
            'committed_at' => now(),
            'committed_by_user_id' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => RolloverBatch::class,
            'entity_id' => $batch->id,
            'action' => AuditLog::ACTION_UPDATED,
            'user_id' => $this->admin->id,
            'module' => 'enrollment',
        ]);

        $log = AuditLog::where('entity_type', RolloverBatch::class)
            ->where('entity_id', $batch->id)
            ->firstOrFail();

        $this->assertEquals('planned', $log->old_values['status']);
        $this->assertEquals('committed', $log->new_values['status']);
        // plan_summary should still be redacted in new_values if present
        if (isset($log->new_values['plan_summary'])) {
            $this->assertEquals('[REDACTED]', $log->new_values['plan_summary']);
        }
    }
}
