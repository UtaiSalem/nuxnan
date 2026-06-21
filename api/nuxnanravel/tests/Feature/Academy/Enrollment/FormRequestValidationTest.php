<?php

namespace Tests\Feature\Academy\Enrollment;

use App\Http\Requests\Academy\Enrollment\DropStudentRequest;
use App\Http\Requests\Academy\Enrollment\GraduateStudentRequest;
use App\Http\Requests\Academy\Enrollment\PromoteStudentRequest;
use App\Http\Requests\Academy\Enrollment\RepeatStudentRequest;
use App\Http\Requests\Academy\Enrollment\TransferStudentRequest;
use App\Http\Requests\Academy\Rollover\CommitRolloverRequest;
use App\Http\Requests\Academy\Rollover\PlanRolloverRequest;
use App\Http\Requests\Academy\Rollover\PreviewRolloverRequest;
use App\Http\Requests\Academy\Rollover\UndoRolloverRequest;
use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\Classroom;
use App\Models\RolloverBatch;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FormRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    private Academy $academy;

    private Academy $otherAcademy;

    private User $owner;

    private User $teacher;

    private User $studentUser;

    private Student $student;

    private AcademicYear $fromYear;

    private AcademicYear $toYear;

    private AcademicYear $otherYear;

    private Classroom $fromClassroom;

    private Classroom $toClassroom;

    private Classroom $sameYearTarget;

    private Classroom $otherAcademyClassroom;

    private RolloverBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();

        Route::bind('academy', fn (string $value) => Academy::query()->findOrFail($value));
        Route::bind('student', fn (string $value) => Student::query()->findOrFail($value));
        Route::bind('batch', fn (string $value) => RolloverBatch::query()->findOrFail($value));

        $this->registerStubRoutes();

        $this->owner = User::factory()->create();
        $this->teacher = User::factory()->create();
        $this->studentUser = User::factory()->create();

        $this->academy = Academy::create([
            'user_id' => $this->owner->id,
            'name' => 'Enrollment Request Academy',
            'slug' => 'enrollment-request-academy',
        ]);

        $this->otherAcademy = Academy::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Other Enrollment Academy',
            'slug' => 'other-enrollment-academy',
        ]);

        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->teacher->id,
            'role' => 'teacher',
        ]);

        $this->student = Student::create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->studentUser->id,
            'first_name_th' => 'สมชาย',
            'last_name_th' => 'ทดสอบ',
            'student_id' => 'REQ001',
            'citizen_id' => '1111111111111',
            'status' => 'active',
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

        $this->otherYear = AcademicYear::create([
            'academy_id' => $this->otherAcademy->id,
            'name' => '3568',
            'start_date' => '2025-05-16',
            'end_date' => '2026-03-31',
        ]);

        $this->fromClassroom = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->fromYear->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
            'homeroom_teacher_id' => $this->owner->id,
        ]);

        $this->toClassroom = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->toYear->id,
            'grade_level' => 'ม.2',
            'section' => '1',
            'name' => 'ม.2/1',
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->sameYearTarget = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->fromYear->id,
            'grade_level' => 'ม.1',
            'section' => '2',
            'name' => 'ม.1/2',
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->otherAcademyClassroom = Classroom::create([
            'academy_id' => $this->otherAcademy->id,
            'academic_year_id' => $this->otherYear->id,
            'grade_level' => 'ม.2',
            'section' => '1',
            'name' => 'ม.2/1 Other',
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->batch = RolloverBatch::create([
            'id' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            'academy_id' => $this->academy->id,
            'from_academic_year_id' => $this->fromYear->id,
            'to_academic_year_id' => $this->toYear->id,
            'status' => 'committed',
            'committed_at' => now(),
            'plan_summary' => [],
            'totals' => [],
        ]);
    }

    public function test_graduate_request_rejects_future_effective_date(): void
    {
        $response = $this->actingAs($this->owner, 'api')->postJson(
            "/api/_test/enrollment/academies/{$this->academy->id}/students/{$this->student->id}/graduate",
            ['effective_at' => now()->addDay()->toDateString()]
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['effective_at']);
    }

    public function test_graduate_request_forbids_unrelated_teacher(): void
    {
        $response = $this->actingAs($this->teacher, 'api')->postJson(
            "/api/_test/enrollment/academies/{$this->academy->id}/students/{$this->student->id}/graduate",
            ['reason' => 'x']
        );

        $response->assertStatus(403);
    }

    public function test_drop_request_requires_reason(): void
    {
        $response = $this->actingAs($this->owner, 'api')->postJson(
            "/api/_test/enrollment/academies/{$this->academy->id}/students/{$this->student->id}/drop",
            []
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['reason']);
    }

    public function test_repeat_request_rejects_classroom_from_other_academy(): void
    {
        $response = $this->actingAs($this->owner, 'api')->postJson(
            "/api/_test/enrollment/academies/{$this->academy->id}/students/{$this->student->id}/repeat",
            ['new_classroom_id' => $this->otherAcademyClassroom->id]
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['new_classroom_id']);
    }

    public function test_promote_request_rejects_same_year_transfer(): void
    {
        $response = $this->actingAs($this->owner, 'api')->postJson(
            "/api/_test/enrollment/academies/{$this->academy->id}/students/{$this->student->id}/promote",
            [
                'from_classroom_id' => $this->fromClassroom->id,
                'to_classroom_id' => $this->sameYearTarget->id,
            ]
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['to_classroom_id']);
    }

    public function test_transfer_request_rejects_cross_year_transfer(): void
    {
        $response = $this->actingAs($this->owner, 'api')->postJson(
            "/api/_test/enrollment/academies/{$this->academy->id}/students/{$this->student->id}/transfer",
            [
                'from_classroom_id' => $this->fromClassroom->id,
                'to_classroom_id' => $this->toClassroom->id,
            ]
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['to_classroom_id']);
    }

    public function test_preview_rollover_request_rejects_year_from_other_academy(): void
    {
        $response = $this->actingAs($this->owner, 'api')->postJson(
            "/api/_test/enrollment/academies/{$this->academy->id}/rollover/preview",
            [
                'from_year_id' => $this->otherYear->id,
                'to_year_id' => $this->toYear->id,
            ]
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['from_year_id']);
    }

    public function test_plan_rollover_request_requires_target_for_promote_action(): void
    {
        $response = $this->actingAs($this->owner, 'api')->postJson(
            "/api/_test/enrollment/academies/{$this->academy->id}/rollover/plan",
            [
                'from_year_id' => $this->fromYear->id,
                'to_year_id' => $this->toYear->id,
                'mapping' => [
                    [
                        'student_id' => $this->student->id,
                        'action' => 'promote',
                        'from_classroom_id' => $this->fromClassroom->id,
                    ],
                ],
            ]
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['mapping.0.to_classroom_id']);
    }

    public function test_plan_rollover_request_rejects_target_for_graduate_action(): void
    {
        $response = $this->actingAs($this->owner, 'api')->postJson(
            "/api/_test/enrollment/academies/{$this->academy->id}/rollover/plan",
            [
                'from_year_id' => $this->fromYear->id,
                'to_year_id' => $this->toYear->id,
                'mapping' => [
                    [
                        'student_id' => $this->student->id,
                        'action' => 'graduate',
                        'from_classroom_id' => $this->fromClassroom->id,
                        'to_classroom_id' => $this->toClassroom->id,
                    ],
                ],
            ]
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['mapping.0.to_classroom_id']);
    }

    public function test_commit_rollover_request_trims_confirm_text(): void
    {
        $response = $this->actingAs($this->owner, 'api')->postJson(
            "/api/_test/enrollment/academies/{$this->academy->id}/rollover/commit",
            [
                'plan_id' => '11111111-2222-3333-4444-555555555555',
                'confirm_text' => '  CONFIRM  ',
            ]
        );

        $response->assertOk()->assertJsonPath('validated.confirm_text', 'CONFIRM');
    }

    public function test_undo_rollover_request_forbids_teacher(): void
    {
        $response = $this->actingAs($this->teacher, 'api')->postJson(
            "/api/_test/enrollment/academies/{$this->academy->id}/rollover/batches/{$this->batch->id}/undo",
            ['reason' => 'undo']
        );

        $response->assertStatus(403);
    }

    private function registerStubRoutes(): void
    {
        Route::middleware(['api', 'auth:api'])->prefix('api/_test/enrollment')->group(function () {
            Route::post('/academies/{academy}/students/{student}/graduate', function (GraduateStudentRequest $request) {
                return response()->json(['validated' => $request->validated()]);
            });

            Route::post('/academies/{academy}/students/{student}/drop', function (DropStudentRequest $request) {
                return response()->json(['validated' => $request->validated()]);
            });

            Route::post('/academies/{academy}/students/{student}/repeat', function (RepeatStudentRequest $request) {
                return response()->json(['validated' => $request->validated()]);
            });

            Route::post('/academies/{academy}/students/{student}/promote', function (PromoteStudentRequest $request) {
                return response()->json(['validated' => $request->validated()]);
            });

            Route::post('/academies/{academy}/students/{student}/transfer', function (TransferStudentRequest $request) {
                return response()->json(['validated' => $request->validated()]);
            });

            Route::post('/academies/{academy}/rollover/preview', function (PreviewRolloverRequest $request) {
                return response()->json(['validated' => $request->validated()]);
            });

            Route::post('/academies/{academy}/rollover/plan', function (PlanRolloverRequest $request) {
                return response()->json(['validated' => $request->validated()]);
            });

            Route::post('/academies/{academy}/rollover/commit', function (CommitRolloverRequest $request) {
                return response()->json(['validated' => $request->validated()]);
            });

            Route::post('/academies/{academy}/rollover/batches/{batch}/undo', function (UndoRolloverRequest $request) {
                return response()->json(['validated' => $request->validated()]);
            });
        });
    }
}
