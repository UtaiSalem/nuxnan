<?php

namespace Tests\Feature\Academy\Enrollment;

use App\Http\Resources\Learn\Academy\Enrollment\ClassroomStudentResource;
use App\Http\Resources\Learn\Academy\Enrollment\RolloverBatchResource;
use App\Http\Resources\Learn\Academy\Enrollment\StudentSummaryResource;
use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\RolloverBatch;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ResourceShapeTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $teacher;

    private Academy $academy;

    private AcademicYear $fromYear;

    private AcademicYear $toYear;

    private Classroom $classroom;

    private Student $student;

    private ClassroomStudent $enrollment;

    private RolloverBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->teacher = User::factory()->create();

        $this->academy = Academy::create([
            'user_id' => $this->owner->id,
            'name' => 'Enrollment Resource Academy',
            'slug' => 'enrollment-resource-academy',
        ]);

        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->teacher->id,
            'role' => 'teacher',
        ]);

        $this->fromYear = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2568',
            'start_date' => '2025-05-16',
            'end_date' => '2026-03-31',
        ]);

        $this->toYear = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2569',
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ]);

        $this->classroom = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->fromYear->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
        ]);

        $studentUser = User::factory()->create();
        $this->student = Student::create([
            'academy_id' => $this->academy->id,
            'user_id' => $studentUser->id,
            'first_name_th' => 'สายไหม',
            'last_name_th' => 'ตัวอย่าง',
            'nickname' => 'ไหม',
            'student_id' => 'RES001',
            'citizen_id' => '1234567890123',
            'status' => 'active',
            'class_level' => '1',
            'class_section' => '1',
        ]);

        $this->enrollment = ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->fromYear->id,
            'classroom_id' => $this->classroom->id,
            'student_id' => $this->student->id,
            'student_number' => 7,
            'status' => ClassroomStudent::STATUS_ACTIVE,
            'enrolled_at' => '2025-05-20',
            'created_by_user_id' => $this->owner->id,
        ]);

        $this->batch = RolloverBatch::create([
            'id' => '99999999-aaaa-bbbb-cccc-dddddddddddd',
            'academy_id' => $this->academy->id,
            'from_academic_year_id' => $this->fromYear->id,
            'to_academic_year_id' => $this->toYear->id,
            'status' => 'committed',
            'committed_at' => Carbon::parse('2026-06-21 09:00:00'),
            'committed_by_user_id' => $this->owner->id,
            'plan_summary' => [
                'before' => [
                    $this->student->id => [
                        'status' => 'active',
                        'class_level' => '1',
                    ],
                ],
                'entries' => [
                    ['student_id' => $this->student->id, 'action' => 'promote'],
                ],
            ],
            'totals' => ['promote' => 1],
            'notes' => 'resource-note',
        ]);
    }

    public function test_classroom_student_resource_contains_core_shape_and_status_text(): void
    {
        $resource = new ClassroomStudentResource(
            $this->enrollment->load(['academicYear', 'createdBy', 'classroom', 'student'])
        );

        $data = $resource->resolve($this->makeRequestFor($this->owner));

        $this->assertSame(ClassroomStudent::STATUS_ACTIVE, $data['status']);
        $this->assertSame('กำลังศึกษา', $data['status_text']);
        $this->assertSame('2568', $data['academic_year']['name']);
        $this->assertSame('ม.1/1', $data['classroom']['display_name']);
        $this->assertSame('สายไหม', $data['student']['first_name_th']);
    }

    public function test_classroom_student_resource_hides_optional_relationships_when_not_loaded(): void
    {
        $data = (new ClassroomStudentResource($this->enrollment))
            ->resolve($this->makeRequestFor($this->owner));

        $this->assertArrayNotHasKey('academic_year', $data);
        $this->assertArrayNotHasKey('classroom', $data);
        $this->assertArrayNotHasKey('student', $data);
        $this->assertArrayNotHasKey('created_by', $data);
    }

    public function test_rollover_batch_resource_hides_plan_summary_for_non_admin(): void
    {
        $resource = new RolloverBatchResource(
            $this->batch->load(['academy', 'fromAcademicYear', 'toAcademicYear', 'committedBy', 'undoneBy'])
        );

        $data = $resource->resolve($this->makeRequestFor($this->teacher));

        $this->assertNull($data['plan_summary']);
        $this->assertArrayNotHasKey('citizen_id', $data);
    }

    public function test_rollover_batch_resource_reports_undoable_state(): void
    {
        $resource = new RolloverBatchResource($this->batch->load('academy'));

        $data = $resource->resolve($this->makeRequestFor($this->owner));

        $this->assertTrue($data['is_undoable']);

        $this->batch->update([
            'undo_closed_at' => now(),
        ]);

        $dataAfterClose = (new RolloverBatchResource($this->batch->fresh()->load('academy')))
            ->resolve($this->makeRequestFor($this->owner));

        $this->assertFalse($dataAfterClose['is_undoable']);
    }

    public function test_rollover_batch_resource_computes_undo_expiry_plus_one_day(): void
    {
        $data = (new RolloverBatchResource($this->batch->load('academy')))
            ->resolve($this->makeRequestFor($this->owner));

        $this->assertSame(
            $this->batch->committed_at->copy()->addDay()->toIso8601String(),
            $data['undo_expires_at']
        );
    }

    public function test_student_summary_resource_excludes_pii_fields(): void
    {
        $data = (new StudentSummaryResource($this->student))
            ->resolve($this->makeRequestFor($this->owner));

        $this->assertArrayNotHasKey('citizen_id', $data);
        $this->assertArrayNotHasKey('email', $data);
        $this->assertArrayNotHasKey('phone', $data);
        $this->assertSame('RES001', $data['student_id']);
    }

    private function makeRequestFor(User $user): Request
    {
        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => $user);

        return $request;
    }
}
