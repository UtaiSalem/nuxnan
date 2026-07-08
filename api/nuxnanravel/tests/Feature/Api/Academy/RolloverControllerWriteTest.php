<?php

namespace Tests\Feature\Api\Academy;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\RolloverBatch;
use App\Models\Student;
use App\Models\User;
use App\Services\AcademicYearRolloverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class RolloverControllerWriteTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYearRolloverService $rolloverService;

    private Academy $academy;

    private Academy $otherAcademy;

    private User $owner;

    private User $teacher;

    private User $studentUser;

    private Student $student;

    private Student $otherStudent;

    private AcademicYear $year2568;

    private AcademicYear $year2569;

    private AcademicYear $otherYear;

    private Classroom $classroomA;

    private Classroom $classroomC;

    private Classroom $otherClassroom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rolloverService = app(AcademicYearRolloverService::class);

        $this->owner = User::factory()->create(['name' => 'Owner Admin']);
        $this->teacher = User::factory()->create(['name' => 'Homeroom Teacher']);
        $this->studentUser = User::factory()->create(['name' => 'Student User']);

        $this->academy = Academy::create([
            'user_id' => $this->owner->id,
            'name' => 'Write Test Academy',
            'slug' => 'write-test-academy',
        ]);

        $this->otherAcademy = Academy::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Other Write Test Academy',
            'slug' => 'other-write-test-academy',
        ]);

        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->teacher->id,
            'role' => 'teacher',
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

        $this->otherYear = AcademicYear::create([
            'academy_id' => $this->otherAcademy->id,
            'name' => '3568',
            'start_date' => '2025-05-16',
            'end_date' => '2026-03-31',
        ]);

        $this->classroomA = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year2568->id,
            'grade_level' => 'เธก.1',
            'section' => '1',
            'name' => 'เธก.1/1',
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
            'homeroom_teacher_id' => $this->teacher->id,
        ]);

        $this->classroomC = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year2569->id,
            'grade_level' => 'เธก.2',
            'section' => '1',
            'name' => 'เธก.2/1',
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->otherClassroom = Classroom::create([
            'academy_id' => $this->otherAcademy->id,
            'academic_year_id' => $this->otherYear->id,
            'grade_level' => 'เธก.1',
            'section' => '1',
            'name' => 'Other เธก.1/1',
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->student = Student::create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->studentUser->id,
            'first_name_th' => 'เธชเธกเธซเธเธดเธ',
            'last_name_th' => 'เธ•เธฑเธงเธญเธขเนเธฒเธ',
            'student_id' => 'LC001',
            'citizen_id' => '1111111111111',
            'status' => 'active',
        ]);

        $this->otherStudent = Student::create([
            'academy_id' => $this->otherAcademy->id,
            'user_id' => User::factory()->create()->id,
            'first_name_th' => 'เธเธเธญเธทเนเธ',
            'last_name_th' => 'เธ•เนเธฒเธเนเธฃเธเน€เธฃเธตเธขเธ',
            'student_id' => 'LC999',
            'citizen_id' => '9999999999999',
            'status' => 'active',
        ]);

        ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'classroom_id' => $this->classroomA->id,
            'academic_year_id' => $this->year2568->id,
            'student_id' => $this->student->id,
            'student_number' => 10,
            'status' => ClassroomStudent::STATUS_ACTIVE,
            'enrolled_at' => now()->subMonth(),
        ]);
    }

    public function test_commit_with_valid_cached_plan_returns_created_batch_and_forgets_cache(): void
    {
        $planId = $this->cachePlanForOwner();

        $response = $this->actingAs($this->owner, 'api')
            ->postJson(route('api.academy.rollover.commit', $this->academy), [
                'plan_id' => $planId,
                'confirm_text' => '2569',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('batch.status', 'committed')
            ->assertJsonPath('batch.to_year.name', '2569')
            ->assertJsonPath('batch.totals.promote', 1);

        $this->assertFalse(Cache::has($this->planCacheKey($planId)));
        $this->assertDatabaseHas('rollover_batches', [
            'academy_id' => $this->academy->id,
            'status' => 'committed',
        ]);
    }

    public function test_commit_with_missing_cached_plan_returns_gone(): void
    {
        $this->actingAs($this->owner, 'api')
            ->postJson(route('api.academy.rollover.commit', $this->academy), [
                'plan_id' => (string) Str::uuid(),
                'confirm_text' => '2569',
            ])
            ->assertStatus(410)
            ->assertJsonPath('error', 'plan_expired');
    }

    public function test_commit_with_wrong_confirm_text_returns_validation_error_and_expected_format(): void
    {
        $planId = $this->cachePlanForOwner();

        $this->actingAs($this->owner, 'api')
            ->postJson(route('api.academy.rollover.commit', $this->academy), [
                'plan_id' => $planId,
                'confirm_text' => 'ปี 2569',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'confirm_text_mismatch')
            ->assertJsonPath('expected_format', '2569');
    }

    public function test_commit_with_cached_plan_from_other_academy_returns_422(): void
    {
        $planId = $this->cachePlanForOwner([
            'academy_id' => $this->otherAcademy->id,
            'from_academic_year_id' => $this->otherYear->id,
            'to_academic_year_id' => $this->otherYear->id,
        ]);

        $this->actingAs($this->owner, 'api')
            ->postJson(route('api.academy.rollover.commit', $this->academy), [
                'plan_id' => $planId,
                'confirm_text' => '2569',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'academy_mismatch');
    }

    public function test_commit_as_teacher_is_forbidden(): void
    {
        $planId = $this->cachePlanForOwner();

        $this->actingAs($this->teacher, 'api')
            ->postJson(route('api.academy.rollover.commit', $this->academy), [
                'plan_id' => $planId,
                'confirm_text' => '2569',
            ])
            ->assertForbidden();
    }

    public function test_commit_twice_with_same_plan_id_returns_410_on_second_call(): void
    {
        $planId = $this->cachePlanForOwner();

        $this->actingAs($this->owner, 'api')
            ->postJson(route('api.academy.rollover.commit', $this->academy), [
                'plan_id' => $planId,
                'confirm_text' => '2569',
            ])
            ->assertStatus(201);

        $this->actingAs($this->owner, 'api')
            ->postJson(route('api.academy.rollover.commit', $this->academy), [
                'plan_id' => $planId,
                'confirm_text' => '2569',
            ])
            ->assertStatus(410)
            ->assertJsonPath('error', 'plan_expired');
    }

    public function test_undo_within_window_returns_undone_batch(): void
    {
        $batch = $this->createCommittedBatch();

        $response = $this->actingAs($this->owner, 'api')
            ->postJson(route('api.academy.rollover.undo', [$this->academy, $batch]), []);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('batch.status', 'undone');

        $this->assertDatabaseHas('rollover_batches', [
            'id' => $batch->id,
            'status' => 'undone',
        ]);
    }

    public function test_undo_after_close_undo_window_returns_conflict(): void
    {
        $batch = $this->createCommittedBatch();
        $this->rolloverService->closeUndoWindow($batch->id, $this->owner);

        $this->actingAs($this->owner, 'api')
            ->postJson(route('api.academy.rollover.undo', [$this->academy, $batch]), [])
            ->assertStatus(409)
            ->assertJsonPath('error', 'cannot_undo');
    }

    public function test_undo_after_24_hours_returns_conflict(): void
    {
        $batch = $this->createCommittedBatch();

        $this->travel(25)->hours();

        $this->actingAs($this->owner, 'api')
            ->postJson(route('api.academy.rollover.undo', [$this->academy, $batch]), [])
            ->assertStatus(409)
            ->assertJsonPath('error', 'cannot_undo');

        $this->travelBack();
    }

    public function test_undo_cross_academy_batch_returns_404(): void
    {
        $batch = RolloverBatch::create([
            'id' => (string) Str::uuid(),
            'academy_id' => $this->otherAcademy->id,
            'from_academic_year_id' => $this->otherYear->id,
            'to_academic_year_id' => $this->otherYear->id,
            'status' => 'committed',
            'plan_summary' => ['before' => [], 'entries' => []],
            'totals' => ['promote' => 0],
            'committed_at' => now(),
            'committed_by_user_id' => $this->owner->id,
        ]);

        $this->actingAs($this->owner, 'api')
            ->postJson(route('api.academy.rollover.undo', [$this->academy, $batch]), [])
            ->assertNotFound();
    }

    public function test_undo_as_teacher_is_forbidden(): void
    {
        $batch = $this->createCommittedBatch();

        $this->actingAs($this->teacher, 'api')
            ->postJson(route('api.academy.rollover.undo', [$this->academy, $batch]), [])
            ->assertForbidden();
    }

    public function test_undo_when_student_has_newer_active_changes_returns_conflict(): void
    {
        $batch = $this->createCommittedBatch();

        $newerClassroom = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year2569->id,
            'grade_level' => 'เธก.2',
            'section' => '2',
            'name' => 'เธก.2/2',
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
        ]);

        ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'classroom_id' => $newerClassroom->id,
            'academic_year_id' => $this->year2569->id,
            'student_id' => $this->student->id,
            'student_number' => 12,
            'status' => ClassroomStudent::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);

        $this->actingAs($this->owner, 'api')
            ->postJson(route('api.academy.rollover.undo', [$this->academy, $batch]), [])
            ->assertStatus(409)
            ->assertJsonPath('error', 'cannot_undo');
    }

    public function test_close_undo_as_admin_returns_updated_batch(): void
    {
        $batch = $this->createCommittedBatch();

        $response = $this->actingAs($this->owner, 'api')
            ->postJson(route('api.academy.rollover.closeUndo', [$this->academy, $batch]), []);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotNull($response->json('batch.undo_closed_at'));
    }

    public function test_close_undo_as_teacher_is_forbidden(): void
    {
        $batch = $this->createCommittedBatch();

        $this->actingAs($this->teacher, 'api')
            ->postJson(route('api.academy.rollover.closeUndo', [$this->academy, $batch]), [])
            ->assertForbidden();
    }

    public function test_close_undo_cross_academy_batch_returns_404(): void
    {
        $batch = RolloverBatch::create([
            'id' => (string) Str::uuid(),
            'academy_id' => $this->otherAcademy->id,
            'from_academic_year_id' => $this->otherYear->id,
            'to_academic_year_id' => $this->otherYear->id,
            'status' => 'committed',
            'plan_summary' => ['before' => [], 'entries' => []],
            'totals' => ['promote' => 0],
            'committed_at' => now(),
            'committed_by_user_id' => $this->owner->id,
        ]);

        $this->actingAs($this->owner, 'api')
            ->postJson(route('api.academy.rollover.closeUndo', [$this->academy, $batch]), [])
            ->assertNotFound();
    }

    public function test_close_undo_on_undone_batch_is_idempotent_no_op(): void
    {
        $batch = $this->createCommittedBatch();
        $this->rolloverService->undoRollover($batch->id, $this->owner);

        $response = $this->actingAs($this->owner, 'api')
            ->postJson(route('api.academy.rollover.closeUndo', [$this->academy, $batch]), []);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('batch.status', 'undone');

        $this->assertNotNull($response->json('batch.undo_closed_at'));
    }

    public function test_undo_end_to_end_after_commit_with_mixed_skip_and_promote_returns_ok(): void
    {
        // Cache a plan with student (promote) and otherStudent (skip)
        // Since otherStudent is in otherAcademy, let's create student2 in this academy instead for mixed plan
        $student2 = Student::create([
            'academy_id' => $this->academy->id,
            'user_id' => User::factory()->create()->id,
            'first_name_th' => 'สมเกียรติ',
            'last_name_th' => 'รักดี',
            'student_id' => 'LC002',
            'citizen_id' => '1111111111112',
            'status' => 'active',
        ]);
        ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'classroom_id' => $this->classroomA->id,
            'academic_year_id' => $this->year2568->id,
            'student_id' => $student2->id,
            'student_number' => 11,
            'status' => ClassroomStudent::STATUS_ACTIVE,
            'enrolled_at' => now()->subMonth(),
        ]);

        $planId = (string) Str::uuid();
        Cache::put($this->planCacheKey($planId), [
            'academy_id' => $this->academy->id,
            'from_academic_year_id' => $this->year2568->id,
            'to_academic_year_id' => $this->year2569->id,
            'entries' => [
                [
                    'student_id' => $this->student->id,
                    'action' => 'promote',
                    'from_classroom_id' => $this->classroomA->id,
                    'to_classroom_id' => $this->classroomC->id,
                    'reason' => 'เลื่อนชั้นปกติ',
                ],
                [
                    'student_id' => $student2->id,
                    'action' => 'skip',
                    'from_classroom_id' => $this->classroomA->id,
                    'to_classroom_id' => null,
                    'reason' => 'ข้ามย้ายห้อง',
                ],
            ],
            'summary' => [
                'promote' => 1,
                'graduate' => 0,
                'repeat' => 0,
                'drop' => 0,
                'new_intake' => 0,
                'skip' => 1,
            ],
            'warnings' => [],
        ], 900);

        // Commit via controller
        $commitResponse = $this->actingAs($this->owner, 'api')
            ->postJson(route('api.academy.rollover.commit', $this->academy), [
                'plan_id' => $planId,
                'confirm_text' => '2569',
            ]);
        $commitResponse->assertStatus(201);
        $batchId = $commitResponse->json('batch.id');

        // Undo via controller
        $response = $this->actingAs($this->owner, 'api')
            ->postJson(route('api.academy.rollover.undo', [$this->academy, $batchId]), []);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('batch.status', 'undone');
    }

    private function cachePlanForOwner(array $overrides = []): string
    {
        $planId = (string) Str::uuid();

        Cache::put($this->planCacheKey($planId), array_replace_recursive([
            'academy_id' => $this->academy->id,
            'from_academic_year_id' => $this->year2568->id,
            'to_academic_year_id' => $this->year2569->id,
            'entries' => [
                [
                    'student_id' => $this->student->id,
                    'action' => 'promote',
                    'from_classroom_id' => $this->classroomA->id,
                    'to_classroom_id' => $this->classroomC->id,
                    'reason' => 'เน€เธฅเธทเนเธญเธเธเธฑเนเธเธเธเธ•เธด',
                ],
            ],
            'summary' => [
                'promote' => 1,
                'graduate' => 0,
                'repeat' => 0,
                'drop' => 0,
                'new_intake' => 0,
                'skip' => 0,
            ],
            'warnings' => [],
        ], $overrides), 900);

        return $planId;
    }

    private function planCacheKey(string $planId): string
    {
        return "rollover_plan:{$planId}:user:{$this->owner->id}";
    }

    private function createCommittedBatch(): RolloverBatch
    {
        $plan = $this->rolloverService->planRollover(
            $this->academy,
            $this->year2568,
            $this->year2569,
            [
                [
                    'student_id' => $this->student->id,
                    'action' => 'promote',
                    'from_classroom_id' => $this->classroomA->id,
                    'to_classroom_id' => $this->classroomC->id,
                    'reason' => 'เน€เธฅเธทเนเธญเธเธเธฑเนเธเธเธเธ•เธด',
                ],
            ]
        );

        return $this->rolloverService->commitRollover($plan, $this->owner);
    }
}
