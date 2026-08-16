<?php

namespace Tests\Feature\Sports;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\SportsDiscipline;
use App\Models\SportsEdition;
use App\Models\SportsEditionHouse;
use App\Models\SportsHouseStanding;
use App\Models\SportsScoreEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SportsScoringTest extends TestCase
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

    private function makeDiscipline(array $attributes = []): SportsDiscipline
    {
        return SportsDiscipline::create(array_merge([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'name' => 'วิ่ง 100 เมตร',
            'type' => 'team',
            'scoring_table' => ['1' => 9, '2' => 8, '3' => 7],
            'max_score' => null,
            'display_order' => 1,
        ], $attributes));
    }

    private function createScoreEntry(array $attributes = []): SportsScoreEntry
    {
        return SportsScoreEntry::create(array_merge([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'house_group_id' => $this->houses[0],
            'discipline_id' => null,
            'source' => 'manual',
            'placing' => null,
            'points' => 0,
            'awarded_by_user_id' => $this->actor->id,
        ], $attributes));
    }

    public function test_multiple_teams_can_tie_for_the_same_placing(): void
    {
        $discipline = $this->makeDiscipline(['type' => 'team', 'scoring_table' => ['1' => 9, '2' => 8, '3' => 7]]);

        $this->actingAs($this->actor, 'api')
            ->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/score-entries", [
                'discipline_id' => $discipline->id,
                'source' => 'placing',
                'house_group_id' => $this->houses[0],
                'placing' => 1,
            ])
            ->assertSuccessful();

        $this->actingAs($this->actor, 'api')
            ->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/score-entries", [
                'discipline_id' => $discipline->id,
                'source' => 'placing',
                'house_group_id' => $this->houses[1],
                'placing' => 1,
            ])
            ->assertSuccessful();

        $scores = SportsScoreEntry::where('discipline_id', $discipline->id)->get();
        $this->assertCount(2, $scores);
        foreach ($scores as $score) {
            $this->assertEqualsWithDelta(9.0, (float) $score->points, 0.001);
        }
    }

    public function test_standings_rank_is_calculated_like_olympics(): void
    {
        $this->createScoreEntry(['house_group_id' => $this->houses[0], 'points' => 9]);
        $this->createScoreEntry(['house_group_id' => $this->houses[1], 'points' => 9]);
        $this->createScoreEntry(['house_group_id' => $this->houses[2], 'points' => 7]);

        $this->actingAs($this->actor, 'api')
            ->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/standings/rebuild")
            ->assertSuccessful();

        $standings = SportsHouseStanding::where('edition_id', $this->edition->id)->get()->keyBy('house_group_id');

        $this->assertSame(1, (int) $standings[$this->houses[0]]->rank);
        $this->assertSame(1, (int) $standings[$this->houses[1]]->rank);
        $this->assertSame(3, (int) $standings[$this->houses[2]]->rank);
    }

    public function test_placing_beyond_scoring_table_gets_zero_points_without_error(): void
    {
        $discipline = $this->makeDiscipline(['scoring_table' => ['1' => 9, '2' => 7, '3' => 5]]);

        $this->actingAs($this->actor, 'api')
            ->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/score-entries", [
                'discipline_id' => $discipline->id,
                'source' => 'placing',
                'house_group_id' => $this->houses[0],
                'placing' => 9,
            ])
            ->assertSuccessful();

        $score = SportsScoreEntry::where('discipline_id', $discipline->id)->first();
        $this->assertNotNull($score);
        $this->assertEqualsWithDelta(0.0, (float) $score->points, 0.001);
    }

    public function test_houses_without_score_entries_still_appear_in_standings_with_zero_points(): void
    {
        $this->createScoreEntry(['house_group_id' => $this->houses[0], 'points' => 10]);

        $this->actingAs($this->actor, 'api')
            ->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/standings/rebuild")
            ->assertSuccessful();

        $standings = SportsHouseStanding::where('edition_id', $this->edition->id)->get();

        $this->assertCount(4, $standings, 'Standings should have 4 rows because there are 4 houses in the edition.');

        $zeroCount = $standings->filter(fn ($s) => (float) $s->total_points === 0.0)->count();
        $this->assertSame(3, $zeroCount);
    }

    public function test_a_house_removed_from_the_edition_leaves_no_standing_behind(): void
    {
        $this->createScoreEntry(['house_group_id' => $this->houses[3], 'points' => 12]);

        $this->actingAs($this->actor, 'api')
            ->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/standings/rebuild")
            ->assertSuccessful();

        $this->assertDatabaseHas('sports_house_standings', [
            'edition_id' => $this->edition->id,
            'house_group_id' => $this->houses[3],
        ]);

        SportsEditionHouse::where('edition_id', $this->edition->id)->where('house_group_id', $this->houses[3])->delete();

        $this->actingAs($this->actor, 'api')
            ->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/standings/rebuild")
            ->assertSuccessful();

        // The table publishes the current house set. A house that left has to disappear
        // from it, otherwise the score table keeps showing a colour nobody competes for.
        $this->assertDatabaseMissing('sports_house_standings', [
            'edition_id' => $this->edition->id,
            'house_group_id' => $this->houses[3],
        ]);
        $this->assertCount(3, SportsHouseStanding::where('edition_id', $this->edition->id)->get());
    }

    public function test_voided_entries_are_excluded_from_standings_but_kept_in_log(): void
    {
        $entry = $this->createScoreEntry(['house_group_id' => $this->houses[0], 'points' => 9]);

        // Ensure standings reflect the score initially
        $this->actingAs($this->actor, 'api')
            ->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/standings/rebuild")
            ->assertSuccessful();

        $standing = SportsHouseStanding::where('edition_id', $this->edition->id)->where('house_group_id', $this->houses[0])->first();
        $this->assertEqualsWithDelta(9.0, (float) $standing->total_points, 0.001);

        // Void the entry
        $this->actingAs($this->actor, 'api')
            ->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/score-entries/{$entry->id}/void")
            ->assertSuccessful();

        $this->assertSame(1, SportsScoreEntry::where('id', $entry->id)->count(), 'The entry row must still exist.');
        $this->assertNotNull(SportsScoreEntry::where('id', $entry->id)->first()->voided_at);

        $standingAfter = SportsHouseStanding::where('edition_id', $this->edition->id)->where('house_group_id', $this->houses[0])->first();
        $this->assertEqualsWithDelta(0.0, (float) $standingAfter->total_points, 0.001);
    }

    public function test_voiding_an_already_voided_entry_does_nothing(): void
    {
        $entry = $this->createScoreEntry(['house_group_id' => $this->houses[0], 'points' => 9, 'voided_at' => now()->subDay()]);
        $originalVoidedAt = $entry->voided_at->format('Y-m-d H:i:s');

        $this->actingAs($this->actor, 'api')
            ->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/score-entries/{$entry->id}/void")
            ->assertSuccessful();

        $entry->refresh();
        $this->assertSame($originalVoidedAt, $entry->voided_at->format('Y-m-d H:i:s'));
    }

    public function test_negative_points_deduct_from_standings(): void
    {
        $this->createScoreEntry(['house_group_id' => $this->houses[0], 'points' => 10]);
        $this->createScoreEntry(['house_group_id' => $this->houses[0], 'points' => -5]);

        $this->actingAs($this->actor, 'api')
            ->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/standings/rebuild")
            ->assertSuccessful();

        $standing = SportsHouseStanding::where('edition_id', $this->edition->id)->where('house_group_id', $this->houses[0])->first();
        $this->assertEqualsWithDelta(5.0, (float) $standing->total_points, 0.001);
    }

    public function test_medal_counts_only_include_placing_source(): void
    {
        // Medals from placing
        $discipline = $this->makeDiscipline();
        $this->createScoreEntry(['house_group_id' => $this->houses[0], 'discipline_id' => $discipline->id, 'source' => 'placing', 'placing' => 1, 'points' => 9]);
        $this->createScoreEntry(['house_group_id' => $this->houses[0], 'discipline_id' => $discipline->id, 'source' => 'placing', 'placing' => 2, 'points' => 7]);
        $this->createScoreEntry(['house_group_id' => $this->houses[0], 'discipline_id' => $discipline->id, 'source' => 'placing', 'placing' => 3, 'points' => 5]);

        // Not a medal (placing 4)
        $this->createScoreEntry(['house_group_id' => $this->houses[0], 'discipline_id' => $discipline->id, 'source' => 'placing', 'placing' => 4, 'points' => 3]);

        // Large points but judged/manual should not give medals
        $this->createScoreEntry(['house_group_id' => $this->houses[1], 'source' => 'judged', 'points' => 99]);
        $this->createScoreEntry(['house_group_id' => $this->houses[1], 'source' => 'manual', 'points' => 99]);
        $this->createScoreEntry(['house_group_id' => $this->houses[1], 'discipline_id' => $discipline->id, 'source' => 'manual', 'placing' => 1, 'points' => 9]);

        $this->actingAs($this->actor, 'api')
            ->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/standings/rebuild")
            ->assertSuccessful();

        $standings = SportsHouseStanding::where('edition_id', $this->edition->id)->get()->keyBy('house_group_id');

        $house0 = $standings[$this->houses[0]];
        $this->assertSame(1, (int) $house0->gold_count);
        $this->assertSame(1, (int) $house0->silver_count);
        $this->assertSame(1, (int) $house0->bronze_count);

        $house1 = $standings[$this->houses[1]];
        $this->assertSame(0, (int) $house1->gold_count);
        $this->assertSame(0, (int) $house1->silver_count);
        $this->assertSame(0, (int) $house1->bronze_count);
    }

    public function test_house_outside_edition_is_rejected_with_422(): void
    {
        $extraHouse = AcademyGroup::create(['academy_id' => $this->academy->id, 'name' => 'Extra', 'type' => 'house']);

        $this->actingAs($this->actor, 'api')
            ->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/score-entries", [
                'source' => 'manual',
                'house_group_id' => $extraHouse->id,
                'points' => 10,
            ])
            ->assertStatus(422);
    }

    public function test_discipline_from_another_edition_is_rejected_with_422(): void
    {
        $edition2 = $this->makeEdition($this->year);
        $discipline2 = $this->makeDiscipline(['edition_id' => $edition2->id]);

        $this->actingAs($this->actor, 'api')
            ->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/score-entries", [
                'discipline_id' => $discipline2->id,
                'source' => 'placing',
                'house_group_id' => $this->houses[0],
                'placing' => 1,
            ])
            ->assertStatus(422);
    }

    public function test_routes_reject_requests_from_other_academies_with_404(): void
    {
        $otherAcademy = Academy::factory()->create(['user_id' => User::factory()->create()->id]);
        $role = AcademyRole::create([
            'academy_id' => $otherAcademy->id,
            'name' => 'sports-admin-other',
            'display_name_th' => 'Other',
            'permissions' => ['sports.view', 'sports.manage'],
        ]);
        $otherActor = User::factory()->create();
        AcademyMember::create([
            'academy_id' => $otherAcademy->id,
            'user_id' => $otherActor->id,
            'academy_role_id' => $role->id,
            'status' => 2,
        ]);

        $this->actingAs($otherActor, 'api')
            ->getJson("/api/academies/{$otherAcademy->id}/sports-editions/{$this->edition->id}/standings")
            ->assertStatus(404);

        $this->actingAs($otherActor, 'api')
            ->postJson("/api/academies/{$otherAcademy->id}/sports-editions/{$this->edition->id}/score-entries", [
                'source' => 'manual',
                'house_group_id' => $this->houses[0],
                'points' => 10,
            ])
            ->assertStatus(404);
    }

    public function test_routes_are_guarded_by_sports_manage_permission(): void
    {
        $viewerRole = AcademyRole::create([
            'academy_id' => $this->academy->id,
            'name' => 'sports-viewer',
            'display_name_th' => 'Viewer',
            'permissions' => ['sports.view'],
        ]);
        $viewer = User::factory()->create();
        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $viewer->id,
            'academy_role_id' => $viewerRole->id,
            'status' => 2,
        ]);

        $this->actingAs($viewer, 'api')
            ->getJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/standings")
            ->assertStatus(200);

        $this->actingAs($viewer, 'api')
            ->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/score-entries", [
                'source' => 'manual',
                'house_group_id' => $this->houses[0],
                'points' => 10,
            ])
            ->assertForbidden();
    }

    public function test_placing_source_ignores_provided_points_and_uses_table(): void
    {
        $discipline = $this->makeDiscipline(['scoring_table' => ['1' => 9, '2' => 7, '3' => 5]]);

        $this->actingAs($this->actor, 'api')
            ->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/score-entries", [
                'discipline_id' => $discipline->id,
                'source' => 'placing',
                'house_group_id' => $this->houses[0],
                'placing' => 1,
                'points' => 999,
            ])
            ->assertSuccessful();

        $score = SportsScoreEntry::where('discipline_id', $discipline->id)->where('house_group_id', $this->houses[0])->first();
        $this->assertEqualsWithDelta(9.0, (float) $score->points, 0.001);
    }

    public function test_standings_do_not_mix_scores_across_editions(): void
    {
        $this->createScoreEntry(['house_group_id' => $this->houses[0], 'points' => 15]);

        $edition2 = $this->makeEdition($this->year);
        SportsScoreEntry::create([
            'edition_id' => $edition2->id,
            'academy_id' => $this->academy->id,
            'house_group_id' => $this->houses[0],
            'discipline_id' => null,
            'source' => 'manual',
            'points' => 20,
            'awarded_by_user_id' => $this->actor->id,
        ]);

        $this->actingAs($this->actor, 'api')
            ->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/standings/rebuild")
            ->assertSuccessful();

        $this->actingAs($this->actor, 'api')
            ->postJson("/api/academies/{$this->academy->id}/sports-editions/{$edition2->id}/standings/rebuild")
            ->assertSuccessful();

        $standing1 = SportsHouseStanding::where('edition_id', $this->edition->id)->where('house_group_id', $this->houses[0])->first();
        $this->assertEqualsWithDelta(15.0, (float) $standing1->total_points, 0.001);

        $standing2 = SportsHouseStanding::where('edition_id', $edition2->id)->where('house_group_id', $this->houses[0])->first();
        $this->assertEqualsWithDelta(20.0, (float) $standing2->total_points, 0.001);
    }
}
