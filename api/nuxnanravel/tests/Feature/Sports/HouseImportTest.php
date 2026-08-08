<?php

namespace Tests\Feature\Sports;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\SportsEdition;
use App\Models\SportsEditionHouse;
use App\Models\Student;
use App\Models\User;
use App\Services\Sports\HouseAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class HouseImportTest extends TestCase
{
    use RefreshDatabase;

    private Academy $academy;

    private AcademicYear $year;

    private SportsEdition $edition;

    private User $actor;

    private array $houses;

    protected function setUp(): void
    {
        parent::setUp();
        $owner = User::factory()->create();
        $this->academy = Academy::factory()->create(['user_id' => $owner->id]);
        $role = AcademyRole::create(['academy_id' => $this->academy->id, 'name' => 'sports', 'display_name_th' => 'Sports', 'permissions' => ['sports.view', 'sports.manage']]);
        $this->actor = User::factory()->create();
        AcademyMember::create(['academy_id' => $this->academy->id, 'user_id' => $this->actor->id, 'academy_role_id' => $role->id, 'status' => 2]);
        $this->year = AcademicYear::create(['academy_id' => $this->academy->id, 'name' => '2569', 'start_date' => '2026-05-01', 'end_date' => '2027-03-31', 'is_current' => true]);
        $this->houses = collect(['Red', 'Blue'])->map(fn ($name) => AcademyGroup::create(['academy_id' => $this->academy->id, 'name' => $name, 'type' => 'house'])->id)->all();
        $this->edition = SportsEdition::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Test',
            'sequence' => 1,
            'status' => 'draft',
            'created_by_user_id' => $this->actor->id,
        ]);
        foreach ($this->houses as $i => $id) {
            SportsEditionHouse::create(['edition_id' => $this->edition->id, 'house_group_id' => $id, 'display_order' => $i]);
        }
    }

    public function test_clean_csv_assigns_and_commits(): void
    {
        $this->student('1001', 'One', 'Person');
        $this->student('1002', 'Two', 'Person');
        $batch = $this->import("code,house\n1001,Red\n1002,Blue\n");
        $this->assertSame(2, $batch->summary['by_status']['ok']);
        app(HouseAssignmentService::class)->commit($batch, $this->actor);
        $this->assertDatabaseCount('house_memberships', 2);
    }

    public function test_unknown_house_and_unmatched_are_not_committed_and_raw_is_saved(): void
    {
        $this->student('2001', 'One', 'Person');
        $batch = $this->import("code,house\n2001,Missing\n9999,Red\n");
        $this->assertSame(['unknown_house' => 1, 'unmatched' => 1], $batch->rows()->pluck('status')->countBy()->all());
        $this->assertNotEmpty($batch->rows()->first()->raw);
        app(HouseAssignmentService::class)->commit($batch, $this->actor);
        $this->assertDatabaseCount('house_memberships', 0);
    }

    public function test_ambiguous_name_and_tone_mark_matching(): void
    {
        $this->student('3001', 'ก่า', 'ดี');
        $this->student('3002', 'ก่า', 'ดี');
        $this->assertSame('ambiguous', $this->import("first,last,house\nก่า,ดี,Red\n", ['student_identifier' => 'first', 'first_name_th' => 'first', 'last_name_th' => 'last'])->rows()->first()->status);
        $this->student('3003', 'ก้า', 'เดี');
        $this->assertSame('ok', $this->import("first,last,house\nกา,เดี,Red\n", ['student_identifier' => 'first', 'first_name_th' => 'first', 'last_name_th' => 'last'])->rows()->first()->status);
    }

    public function test_citizen_requires_thirteen_digits(): void
    {
        $student = $this->student('4001', 'Citizen', 'Person', '1234567890123');
        $this->assertSame($student->id, $this->import("citizen,house\n1234567890123,Red\n", ['student_identifier' => 'citizen'])->rows()->first()->student_id);
        $this->assertSame('unmatched', $this->import("citizen,house\n1.90E+12,Red\n", ['student_identifier' => 'citizen'])->rows()->first()->status);
    }

    public function test_conflict_skip_overwrite_and_undo(): void
    {
        $student = $this->student('5001', 'Conflict', 'Person');
        DB::table('house_memberships')->insert(['academy_id' => $this->academy->id, 'edition_id' => $this->edition->id, 'student_id' => $student->id, 'house_group_id' => $this->houses[0], 'source' => 'manual', 'created_at' => now(), 'updated_at' => now()]);

        // Default: leave the existing house alone.
        $this->assertSame('already_assigned', $this->import("code,house\n5001,Blue\n")->rows()->first()->status);
        app(HouseAssignmentService::class)->commit($this->import("code,house\n5001,Blue\n"), $this->actor);
        $this->assertSame($this->houses[0], (int) DB::table('house_memberships')->where('student_id', $student->id)->value('house_group_id'));

        // Explicit overwrite: the move happens and is recorded well enough to reverse.
        $batch = $this->import("code,house\n5001,Blue\n", [], ['on_conflict' => 'overwrite']);
        $this->assertSame('ok', $batch->rows()->first()->status);
        $this->assertSame($this->houses[0], (int) $batch->rows()->first()->previous_house_group_id);

        app(HouseAssignmentService::class)->commit($batch, $this->actor);
        $this->assertSame($this->houses[1], (int) DB::table('house_memberships')->where('student_id', $student->id)->value('house_group_id'));

        app(HouseAssignmentService::class)->undo($batch->fresh(), $this->actor);
        $restored = DB::table('house_memberships')->where('student_id', $student->id)->first();
        $this->assertNotNull($restored, 'undo must not strand a student who already had a house');
        $this->assertSame($this->houses[0], (int) $restored->house_group_id);
    }

    public function test_a_student_listed_twice_is_flagged_rather_than_counted_twice(): void
    {
        $this->student('7001', 'Twice', 'Listed');
        $this->student('7002', 'Once', 'Listed');

        $batch = $this->import("code,house\n7001,Red\n7002,Blue\n7001,Blue\n");

        // commit() upserts, so a duplicate would leave the preview claiming three
        // assignments while only two rows are ever written.
        $this->assertSame(2, $batch->summary['by_status']['ok']);
        $this->assertSame(1, $batch->summary['by_status']['ambiguous']);
        $this->assertSame(2, array_sum($batch->summary['per_house']));

        $duplicate = $batch->rows()->where('status', 'ambiguous')->first();
        $this->assertNull($duplicate->house_group_id);
        $this->assertStringContainsString('row 1', $duplicate->message);

        app(HouseAssignmentService::class)->commit($batch, $this->actor);
        $this->assertDatabaseCount('house_memberships', 2);
    }

    public function test_the_status_summary_matches_the_rows_actually_stored(): void
    {
        $this->student('8001', 'Good', 'Row');
        $batch = $this->import("code,house\n8001,Red\n8002,Red\n8001,Nowhere\n\n");

        $actual = $batch->rows()->pluck('status')->countBy()->all();
        foreach ($batch->summary['by_status'] as $status => $count) {
            $this->assertSame($actual[$status] ?? 0, $count, "summary miscounts '{$status}'");
        }
        $this->assertSame($batch->rows()->count(), $batch->summary['total']);
    }

    public function test_running_an_import_needs_more_than_read_access(): void
    {
        $viewer = User::factory()->create();
        $role = AcademyRole::create(['academy_id' => $this->academy->id, 'name' => uniqid('view'), 'display_name_th' => 'Viewer', 'permissions' => ['sports.view']]);
        AcademyMember::create(['academy_id' => $this->academy->id, 'user_id' => $viewer->id, 'academy_role_id' => $role->id, 'status' => 2]);

        $this->actingAs($viewer, 'api')
            ->postJson("/api/academies/{$this->academy->id}/house-assignments/preview-import", [
                'edition_id' => $this->edition->id,
                'column_mapping' => ['student_identifier' => 'code', 'house_name' => 'house'],
                'file' => UploadedFile::fake()->createWithContent('house.csv', "code,house\n1,Red\n"),
            ])->assertForbidden();
    }

    public function test_xlsx_input_works(): void
    {
        $this->student('6001', 'Xlsx', 'Person');
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray([['code', 'house'], ['6001', 'Red']]);
        $path = tempnam(sys_get_temp_dir(), 'house');
        (new Xlsx($book))->save($path);
        $batch = app(HouseAssignmentService::class)->previewImport($this->academy, $this->edition, new UploadedFile($path, 'house.xlsx', null, null, true), ['column_mapping' => ['student_identifier' => 'code', 'house_name' => 'house']], $this->actor);
        $this->assertSame('ok', $batch->rows()->first()->status);
    }

    public function test_import_reports_unknown_house_for_a_house_outside_the_edition(): void
    {
        $extraHouse = AcademyGroup::create(['academy_id' => $this->academy->id, 'type' => 'house', 'name' => 'Silver']);
        $this->student('6001', 'Test', 'Person');

        $batch = $this->import("code,house\n6001,Silver");

        $this->assertSame('unknown_house', $batch->rows()->first()->status);
    }

    private function import(string $csv, array $mapping = ['student_identifier' => 'code', 'house_name' => 'house'], array $options = [])
    {
        return app(HouseAssignmentService::class)->previewImport($this->academy, $this->edition, UploadedFile::fake()->createWithContent('house.csv', $csv), array_merge(['column_mapping' => array_merge(['student_identifier' => 'code', 'house_name' => 'house'], $mapping)], $options), $this->actor);
    }

    private function student(string $code, string $first, string $last, ?string $citizen = null): Student
    {
        return Student::create(['academy_id' => $this->academy->id, 'user_id' => User::factory()->create()->id, 'student_id' => $code, 'citizen_id' => $citizen, 'first_name_th' => $first, 'last_name_th' => $last, 'status' => 'active']);
    }
}
