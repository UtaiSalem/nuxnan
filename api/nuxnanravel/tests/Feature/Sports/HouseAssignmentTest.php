<?php

namespace Tests\Feature\Sports;

use App\Http\Controllers\Api\Learn\Academy\SportsEditionController;
use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Classroom;
use App\Models\HouseAssignmentBatch;
use App\Models\SportsEdition;
use App\Models\SportsEditionHouse;
use App\Models\Student;
use App\Models\User;
use App\Services\Sports\HouseAssignmentService;
use App\Services\Sports\HouseMembershipProjector;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HouseAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private Academy $academy;

    private User $actor;

    private AcademicYear $year;

    /** @var array<int, int> */
    private array $houses = [];

    protected SportsEdition $edition;

    protected function setUp(): void
    {
        parent::setUp();

        $owner = User::factory()->create();
        $this->academy = Academy::factory()->create(['user_id' => $owner->id]);
        $role = AcademyRole::create([
            'academy_id' => $this->academy->id,
            'name' => 'sports-admin',
            'display_name_th' => 'Sports',
            'permissions' => ['sports.view', 'sports.manage'],
        ]);
        $this->actor = User::factory()->create();
        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->actor->id,
            'academy_role_id' => $role->id,
            'status' => 2,
        ]);
        $this->year = $this->makeYear(true);
        $this->edition = $this->makeEdition($this->year, 'active');
    }

    public function test_only_students_enrolled_this_year_are_divided(): void
    {
        $classroom = $this->makeClassroom($this->year);
        $enrolled = $this->makeStudent();
        $this->enrol($enrolled, $classroom, $this->year);

        // Active in the registry but with no classroom row for this year — the 460-row
        // gap on the real database (2,662 active students vs 2,202 actual enrolments).
        $notEnrolled = $this->makeStudent();

        // Enrolled, but the enrolment itself was withdrawn.
        $left = $this->makeStudent();
        $this->enrol($left, $classroom, $this->year, 'left');

        $batch = $this->preview();

        $assigned = $batch->rows()->pluck('student_id')->all();
        $this->assertSame([$enrolled->id], $assigned);
        $this->assertNotContains($notEnrolled->id, $assigned);
        $this->assertNotContains($left->id, $assigned);
    }

    public function test_stratified_split_spreads_every_classroom_across_every_house(): void
    {
        foreach (range(1, 4) as $n) {
            $classroom = $this->makeClassroom($this->year, "ม.{$n}", (string) $n);
            foreach (range(1, 12) as $i) {
                $this->enrol($this->makeStudent(), $classroom, $this->year);
            }
        }

        $batch = $this->preview(['balance_gender' => false]);

        $this->assertSame(48, $batch->rows()->count());

        // 48 students / 4 houses — an even split is achievable and must actually happen.
        foreach ($batch->summary['per_house'] as $count) {
            $this->assertSame(12, $count);
        }

        // Within a single classroom no house may be more than one student ahead of another,
        // otherwise a คณะสี ends up carrying whole classrooms instead of a mix.
        $perClassroom = DB::table('house_assignment_rows')
            ->join('classroom_students', 'classroom_students.student_id', '=', 'house_assignment_rows.student_id')
            ->where('house_assignment_rows.batch_id', $batch->id)
            ->select('classroom_students.classroom_id', 'house_assignment_rows.house_group_id', DB::raw('COUNT(*) as total'))
            ->groupBy('classroom_students.classroom_id', 'house_assignment_rows.house_group_id')
            ->get()
            ->groupBy('classroom_id');

        foreach ($perClassroom as $rows) {
            $this->assertSame(4, $rows->count(), 'every house should receive part of every classroom');
            $this->assertLessThanOrEqual(1, $rows->max('total') - $rows->min('total'));
        }
    }

    public function test_students_with_no_recorded_gender_are_their_own_bucket_and_nobody_is_dropped(): void
    {
        $classroom = $this->makeClassroom($this->year);
        foreach ([1, 1, 1, 0, 0, 0, null, null, null] as $gender) {
            $this->enrol($this->makeStudent($gender), $classroom, $this->year);
        }

        $batch = $this->preview(['balance_gender' => true]);

        // 227 students on the real database have a null gender. They must be divided,
        // not folded into the male or female bucket and not silently skipped.
        $this->assertSame(9, $batch->rows()->count());
        $this->assertSame(9, $batch->summary['total']);
        $this->assertSame(9, array_sum($batch->summary['per_house']));

        // No student may appear twice in one batch. Loose comparison treats null as 0,
        // which silently puts the unknown-gender students in two buckets at once.
        $studentIds = $batch->rows()->pluck('student_id')->all();
        $this->assertSame(count($studentIds), count(array_unique($studentIds)));
    }

    public function test_the_same_seed_reproduces_the_same_division_whatever_order_the_houses_arrive_in(): void
    {
        $classroom = $this->makeClassroom($this->year);
        foreach (range(1, 20) as $i) {
            $this->enrol($this->makeStudent(), $classroom, $this->year);
        }

        $first = $this->preview(['seed' => 4242]);
        $second = $this->preview(['seed' => 4242, 'house_group_ids' => array_reverse($this->houses)]);

        $this->assertSame($this->mapping($first), $this->mapping($second));
    }

    public function test_an_unseeded_preview_stores_the_seed_it_used(): void
    {
        $classroom = $this->makeClassroom($this->year);
        $this->enrol($this->makeStudent(), $classroom, $this->year);

        $batch = $this->preview();

        // Without the stored seed there is no way to show a doubting student that the
        // division was drawn rather than arranged.
        $this->assertIsInt($batch->options['seed']);
        $this->assertNotEmpty($batch->options['seed']);
    }

    public function test_a_student_cannot_hold_two_houses_in_one_year(): void
    {
        $student = $this->makeStudent();
        $row = [
            'academy_id' => $this->academy->id,
            'edition_id' => $this->edition->id,
            'student_id' => $student->id,
            'source' => 'manual',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        DB::table('house_memberships')->insert($row + ['house_group_id' => $this->houses[0]]);

        $this->expectException(QueryException::class);
        DB::table('house_memberships')->insert($row + ['house_group_id' => $this->houses[1]]);
    }

    public function test_commit_projects_into_the_group_table_and_undo_takes_it_back_out(): void
    {
        $classroom = $this->makeClassroom($this->year);
        foreach (range(1, 6) as $i) {
            $this->enrol($this->makeStudent(), $classroom, $this->year);
        }

        $batch = $this->preview();
        $this->service()->commit($batch, $this->actor);

        $this->assertSame(6, DB::table('house_memberships')->where('edition_id', $this->edition->id)->count());
        $this->assertSame(6, $this->projectedCount());

        $this->service()->undo($batch->fresh(), $this->actor);

        $this->assertSame(0, DB::table('house_memberships')->where('edition_id', $this->edition->id)->count());
        $this->assertSame(0, $this->projectedCount());
        $this->assertSame('undone', $batch->fresh()->status);
    }

    public function test_a_batch_that_was_never_committed_cannot_be_undone(): void
    {
        $classroom = $this->makeClassroom($this->year);
        $this->enrol($this->makeStudent(), $classroom, $this->year);

        $this->expectException(DomainException::class);
        $this->service()->undo($this->preview(), $this->actor);
    }

    public function test_undoing_a_re_division_returns_students_to_their_previous_house_rather_than_stranding_them(): void
    {
        $classroom = $this->makeClassroom($this->year);
        $student = $this->makeStudent();
        $this->enrol($student, $classroom, $this->year);

        $first = $this->preview(['seed' => 1]);
        $this->service()->commit($first, $this->actor);
        $original = DB::table('house_memberships')->where('student_id', $student->id)->value('house_group_id');

        // Re-divide everyone, forcing this student somewhere else.
        $other = collect($this->houses)->reject(fn ($id) => $id === (int) $original)->values()->all();
        $second = $this->preview(['scope' => 'all', 'house_group_ids' => $other, 'seed' => 2]);
        $this->service()->commit($second, $this->actor);
        $this->assertNotSame((int) $original, (int) DB::table('house_memberships')->where('student_id', $student->id)->value('house_group_id'));

        $this->service()->undo($second->fresh(), $this->actor);

        // The dangerous outcome is not "wrong house" — it is "no house at all", under a
        // button labelled ย้อนกลับ.
        $restored = DB::table('house_memberships')->where('student_id', $student->id)->first();
        $this->assertNotNull($restored, 'undo must not strand a student who already had a house');
        $this->assertSame((int) $original, (int) $restored->house_group_id);
    }

    public function test_unassigned_only_leaves_existing_houses_alone_while_all_reports_the_moves(): void
    {
        $classroom = $this->makeClassroom($this->year);
        $settled = $this->makeStudent();
        $fresh = $this->makeStudent();
        $this->enrol($settled, $classroom, $this->year);
        $this->enrol($fresh, $classroom, $this->year);

        DB::table('house_memberships')->insert([
            'academy_id' => $this->academy->id,
            'edition_id' => $this->edition->id,
            'house_group_id' => $this->houses[0],
            'student_id' => $settled->id,
            'source' => 'manual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $default = $this->preview();
        $this->assertSame([$fresh->id], $default->rows()->pluck('student_id')->all());
        $this->assertSame(1, $default->summary['skipped_count']);

        $everyone = $this->preview(['scope' => 'all']);
        $this->assertSame(2, $everyone->rows()->count());
        $this->assertSame(0, $everyone->summary['skipped_count']);
        $this->assertGreaterThanOrEqual(0, $everyone->summary['moved_count']);
    }

    public function test_a_past_year_is_recorded_but_never_projected_onto_the_live_group(): void
    {
        $past = $this->makeYear(false);
        $pastEdition = $this->makeEdition($past);
        $classroom = $this->makeClassroom($past);
        foreach (range(1, 4) as $i) {
            $this->enrol($this->makeStudent(), $classroom, $past);
        }

        $batch = $this->preview([], $pastEdition);
        $this->service()->commit($batch, $this->actor);

        $this->assertSame(4, DB::table('house_memberships')->where('edition_id', $pastEdition->id)->count());
        // academy_group_members shows who is in a คณะสี *now*; back-filling an old year
        // must not rewrite it.
        $this->assertSame(0, $this->projectedCount());
    }

    public function test_a_student_with_no_account_is_still_a_house_member_even_though_nothing_is_projected(): void
    {
        $classroom = $this->makeClassroom($this->year);
        $this->enrol($this->makeStudent(1, false), $classroom, $this->year);
        $this->enrol($this->makeStudent(1, true), $classroom, $this->year);

        $this->service()->commit($this->preview(), $this->actor);

        // Headcount must be read from house_memberships; the projection can only ever
        // carry the students who happen to have a user account.
        $this->assertSame(2, DB::table('house_memberships')->where('edition_id', $this->edition->id)->count());
        $this->assertSame(1, $this->projectedCount());
    }

    public function test_reading_the_split_does_not_let_you_run_or_commit_one(): void
    {
        $viewer = $this->memberWith(['sports.view']);
        $outsider = $this->memberWith([]);

        $this->actingAs($viewer, 'api')
            ->postJson("/api/academies/{$this->academy->id}/house-assignments/preview-random", [
                'edition_id' => $this->edition->id,
                'house_group_ids' => $this->houses,
            ])->assertForbidden();

        $batch = $this->preview();
        $this->actingAs($viewer, 'api')
            ->postJson("/api/academies/{$this->academy->id}/house-assignments/{$batch->id}/commit")
            ->assertForbidden();

        $this->actingAs($outsider, 'api')
            ->getJson("/api/academies/{$this->academy->id}/house-assignments")
            ->assertForbidden();
    }

    public function test_a_batch_cannot_be_reached_through_another_academys_url(): void
    {
        $classroom = $this->makeClassroom($this->year);
        $this->enrol($this->makeStudent(), $classroom, $this->year);
        $batch = $this->preview();

        $other = Academy::factory()->create(['user_id' => User::factory()->create()->id]);
        $role = AcademyRole::create([
            'academy_id' => $other->id,
            'name' => 'sports-admin',
            'display_name_th' => 'Sports',
            'permissions' => ['sports.view', 'sports.manage'],
        ]);
        $intruder = User::factory()->create();
        AcademyMember::create([
            'academy_id' => $other->id,
            'user_id' => $intruder->id,
            'academy_role_id' => $role->id,
            'status' => 2,
        ]);

        $this->actingAs($intruder, 'api')
            ->getJson("/api/academies/{$other->id}/house-assignments/{$batch->id}")
            ->assertNotFound();
    }

    public function test_a_year_belonging_to_another_academy_is_refused(): void
    {
        $other = Academy::factory()->create(['user_id' => User::factory()->create()->id]);
        $foreignYear = AcademicYear::create([
            'academy_id' => $other->id,
            'name' => '2570',
            'start_date' => '2027-05-01',
            'end_date' => '2028-03-31',
            'is_current' => false,
        ]);
        $foreignEdition = $this->makeEdition($foreignYear);

        $this->expectException(DomainException::class);
        $this->service()->previewRandom($this->academy, $foreignEdition, [
            'house_group_ids' => $this->houses,
        ], $this->actor);
    }

    public function test_house_assignment_routes_are_registered_and_guarded(): void
    {
        $routes = collect(app('router')->getRoutes())
            ->filter(fn ($route) => str_contains($route->getName() ?? '', 'api.academy.house-assignments.'));

        $this->assertEqualsCanonicalizing([
            'api.academy.house-assignments.index',
            'api.academy.house-assignments.current',
            'api.academy.house-assignments.preview-random',
            'api.academy.house-assignments.preview-import',
            'api.academy.house-assignments.template',
            'api.academy.house-assignments.show',
            'api.academy.house-assignments.rows',
            'api.academy.house-assignments.commit',
            'api.academy.house-assignments.undo',
            'api.academy.house-assignments.discard',
        ], $routes->map(fn ($route) => $route->getName())->values()->all());

        foreach ($routes as $route) {
            $this->assertContains('auth:api', $route->middleware());
            $this->assertTrue(
                collect($route->middleware())->contains(fn ($m) => str_contains($m, 'academy.permission:sports.')),
                "{$route->getName()} is missing its sports permission guard"
            );
        }
    }

    // ---------------------------------------------------------------- helpers

    private function service(): HouseAssignmentService
    {
        return app(HouseAssignmentService::class);
    }

    private function preview(array $options = [], ?SportsEdition $edition = null): HouseAssignmentBatch
    {
        $edition ??= $this->edition;

        return $this->service()->previewRandom(
            $this->academy,
            $edition,
            array_merge(['house_group_ids' => $this->houses, 'strategy' => 'stratified'], $options),
            $this->actor,
        );
    }

    /** @return array<int, int> student_id => house_group_id */
    private function mapping(HouseAssignmentBatch $batch): array
    {
        return $batch->rows()->orderBy('student_id')->pluck('house_group_id', 'student_id')->map(fn ($id) => (int) $id)->all();
    }

    private function projectedCount(): int
    {
        return DB::table('academy_group_members')
            ->whereIn('academy_group_id', AcademyGroup::where('academy_id', $this->academy->id)->where('type', 'house')->pluck('id'))
            ->count();
    }

    private function memberWith(array $permissions): User
    {
        $user = User::factory()->create();
        $roleId = null;
        if ($permissions !== []) {
            $roleId = AcademyRole::create([
                'academy_id' => $this->academy->id,
                'name' => uniqid('role'),
                'display_name_th' => 'Scoped',
                'permissions' => $permissions,
            ])->id;
        }
        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $roleId,
            'status' => 2,
        ]);

        return $user;
    }

    private function makeYear(bool $current): AcademicYear
    {
        $year = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => $current ? '2569' : '2568',
            'start_date' => $current ? '2026-05-01' : '2025-05-01',
            'end_date' => $current ? '2027-03-31' : '2026-03-31',
            'is_current' => $current,
        ]);

        if ($this->houses === []) {
            $this->houses = collect(['แดง', 'น้ำเงิน', 'เขียว', 'เหลือง'])
                ->map(fn ($name) => AcademyGroup::create([
                    'academy_id' => $this->academy->id,
                    'name' => $name,
                    'type' => 'house',
                ])->id)
                ->all();
        }

        return $year;
    }

    private function makeEdition(AcademicYear $year, string $status = 'draft'): SportsEdition
    {
        $sequence = SportsEdition::where('academy_id', $year->academy_id)
            ->where('academic_year_id', $year->id)
            ->max('sequence') ?? 0;

        $edition = SportsEdition::create([
            'academy_id' => $year->academy_id,
            'academic_year_id' => $year->id,
            'name' => 'Test',
            'sequence' => $sequence + 1,
            'status' => $status,
            'created_by_user_id' => $this->actor->id,
        ]);

        foreach ($this->houses as $i => $id) {
            SportsEditionHouse::create(['edition_id' => $edition->id, 'house_group_id' => $id, 'display_order' => $i]);
        }

        return $edition;
    }

    private function makeClassroom(AcademicYear $year, string $level = 'ม.1', string $section = '1'): Classroom
    {
        return Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $year->id,
            'name' => "{$level}/{$section}",
            'grade_level' => $level,
            'section' => $section,
        ]);
    }

    private function makeStudent(?int $gender = 1, bool $withAccount = true): Student
    {
        static $sequence = 0;
        $sequence++;

        return Student::create([
            'academy_id' => $this->academy->id,
            'user_id' => $withAccount ? User::factory()->create()->id : null,
            'student_id' => (string) (90000 + $sequence),
            'first_name_th' => "นักเรียน{$sequence}",
            'last_name_th' => 'ทดสอบ',
            'gender' => $gender,
            'status' => 'active',
        ]);
    }

    private function enrol(Student $student, Classroom $classroom, AcademicYear $year, string $status = 'active'): void
    {
        DB::table('classroom_students')->insert([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_two_editions_in_one_year_keep_separate_memberships(): void
    {
        $classroom = $this->makeClassroom($this->year);
        $this->enrol($this->makeStudent(), $classroom, $this->year);

        $edition1 = $this->edition;
        $this->service()->commit($this->preview([], $edition1), $this->actor);
        $studentId = DB::table('house_memberships')->where('edition_id', $edition1->id)->value('student_id');
        $houseId1 = DB::table('house_memberships')->where('edition_id', $edition1->id)->where('student_id', $studentId)->value('house_group_id');

        $edition2 = $this->makeEdition($this->year);
        $this->service()->commit($this->preview([], $edition2), $this->actor);

        $count = DB::table('house_memberships')->where('student_id', $studentId)->count();
        $this->assertSame(2, $count);
        $houseId1After = DB::table('house_memberships')->where('edition_id', $edition1->id)->where('student_id', $studentId)->value('house_group_id');
        $this->assertSame($houseId1, $houseId1After);
    }

    public function test_a_second_active_edition_is_rejected_by_the_database(): void
    {
        $this->expectException(QueryException::class);
        SportsEdition::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Active 2',
            'sequence' => 2,
            'status' => 'active',
            'created_by_user_id' => $this->actor->id,
        ]);
    }

    public function test_two_drafts_and_two_closed_editions_are_fine(): void
    {
        SportsEdition::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Draft 2',
            'sequence' => 2,
            'status' => 'draft',
            'created_by_user_id' => $this->actor->id,
        ]);
        SportsEdition::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Closed 1',
            'sequence' => 4,
            'status' => 'closed',
            'created_by_user_id' => $this->actor->id,
        ]);
        SportsEdition::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Closed 2',
            'sequence' => 5,
            'status' => 'closed',
            'created_by_user_id' => $this->actor->id,
        ]);
        $this->assertTrue(true);
    }

    public function test_activate_demotes_the_previous_edition_and_reprojects(): void
    {
        $classroom = $this->makeClassroom($this->year);
        foreach (range(1, 4) as $i) {
            $this->enrol($this->makeStudent(), $classroom, $this->year);
        }

        $edition1 = $this->edition;
        $this->service()->commit($this->preview([], $edition1), $this->actor);
        $this->assertGreaterThan(0, DB::table('academy_group_members')->count());

        $edition2 = $this->makeEdition($this->year);
        $this->service()->commit($this->preview([], $edition2), $this->actor);

        app(SportsEditionController::class)
            ->activate($this->academy, $edition2, app(HouseMembershipProjector::class));

        $this->assertSame('closed', $edition1->fresh()->status);
        $this->assertSame('active', $edition2->fresh()->status);

        $members2 = DB::table('academy_group_members')->get();
        $edition2Count = DB::table('house_memberships')->where('edition_id', $edition2->id)->count();
        $this->assertSame($edition2Count, $members2->count());
    }

    public function test_activating_an_edition_with_no_houses_empties_the_projection(): void
    {
        $classroom = $this->makeClassroom($this->year);
        foreach (range(1, 3) as $i) {
            $this->enrol($this->makeStudent(), $classroom, $this->year);
        }
        $this->service()->commit($this->preview([], $this->edition), $this->actor);
        $this->assertGreaterThan(0, DB::table('academy_group_members')->count());

        // An edition whose house set has not been chosen yet is still the live one. The
        // previous edition's members must not stay published under it.
        $empty = SportsEdition::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year->id,
            'name' => 'No houses yet',
            'sequence' => 90,
            'status' => 'draft',
            'created_by_user_id' => $this->actor->id,
        ]);

        app(SportsEditionController::class)->activate($this->academy, $empty, app(HouseMembershipProjector::class));

        $this->assertSame(0, DB::table('academy_group_members')->count());
    }

    public function test_preview_rejects_houses_that_are_not_in_the_edition(): void
    {
        $extraHouse = AcademyGroup::create(['academy_id' => $this->academy->id, 'type' => 'house', 'name' => 'Extra']);
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('All houses must belong to this edition.');
        $this->preview(['house_group_ids' => [$this->houses[0], $extraHouse->id]]);
    }

    public function test_a_student_assigned_in_a_previous_edition_is_not_already_assigned(): void
    {
        $edition1 = $this->edition;
        $batch1 = $this->preview(['scope' => 'unassigned_only'], $edition1);
        $this->service()->commit($batch1, $this->actor);

        $edition2 = $this->makeEdition($this->year);
        $batch2 = $this->preview(['scope' => 'unassigned_only'], $edition2);

        $this->assertSame($batch1->rows()->count(), $batch2->rows()->count());
    }
}
