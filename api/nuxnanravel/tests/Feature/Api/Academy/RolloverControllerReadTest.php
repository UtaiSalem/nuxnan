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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class RolloverControllerReadTest extends TestCase
{
    use RefreshDatabase;

    private Academy $academy;

    private Academy $otherAcademy;

    private User $owner;

    private User $teacher;

    private User $studentUser;

    private User $randomUser;

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

        $this->owner = User::factory()->create(['name' => 'Owner Admin']);
        $this->teacher = User::factory()->create(['name' => 'Homeroom Teacher']);
        $this->studentUser = User::factory()->create(['name' => 'Student User']);
        $this->randomUser = User::factory()->create(['name' => 'Random User']);

        $this->academy = Academy::create([
            'user_id' => $this->owner->id,
            'name' => 'Test Academy',
            'slug' => 'test-academy',
        ]);

        $this->otherAcademy = Academy::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Other Academy',
            'slug' => 'other-academy',
        ]);

        // Setup membership
        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->teacher->id,
            'role' => 'teacher',
        ]);

        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->studentUser->id,
            'role' => 'student',
        ]);

        // Academic years
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
            'name' => '2568-Other',
            'start_date' => '2025-05-16',
            'end_date' => '2026-03-31',
        ]);

        // Classrooms
        $this->classroomA = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year2568->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
            'homeroom_teacher_id' => $this->teacher->id,
        ]);

        $this->classroomC = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year2569->id,
            'grade_level' => 'ม.2',
            'section' => '1',
            'name' => 'ม.2/1',
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->otherClassroom = Classroom::create([
            'academy_id' => $this->otherAcademy->id,
            'academic_year_id' => $this->otherYear->id,
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'Other ม.1/1',
            'capacity' => 40,
            'is_active' => true,
            'status' => 'active',
        ]);

        // Students
        $this->student = Student::create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->studentUser->id,
            'first_name_th' => 'สมหญิง',
            'last_name_th' => 'เรียนดี',
            'student_id' => 'LC001',
            'citizen_id' => '1111111111111',
            'status' => 'active',
        ]);

        $this->otherStudent = Student::create([
            'academy_id' => $this->otherAcademy->id,
            'user_id' => User::factory()->create()->id,
            'first_name_th' => 'สมชาย',
            'last_name_th' => 'เรียนดี',
            'student_id' => 'LC002',
            'citizen_id' => '2222222222222',
            'status' => 'active',
        ]);

        // Active enrollment in 2568
        ClassroomStudent::create([
            'academy_id' => $this->academy->id,
            'classroom_id' => $this->classroomA->id,
            'academic_year_id' => $this->year2568->id,
            'student_id' => $this->student->id,
            'status' => ClassroomStudent::STATUS_ACTIVE,
        ]);
    }

    /**
     * C1: preview as academy admin -> 200 + suggested mapping shape
     */
    public function test_preview_as_academy_admin(): void
    {
        $this->actingAs($this->owner, 'api');

        $response = $this->postJson(route('api.academy.rollover.preview', $this->academy), [
            'from_year_id' => $this->year2568->id,
            'to_year_id' => $this->year2569->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'preview' => [
                    'entries',
                    'missing_targets',
                    'totals',
                    'warnings',
                ],
            ]);
    }

    /**
     * C2: preview as teacher -> 200 (Decision: teacher preview ได้)
     */
    public function test_preview_as_teacher(): void
    {
        $this->actingAs($this->teacher, 'api');

        $response = $this->postJson(route('api.academy.rollover.preview', $this->academy), [
            'from_year_id' => $this->year2568->id,
            'to_year_id' => $this->year2569->id,
        ]);

        $response->assertStatus(200);
    }

    /**
     * C3: preview as student -> 403
     */
    public function test_preview_as_student(): void
    {
        $this->actingAs($this->studentUser, 'api');

        $response = $this->postJson(route('api.academy.rollover.preview', $this->academy), [
            'from_year_id' => $this->year2568->id,
            'to_year_id' => $this->year2569->id,
        ]);

        $response->assertStatus(403);
    }

    /**
     * C4: preview from_year ของ academy อื่น -> 422 (FormRequest scope)
     */
    public function test_preview_from_year_of_other_academy(): void
    {
        $this->actingAs($this->owner, 'api');

        $response = $this->postJson(route('api.academy.rollover.preview', $this->academy), [
            'from_year_id' => $this->otherYear->id,
            'to_year_id' => $this->year2569->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from_year_id']);
    }

    /**
     * C5: plan as admin -> 200 + plan_id (uuid) + cached
     */
    public function test_plan_as_admin(): void
    {
        $this->actingAs($this->owner, 'api');

        $response = $this->postJson(route('api.academy.rollover.plan', $this->academy), [
            'from_year_id' => $this->year2568->id,
            'to_year_id' => $this->year2569->id,
            'mapping' => [
                [
                    'student_id' => $this->student->id,
                    'action' => 'promote',
                    'from_classroom_id' => $this->classroomA->id,
                    'to_classroom_id' => $this->classroomC->id,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'plan_id',
                'expires_in_seconds',
                'summary',
                'warnings',
                'entries_count',
            ]);

        $planId = $response->json('plan_id');
        $cacheKey = "rollover_plan:{$planId}:user:{$this->owner->id}";
        $this->assertTrue(Cache::has($cacheKey));
    }

    /**
     * C6: plan as teacher -> 200
     */
    public function test_plan_as_teacher(): void
    {
        $this->actingAs($this->teacher, 'api');

        $response = $this->postJson(route('api.academy.rollover.plan', $this->academy), [
            'from_year_id' => $this->year2568->id,
            'to_year_id' => $this->year2569->id,
            'mapping' => [
                [
                    'student_id' => $this->student->id,
                    'action' => 'promote',
                    'from_classroom_id' => $this->classroomA->id,
                    'to_classroom_id' => $this->classroomC->id,
                ],
            ],
        ]);

        $response->assertStatus(200);
    }

    /**
     * C7: plan with invalid mapping (student ไม่อยู่ใน academy) -> 422
     */
    public function test_plan_with_invalid_mapping_student_not_in_academy(): void
    {
        $this->actingAs($this->owner, 'api');

        $response = $this->postJson(route('api.academy.rollover.plan', $this->academy), [
            'from_year_id' => $this->year2568->id,
            'to_year_id' => $this->year2569->id,
            'mapping' => [
                [
                    'student_id' => $this->otherStudent->id,
                    'action' => 'promote',
                    'from_classroom_id' => $this->classroomA->id,
                    'to_classroom_id' => $this->classroomC->id,
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mapping.0.student_id']);
    }

    /**
     * C8: plan returns errors for missing target classroom on promote
     */
    public function test_plan_validation_fails_for_missing_target_classroom(): void
    {
        $this->actingAs($this->owner, 'api');

        $response = $this->postJson(route('api.academy.rollover.plan', $this->academy), [
            'from_year_id' => $this->year2568->id,
            'to_year_id' => $this->year2569->id,
            'mapping' => [
                [
                    'student_id' => $this->student->id,
                    'action' => 'promote',
                    'from_classroom_id' => $this->classroomA->id,
                    'to_classroom_id' => null, // empty classroom on promote action -> validation error
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mapping.0.to_classroom_id']);
    }

    /**
     * C9: plan cached key correct format
     */
    public function test_plan_cached_key_correct_format(): void
    {
        $this->actingAs($this->owner, 'api');

        $response = $this->postJson(route('api.academy.rollover.plan', $this->academy), [
            'from_year_id' => $this->year2568->id,
            'to_year_id' => $this->year2569->id,
            'mapping' => [
                [
                    'student_id' => $this->student->id,
                    'action' => 'promote',
                    'from_classroom_id' => $this->classroomA->id,
                    'to_classroom_id' => $this->classroomC->id,
                ],
            ],
        ]);

        $planId = $response->json('plan_id');
        $cacheKey = "rollover_plan:{$planId}:user:{$this->owner->id}";
        $this->assertTrue(Cache::has($cacheKey));

        $cachedData = Cache::get($cacheKey);
        $this->assertEquals($this->academy->id, $cachedData['academy_id']);
        $this->assertEquals($this->year2568->id, $cachedData['from_academic_year_id']);
        $this->assertEquals($this->year2569->id, $cachedData['to_academic_year_id']);
    }

    /**
     * C10: index as staff -> paginated list (only own academy)
     */
    public function test_index_as_staff(): void
    {
        RolloverBatch::create([
            'id' => Str::uuid()->toString(),
            'academy_id' => $this->academy->id,
            'from_academic_year_id' => $this->year2568->id,
            'to_academic_year_id' => $this->year2569->id,
            'status' => 'committed',
            'plan_summary' => ['totals' => ['promote' => 1]],
            'totals' => ['promote' => 1],
            'committed_at' => now(),
            'committed_by_user_id' => $this->owner->id,
        ]);

        $this->actingAs($this->teacher, 'api');

        $response = $this->getJson(route('api.academy.rollover.index', $this->academy));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'academy_id',
                        'from_year',
                        'to_year',
                        'status',
                        'committed_at',
                        'committed_by',
                        'is_undoable',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    /**
     * C11: index as student -> 403
     */
    public function test_index_as_student(): void
    {
        $this->actingAs($this->studentUser, 'api');

        $response = $this->getJson(route('api.academy.rollover.index', $this->academy));

        $response->assertStatus(403);
    }

    /**
     * C12: show batch in own academy -> 200 + full batch shape
     */
    public function test_show_batch_in_own_academy(): void
    {
        $batchId = Str::uuid()->toString();
        $batch = RolloverBatch::create([
            'id' => $batchId,
            'academy_id' => $this->academy->id,
            'from_academic_year_id' => $this->year2568->id,
            'to_academic_year_id' => $this->year2569->id,
            'status' => 'committed',
            'plan_summary' => ['totals' => ['promote' => 1]],
            'totals' => ['promote' => 1],
            'committed_at' => now(),
            'committed_by_user_id' => $this->owner->id,
        ]);

        $this->actingAs($this->owner, 'api');

        $response = $this->getJson(route('api.academy.rollover.show', [$this->academy, $batch]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'batch' => [
                    'id',
                    'academy_id',
                    'from_year',
                    'to_year',
                    'status',
                    'plan_summary',
                    'totals',
                ],
            ]);
    }

    /**
     * C13: show batch from other academy -> 404 (scopeBindings)
     */
    public function test_show_batch_from_other_academy(): void
    {
        $batchId = Str::uuid()->toString();
        $batch = RolloverBatch::create([
            'id' => $batchId,
            'academy_id' => $this->otherAcademy->id,
            'from_academic_year_id' => $this->otherYear->id,
            'to_academic_year_id' => $this->otherYear->id, // simple fallback
            'status' => 'committed',
            'plan_summary' => ['totals' => ['promote' => 1]],
            'totals' => ['promote' => 1],
            'committed_at' => now(),
            'committed_by_user_id' => $this->owner->id,
        ]);

        $this->actingAs($this->owner, 'api');

        // Note: Route model binding with scopeBindings will search for rollover batches owned by $this->academy
        $response = $this->getJson(route('api.academy.rollover.show', [$this->academy, $batch]));

        $response->assertStatus(404);
    }

    /**
     * C14: show batch hides plan_summary for teacher
     */
    public function test_show_batch_hides_plan_summary_for_teacher(): void
    {
        $batchId = Str::uuid()->toString();
        $batch = RolloverBatch::create([
            'id' => $batchId,
            'academy_id' => $this->academy->id,
            'from_academic_year_id' => $this->year2568->id,
            'to_academic_year_id' => $this->year2569->id,
            'status' => 'committed',
            'plan_summary' => ['totals' => ['promote' => 1]],
            'totals' => ['promote' => 1],
            'committed_at' => now(),
            'committed_by_user_id' => $this->owner->id,
        ]);

        $this->actingAs($this->teacher, 'api');

        $response = $this->getJson(route('api.academy.rollover.show', [$this->academy, $batch]));

        $response->assertStatus(200);
        $this->assertNull($response->json('batch.plan_summary'));
    }

    /**
     * C15: show batch shows plan_summary for admin
     */
    public function test_show_batch_shows_plan_summary_for_admin(): void
    {
        $batchId = Str::uuid()->toString();
        $batch = RolloverBatch::create([
            'id' => $batchId,
            'academy_id' => $this->academy->id,
            'from_academic_year_id' => $this->year2568->id,
            'to_academic_year_id' => $this->year2569->id,
            'status' => 'committed',
            'plan_summary' => ['totals' => ['promote' => 1]],
            'totals' => ['promote' => 1],
            'committed_at' => now(),
            'committed_by_user_id' => $this->owner->id,
        ]);

        $this->actingAs($this->owner, 'api');

        $response = $this->getJson(route('api.academy.rollover.show', [$this->academy, $batch]));

        $response->assertStatus(200);
        $this->assertNotNull($response->json('batch.plan_summary'));
    }
}
