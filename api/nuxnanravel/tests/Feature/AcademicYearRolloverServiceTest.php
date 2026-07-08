<?php

namespace Tests\Feature;

use App\Exceptions\RolloverNotUndoable;
use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentAcademicInfo;
use App\Models\User;
use App\Services\AcademicYearRolloverService;
use App\Services\Rollover\RolloverPlan;
use App\Services\StudentEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AcademicYearRolloverServiceTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYearRolloverService $service;

    private StudentEnrollmentService $enrollService;

    private Academy $academy;

    private AcademicYear $year2568;

    private AcademicYear $year2569;

    private Classroom $class1_1_2568;

    private Classroom $class6_1_2568;

    private Classroom $class2_1_2569;

    private Student $studentActive1;

    private Student $studentActive6;

    private Student $studentPending;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AcademicYearRolloverService::class);
        $this->enrollService = app(StudentEnrollmentService::class);

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

        // Classrooms in 2568
        $this->class1_1_2568 = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year2568->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
            'capacity' => 50,
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->class6_1_2568 = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year2568->id,
            'grade_level' => 'ม.6',
            'section' => '1',
            'name' => 'ม.6/1',
            'capacity' => 50,
            'is_active' => true,
            'status' => 'active',
        ]);

        // Classroom in 2569
        $this->class2_1_2569 = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year2569->id,
            'grade_level' => 'ม.2',
            'section' => '1',
            'name' => 'ม.2/1',
            'capacity' => 50,
            'is_active' => true,
            'status' => 'active',
        ]);

        // Create Students
        $user1 = User::factory()->create();
        $this->studentActive1 = Student::create([
            'user_id' => $user1->id,
            'academy_id' => $this->academy->id,
            'first_name_th' => 'สมชาย',
            'last_name_th' => 'สายเสมอ',
            'student_id' => 'S001',
            'citizen_id' => '1234567890121',
        ]);

        $user2 = User::factory()->create();
        $this->studentActive6 = Student::create([
            'user_id' => $user2->id,
            'academy_id' => $this->academy->id,
            'first_name_th' => 'สมศรี',
            'last_name_th' => 'มีมงคล',
            'student_id' => 'S006',
            'citizen_id' => '1234567890122',
        ]);

        $user3 = User::factory()->create();
        $this->studentPending = Student::create([
            'user_id' => $user3->id,
            'academy_id' => $this->academy->id,
            'first_name_th' => 'เด็กใหม่',
            'last_name_th' => 'มีไฟ',
            'student_id' => 'SNEW',
            'citizen_id' => '1234567890123',
            'class_level' => 'ม.2', // Intending to enter ม.2
            'class_section' => '1',
        ]);

        // Enroll active students
        $this->enrollService->enrollStudent($this->studentActive1, $this->class1_1_2568);
        $this->enrollService->enrollStudent($this->studentActive6, $this->class6_1_2568);
    }

    public function test_preview_suggests_promote_for_normal_flow(): void
    {
        $preview = $this->service->previewRollover($this->academy, $this->year2568, $this->year2569);

        // Find studentActive1 suggested entry
        $entry1 = collect($preview['entries'])->firstWhere('student_id', $this->studentActive1->id);
        $this->assertNotNull($entry1);
        $this->assertEquals('promote', $entry1['action']);
        $this->assertEquals($this->class2_1_2569->id, $entry1['to_classroom_id']);
    }

    public function test_preview_suggests_graduate_for_m6(): void
    {
        $preview = $this->service->previewRollover($this->academy, $this->year2568, $this->year2569);

        $entry6 = collect($preview['entries'])->firstWhere('student_id', $this->studentActive6->id);
        $this->assertNotNull($entry6);
        $this->assertEquals('graduate', $entry6['action']);
        $this->assertNull($entry6['to_classroom_id']);
    }

    public function test_preview_includes_pending_intake(): void
    {
        $preview = $this->service->previewRollover($this->academy, $this->year2568, $this->year2569);

        $entryNew = collect($preview['entries'])->firstWhere('student_id', $this->studentPending->id);
        $this->assertNotNull($entryNew);
        $this->assertEquals('new_intake', $entryNew['action']);
        $this->assertEquals($this->class2_1_2569->id, $entryNew['to_classroom_id']);
    }

    public function test_preview_warns_on_missing_target_classroom(): void
    {
        // Delete class2_1_2569 so ม.2 classroom is missing
        $this->class2_1_2569->delete();

        $preview = $this->service->previewRollover($this->academy, $this->year2568, $this->year2569);

        // Active 1 should be action = skip because ม.2 doesn't exist
        $entry1 = collect($preview['entries'])->firstWhere('student_id', $this->studentActive1->id);
        $this->assertEquals('skip', $entry1['action']);
        $this->assertContains('ไม่พบห้องเรียนระดับชั้น ม.2 ในปีการศึกษา 2569', $preview['warnings']);
    }

    public function test_plan_rollover_validates_duplicate_entries(): void
    {
        $userMapping = [
            [
                'student_id' => $this->studentActive1->id,
                'action' => 'promote',
                'from_classroom_id' => $this->class1_1_2568->id,
                'to_classroom_id' => $this->class2_1_2569->id,
            ],
            [
                'student_id' => $this->studentActive1->id, // DUPLICATE
                'action' => 'repeat',
                'from_classroom_id' => $this->class1_1_2568->id,
                'to_classroom_id' => $this->class2_1_2569->id,
            ],
        ];

        $this->expectException(ValidationException::class);
        $this->service->planRollover($this->academy, $this->year2568, $this->year2569, $userMapping);
    }

    public function test_rollover_plan_from_array_round_trip_matches_to_array(): void
    {
        $plan = new RolloverPlan(
            academyId: $this->academy->id,
            fromYearId: $this->year2568->id,
            toYearId: $this->year2569->id,
            entries: [
                [
                    'student_id' => $this->studentActive1->id,
                    'action' => 'promote',
                    'from_classroom_id' => $this->class1_1_2568->id,
                    'to_classroom_id' => $this->class2_1_2569->id,
                    'reason' => 'เน€เธฅเธทเนเธญเธเธเธฑเนเธ',
                ],
            ],
            summary: [
                'promote' => 1,
                'graduate' => 0,
                'repeat' => 0,
                'drop' => 0,
                'new_intake' => 0,
                'skip' => 0,
            ],
            warnings: ['warning-1'],
        );

        $rebuilt = RolloverPlan::fromArray($plan->toArray());

        $this->assertEquals($plan->toArray(), $rebuilt->toArray());
    }

    public function test_commit_rollover_commits_all_types_correctly(): void
    {
        // We will test commit with: studentActive1 (promote), studentActive6 (graduate), studentPending (new_intake)
        $userMapping = [
            [
                'student_id' => $this->studentActive1->id,
                'action' => 'promote',
                'from_classroom_id' => $this->class1_1_2568->id,
                'to_classroom_id' => $this->class2_1_2569->id,
            ],
            [
                'student_id' => $this->studentActive6->id,
                'action' => 'graduate',
                'from_classroom_id' => $this->class6_1_2568->id,
                'to_classroom_id' => null,
            ],
            [
                'student_id' => $this->studentPending->id,
                'action' => 'new_intake',
                'from_classroom_id' => null,
                'to_classroom_id' => $this->class2_1_2569->id,
            ],
        ];

        $plan = $this->service->planRollover($this->academy, $this->year2568, $this->year2569, $userMapping);
        $batch = $this->service->commitRollover($plan, $this->admin);

        $this->assertDatabaseHas('rollover_batches', [
            'id' => $batch->id,
            'status' => 'committed',
        ]);

        // Check studentActive1
        $this->assertDatabaseHas('classroom_students', [
            'classroom_id' => $this->class1_1_2568->id,
            'student_id' => $this->studentActive1->id,
            'status' => 'promoted',
            'rollover_batch_id' => $batch->id,
        ]);
        $this->assertDatabaseHas('classroom_students', [
            'classroom_id' => $this->class2_1_2569->id,
            'student_id' => $this->studentActive1->id,
            'status' => 'active',
            'rollover_batch_id' => $batch->id,
        ]);

        // Check studentActive6
        $this->assertDatabaseHas('classroom_students', [
            'classroom_id' => $this->class6_1_2568->id,
            'student_id' => $this->studentActive6->id,
            'status' => 'graduated',
            'rollover_batch_id' => $batch->id,
        ]);

        // Check studentPending
        $this->assertDatabaseHas('classroom_students', [
            'classroom_id' => $this->class2_1_2569->id,
            'student_id' => $this->studentPending->id,
            'status' => 'active',
            'rollover_batch_id' => $batch->id,
        ]);

        $this->assertEquals(1, $batch->totals['promote']);
        $this->assertEquals(1, $batch->totals['graduate']);
        $this->assertEquals(1, $batch->totals['new_intake']);
    }

    public function test_commit_rollover_is_idempotent(): void
    {
        $userMapping = [
            [
                'student_id' => $this->studentActive1->id,
                'action' => 'promote',
                'from_classroom_id' => $this->class1_1_2568->id,
                'to_classroom_id' => $this->class2_1_2569->id,
            ],
        ];

        $plan = $this->service->planRollover($this->academy, $this->year2568, $this->year2569, $userMapping);

        $batch1 = $this->service->commitRollover($plan, $this->admin);
        $this->assertNotNull($batch1);

        // Commit same plan again -> unique constraints will make updateOrCreate safely overwrite, not double insert
        $batch2 = $this->service->commitRollover($plan, $this->admin);
        $this->assertNotNull($batch2);

        $count = ClassroomStudent::where('classroom_id', $this->class2_1_2569->id)
            ->where('student_id', $this->studentActive1->id)
            ->count();
        $this->assertEquals(1, $count);
    }

    public function test_undo_rollover_reverts_state_within_24_hours(): void
    {
        $userMapping = [
            [
                'student_id' => $this->studentActive1->id,
                'action' => 'promote',
                'from_classroom_id' => $this->class1_1_2568->id,
                'to_classroom_id' => $this->class2_1_2569->id,
            ],
            [
                'student_id' => $this->studentActive6->id,
                'action' => 'graduate',
                'from_classroom_id' => $this->class6_1_2568->id,
                'to_classroom_id' => null,
            ],
            [
                'student_id' => $this->studentPending->id,
                'action' => 'new_intake',
                'from_classroom_id' => null,
                'to_classroom_id' => $this->class2_1_2569->id,
            ],
        ];

        $plan = $this->service->planRollover($this->academy, $this->year2568, $this->year2569, $userMapping);
        $batch = $this->service->commitRollover($plan, $this->admin);

        // Trigger undo
        $undoneBatch = $this->service->undoRollover($batch->id, $this->admin);

        $this->assertEquals('undone', $undoneBatch->status);
        $this->assertNotNull($undoneBatch->undone_at);

        // Verify Student 1 is back in class1_1_2568 as active, class2_1_2569 enrollment deleted
        $this->assertDatabaseHas('classroom_students', [
            'classroom_id' => $this->class1_1_2568->id,
            'student_id' => $this->studentActive1->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseMissing('classroom_students', [
            'classroom_id' => $this->class2_1_2569->id,
            'student_id' => $this->studentActive1->id,
        ]);

        // Verify Student 6 is back in class6_1_2568 as active
        $this->assertDatabaseHas('classroom_students', [
            'classroom_id' => $this->class6_1_2568->id,
            'student_id' => $this->studentActive6->id,
            'status' => 'active',
        ]);

        // Verify studentPending enrollment deleted
        $this->assertDatabaseMissing('classroom_students', [
            'classroom_id' => $this->class2_1_2569->id,
            'student_id' => $this->studentPending->id,
        ]);

        // Verify StudentAcademicInfo reverted
        $this->assertDatabaseHas('student_academic_info', [
            'student_id' => $this->studentActive1->id,
            'classroom_id' => $this->class1_1_2568->id,
            'is_current' => true,
        ]);
        $this->assertDatabaseMissing('student_academic_info', [
            'student_id' => $this->studentActive1->id,
            'classroom_id' => $this->class2_1_2569->id,
        ]);
    }

    public function test_undo_rollover_after_24_hours_fails(): void
    {
        $userMapping = [
            [
                'student_id' => $this->studentActive1->id,
                'action' => 'promote',
                'from_classroom_id' => $this->class1_1_2568->id,
                'to_classroom_id' => $this->class2_1_2569->id,
            ],
        ];

        $plan = $this->service->planRollover($this->academy, $this->year2568, $this->year2569, $userMapping);
        $batch = $this->service->commitRollover($plan, $this->admin);

        // Travel 25 hours forward
        Carbon::setTestNow(now()->addHours(25));

        $this->expectException(RolloverNotUndoable::class);
        $this->service->undoRollover($batch->id, $this->admin);
    }

    public function test_undo_rollover_after_manually_closed_fails(): void
    {
        $userMapping = [
            [
                'student_id' => $this->studentActive1->id,
                'action' => 'promote',
                'from_classroom_id' => $this->class1_1_2568->id,
                'to_classroom_id' => $this->class2_1_2569->id,
            ],
        ];

        $plan = $this->service->planRollover($this->academy, $this->year2568, $this->year2569, $userMapping);
        $batch = $this->service->commitRollover($plan, $this->admin);

        $this->service->closeUndoWindow($batch->id, $this->admin);

        $this->expectException(RolloverNotUndoable::class);
        $this->service->undoRollover($batch->id, $this->admin);
    }

    public function test_close_undo_window_sets_timestamp(): void
    {
        $userMapping = [
            [
                'student_id' => $this->studentActive1->id,
                'action' => 'promote',
                'from_classroom_id' => $this->class1_1_2568->id,
                'to_classroom_id' => $this->class2_1_2569->id,
            ],
        ];

        $plan = $this->service->planRollover($this->academy, $this->year2568, $this->year2569, $userMapping);
        $batch = $this->service->commitRollover($plan, $this->admin);

        $this->service->closeUndoWindow($batch->id, $this->admin);

        $batch->refresh();
        $this->assertNotNull($batch->undo_closed_at);
        $this->assertFalse($batch->isUndoable());
    }

    public function test_commit_excludes_skip_entries_from_before_snapshots(): void
    {
        $userMapping = [
            [
                'student_id' => $this->studentActive1->id,
                'action' => 'promote',
                'from_classroom_id' => $this->class1_1_2568->id,
                'to_classroom_id' => $this->class2_1_2569->id,
            ],
            [
                'student_id' => $this->studentActive6->id,
                'action' => 'skip',
                'from_classroom_id' => $this->class6_1_2568->id,
                'to_classroom_id' => null,
            ],
        ];

        $plan = $this->service->planRollover($this->academy, $this->year2568, $this->year2569, $userMapping);
        $batch = $this->service->commitRollover($plan, $this->admin);

        $before = $batch->plan_summary['before'];
        $this->assertArrayHasKey($this->studentActive1->id, $before);
        $this->assertArrayNotHasKey($this->studentActive6->id, $before);
    }

    public function test_undo_succeeds_when_skipped_student_has_unrelated_active_enrollment(): void
    {
        // 1. Commit with studentActive1 (promote) and studentActive6 (skip)
        $userMapping = [
            [
                'student_id' => $this->studentActive1->id,
                'action' => 'promote',
                'from_classroom_id' => $this->class1_1_2568->id,
                'to_classroom_id' => $this->class2_1_2569->id,
            ],
            [
                'student_id' => $this->studentActive6->id,
                'action' => 'skip',
                'from_classroom_id' => $this->class6_1_2568->id,
                'to_classroom_id' => null,
            ],
        ];

        $plan = $this->service->planRollover($this->academy, $this->year2568, $this->year2569, $userMapping);
        $batch = $this->service->commitRollover($plan, $this->admin);

        // 2. Mock that skipped student Active6 gets enrolled in some unrelated active classroom in the meantime (e.g. another year or same year different room)
        $unrelatedClass = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year2569->id,
            'grade_level' => 'ม.6',
            'section' => '2',
            'name' => 'ม.6/2',
            'capacity' => 50,
            'is_active' => true,
            'status' => 'active',
        ]);
        $this->enrollService->enrollStudent($this->studentActive6, $unrelatedClass);

        // 3. Undo should succeed because studentActive6 is skip, hence not in beforeSnapshots keys checked by E8
        $undoneBatch = $this->service->undoRollover($batch->id, $this->admin);
        $this->assertEquals('undone', $undoneBatch->status);
    }

    public function test_undo_does_not_delete_target_year_sai_for_skip_students(): void
    {
        // Create a target year SAI for skip student Active6 prior to rollover
        StudentAcademicInfo::create([
            'student_id' => $this->studentActive6->id,
            'classroom_id' => $this->class6_1_2568->id, // just dummy
            'academic_year' => '2569',
            'is_current' => true,
        ]);

        $userMapping = [
            [
                'student_id' => $this->studentActive1->id,
                'action' => 'promote',
                'from_classroom_id' => $this->class1_1_2568->id,
                'to_classroom_id' => $this->class2_1_2569->id,
            ],
            [
                'student_id' => $this->studentActive6->id,
                'action' => 'skip',
                'from_classroom_id' => $this->class6_1_2568->id,
                'to_classroom_id' => null,
            ],
        ];

        $plan = $this->service->planRollover($this->academy, $this->year2568, $this->year2569, $userMapping);
        $batch = $this->service->commitRollover($plan, $this->admin);

        // Undo
        $this->service->undoRollover($batch->id, $this->admin);

        // Active6's 2569 SAI should STILL exist because she was skipped and not affected by undo delete
        $this->assertDatabaseHas('student_academic_info', [
            'student_id' => $this->studentActive6->id,
            'academic_year' => '2569',
        ]);
    }

    public function test_undo_does_not_flip_is_current_of_from_year_sai_for_skip_students(): void
    {
        // Seed from-year SAI for skip student Active6 as is_current = false
        $sai = StudentAcademicInfo::where('student_id', $this->studentActive6->id)
            ->where('academic_year', '2568')
            ->first();
        if ($sai) {
            $sai->update(['is_current' => false]);
        } else {
            StudentAcademicInfo::create([
                'student_id' => $this->studentActive6->id,
                'classroom_id' => $this->class6_1_2568->id,
                'academic_year' => '2568',
                'is_current' => false,
            ]);
        }

        $userMapping = [
            [
                'student_id' => $this->studentActive1->id,
                'action' => 'promote',
                'from_classroom_id' => $this->class1_1_2568->id,
                'to_classroom_id' => $this->class2_1_2569->id,
            ],
            [
                'student_id' => $this->studentActive6->id,
                'action' => 'skip',
                'from_classroom_id' => $this->class6_1_2568->id,
                'to_classroom_id' => null,
            ],
        ];

        $plan = $this->service->planRollover($this->academy, $this->year2568, $this->year2569, $userMapping);
        $batch = $this->service->commitRollover($plan, $this->admin);

        // Undo
        $this->service->undoRollover($batch->id, $this->admin);

        // Active6's 2568 SAI should STILL be is_current = false
        $this->assertDatabaseHas('student_academic_info', [
            'student_id' => $this->studentActive6->id,
            'academic_year' => '2568',
            'is_current' => false,
        ]);
    }

    public function test_undo_uses_frozen_year_name_even_if_academic_year_renamed(): void
    {
        $userMapping = [
            [
                'student_id' => $this->studentActive1->id,
                'action' => 'promote',
                'from_classroom_id' => $this->class1_1_2568->id,
                'to_classroom_id' => $this->class2_1_2569->id,
            ],
        ];

        $plan = $this->service->planRollover($this->academy, $this->year2568, $this->year2569, $userMapping);
        $batch = $this->service->commitRollover($plan, $this->admin);

        // Rename the actual academic year names
        $this->year2568->update(['name' => '2568-renamed']);
        $this->year2569->update(['name' => '2569-renamed']);

        // Undo should still delete '2569' and restore '2568' because it used the frozen year names
        $this->service->undoRollover($batch->id, $this->admin);

        // Assert 2569 SAI deleted
        $this->assertDatabaseMissing('student_academic_info', [
            'student_id' => $this->studentActive1->id,
            'academic_year' => '2569',
        ]);
        // Assert 2568 SAI is active again
        $this->assertDatabaseHas('student_academic_info', [
            'student_id' => $this->studentActive1->id,
            'academic_year' => '2568',
            'is_current' => true,
        ]);
    }

    public function test_undo_demotes_other_current_sai_when_restoring_from_year_row(): void
    {
        $userMapping = [
            [
                'student_id' => $this->studentActive1->id,
                'action' => 'promote',
                'from_classroom_id' => $this->class1_1_2568->id,
                'to_classroom_id' => $this->class2_1_2569->id,
            ],
        ];

        $plan = $this->service->planRollover($this->academy, $this->year2568, $this->year2569, $userMapping);
        $batch = $this->service->commitRollover($plan, $this->admin);

        // Seed a third unrelated SAI as is_current = true
        StudentAcademicInfo::create([
            'student_id' => $this->studentActive1->id,
            'classroom_id' => $this->class1_1_2568->id,
            'academic_year' => '2570',
            'is_current' => true,
        ]);

        // Undo
        $this->service->undoRollover($batch->id, $this->admin);

        // 2570 should be demoted to is_current = false, and 2568 restored to is_current = true
        $this->assertDatabaseHas('student_academic_info', [
            'student_id' => $this->studentActive1->id,
            'academic_year' => '2570',
            'is_current' => false,
        ]);
        $this->assertDatabaseHas('student_academic_info', [
            'student_id' => $this->studentActive1->id,
            'academic_year' => '2568',
            'is_current' => true,
        ]);
    }
}
