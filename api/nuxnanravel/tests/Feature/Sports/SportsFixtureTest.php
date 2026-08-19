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
use App\Models\SportsMatch;
use App\Models\SportsScoreEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SportsFixtureTest extends TestCase
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
            'format' => 'none',
            'scoring_table' => ['1' => 9, '2' => 8, '3' => 7],
            'max_score' => null,
            'display_order' => 1,
        ], $attributes));
    }

    /** @return array<int,int> ids ของคณะสีที่ใช้ในเทสต์นี้ */
    private function useHouses(int $count): array
    {
        $ids = $this->houses;
        $order = count($ids);
        while (count($ids) < $count) {
            $group = AcademyGroup::create([
                'academy_id' => $this->academy->id,
                'name' => 'คณะ '.(count($ids) + 1),
                'type' => 'house',
            ]);
            SportsEditionHouse::create([
                'edition_id' => $this->edition->id,
                'house_group_id' => $group->id,
                'display_order' => $order++,
            ]);
            $ids[] = $group->id;
        }

        return array_slice($ids, 0, $count);
    }

    public function test_round_robin_with_four_houses_creates_six_matches(): void
    {
        $discipline = $this->makeDiscipline();
        $houses = $this->useHouses(4);

        $response = $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$discipline->id}/generate-fixtures", [
            'format' => 'round_robin',
            'house_group_ids' => $houses,
        ]);
        $response->assertStatus(201);

        $matches = SportsMatch::where('discipline_id', $discipline->id)->with('participants')->get();
        $this->assertCount(6, $matches);

        $pairs = [];
        foreach ($matches as $match) {
            $this->assertCount(2, $match->participants);
            $pIds = [$match->participants[0]->house_group_id, $match->participants[1]->house_group_id];
            sort($pIds);
            $pairs[] = implode('-', $pIds);
        }
        $this->assertCount(6, array_unique($pairs));
    }

    public function test_round_robin_with_five_houses_gives_every_house_four_games(): void
    {
        $discipline = $this->makeDiscipline();
        $houses = $this->useHouses(5);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$discipline->id}/generate-fixtures", [
            'format' => 'round_robin',
            'house_group_ids' => $houses,
        ])->assertStatus(201);

        $matches = SportsMatch::where('discipline_id', $discipline->id)->with('participants')->get();
        $this->assertCount(10, $matches);

        $gamesCount = array_fill_keys($houses, 0);
        foreach ($matches as $match) {
            foreach ($match->participants as $p) {
                $gamesCount[$p->house_group_id]++;
            }
        }
        foreach ($gamesCount as $count) {
            $this->assertEquals(4, $count);
        }
    }

    public function test_knockout_with_four_houses_links_semifinals_to_the_final(): void
    {
        $discipline = $this->makeDiscipline();
        $houses = $this->useHouses(4);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$discipline->id}/generate-fixtures", [
            'format' => 'knockout',
            'house_group_ids' => $houses,
        ])->assertStatus(201);

        $matches = SportsMatch::where('discipline_id', $discipline->id)->get();
        $this->assertCount(3, $matches);

        $final = $matches->sortByDesc('round_order')->first();
        $this->assertEquals(2, $final->round_order);
        $this->assertEquals(1, $final->match_number);

        $semis = $matches->where('round_order', 1);
        $this->assertCount(2, $semis);

        $slots = [];
        foreach ($semis as $semi) {
            $this->assertEquals($final->id, $semi->next_match_id);
            $slots[] = $semi->next_match_slot;
        }
        sort($slots);
        $this->assertEquals([1, 2], $slots);
    }

    public function test_knockout_with_five_houses_places_byes_correctly(): void
    {
        $discipline = $this->makeDiscipline();
        $houses = $this->useHouses(5);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$discipline->id}/generate-fixtures", [
            'format' => 'knockout',
            'house_group_ids' => $houses,
        ])->assertStatus(201);

        $matches = SportsMatch::where('discipline_id', $discipline->id)->get();
        $this->assertCount(4, $matches);

        $roundOne = $matches->where('round_order', 1);
        $this->assertCount(1, $roundOne);
        $this->assertCount(2, $roundOne->first()->participants);

        $roundTwo = $matches->where('round_order', 2);
        $this->assertCount(2, $roundTwo);

        $pCount = 0;
        foreach ($roundTwo as $m) {
            $pCount += $m->participants()->count();
        }
        $this->assertEquals(3, $pCount);
    }

    public function test_knockout_can_add_a_third_place_match(): void
    {
        $discipline = $this->makeDiscipline();
        $houses = $this->useHouses(4);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$discipline->id}/generate-fixtures", [
            'format' => 'knockout',
            'house_group_ids' => $houses,
            'options' => ['third_place' => true],
        ])->assertStatus(201);

        $matches = SportsMatch::where('discipline_id', $discipline->id)->get();
        $this->assertCount(4, $matches);

        $third = $matches->where('round_label', 'ชิงอันดับที่ 3')->first();
        $this->assertNotNull($third);
        $this->assertEquals(2, $third->match_number);

        $final = $matches->where('round_order', $third->round_order)->where('match_number', 1)->first();
        $this->assertNotNull($final);
    }

    public function test_heats_split_twelve_houses_into_two_heats_and_a_final(): void
    {
        $discipline = $this->makeDiscipline();
        $houses = $this->useHouses(12);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$discipline->id}/generate-fixtures", [
            'format' => 'heats',
            'house_group_ids' => $houses,
            'options' => ['lanes_per_heat' => 8],
        ])->assertStatus(201);

        $matches = SportsMatch::where('discipline_id', $discipline->id)->get();
        $this->assertCount(3, $matches);

        $heats = $matches->where('round_order', 1);
        $this->assertCount(2, $heats);
        foreach ($heats as $heat) {
            $this->assertCount(6, $heat->participants);
        }

        $final = $matches->where('round_order', 2)->first();
        $this->assertNotNull($final);
        $this->assertCount(0, $final->participants);
    }

    public function test_a_single_heat_does_not_create_an_empty_final(): void
    {
        $discipline = $this->makeDiscipline();
        $houses = $this->useHouses(4);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$discipline->id}/generate-fixtures", [
            'format' => 'heats',
            'house_group_ids' => $houses,
            'options' => ['lanes_per_heat' => 8],
        ])->assertStatus(201);

        $matches = SportsMatch::where('discipline_id', $discipline->id)->get();
        $this->assertCount(1, $matches);
    }

    public function test_regenerating_is_rejected_when_a_match_is_finished(): void
    {
        $discipline = $this->makeDiscipline();
        $houses = $this->useHouses(4);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$discipline->id}/generate-fixtures", [
            'format' => 'round_robin',
            'house_group_ids' => $houses,
        ])->assertStatus(201);

        $match = SportsMatch::where('discipline_id', $discipline->id)->first();
        $match->update(['status' => 'finished']);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$discipline->id}/generate-fixtures", [
            'format' => 'round_robin',
            'house_group_ids' => $houses,
        ])->assertStatus(422);

        $matches = SportsMatch::where('discipline_id', $discipline->id)->get();
        $this->assertCount(6, $matches);
        $this->assertNotNull($matches->where('status', 'finished')->first());
    }

    public function test_regenerating_replaces_scheduled_matches(): void
    {
        $discipline = $this->makeDiscipline();
        $houses = $this->useHouses(4);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$discipline->id}/generate-fixtures", [
            'format' => 'round_robin',
            'house_group_ids' => $houses,
        ])->assertStatus(201);

        $firstIds = SportsMatch::where('discipline_id', $discipline->id)->pluck('id')->toArray();

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$discipline->id}/generate-fixtures", [
            'format' => 'round_robin',
            'house_group_ids' => $houses,
        ])->assertStatus(201);

        $matches = SportsMatch::where('discipline_id', $discipline->id)->get();
        $this->assertCount(6, $matches);

        $secondIds = $matches->pluck('id')->toArray();
        $this->assertEmpty(array_intersect($firstIds, $secondIds));
    }

    public function test_a_house_outside_the_edition_is_rejected(): void
    {
        $discipline = $this->makeDiscipline();
        $extraHouse = AcademyGroup::create(['academy_id' => $this->academy->id, 'name' => 'Extra', 'type' => 'house']);

        $houses = $this->useHouses(3);
        $houses[] = $extraHouse->id;

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$discipline->id}/generate-fixtures", [
            'format' => 'round_robin',
            'house_group_ids' => $houses,
        ])->assertStatus(422);

        $this->assertEquals(0, SportsMatch::count());
    }

    public function test_generating_fixtures_never_writes_a_score_entry(): void
    {
        $discipline = $this->makeDiscipline();
        $houses = $this->useHouses(4);

        foreach (['round_robin', 'knockout', 'heats'] as $format) {
            SportsMatch::where('discipline_id', $discipline->id)->update(['next_match_id' => null, 'next_match_slot' => null]);
            SportsMatch::where('discipline_id', $discipline->id)->delete();

            $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$discipline->id}/generate-fixtures", [
                'format' => $format,
                'house_group_ids' => $houses,
            ])->assertStatus(201);

            $this->assertEquals(0, SportsScoreEntry::count());
        }
    }

    public function test_format_none_is_rejected(): void
    {
        $discipline = $this->makeDiscipline();
        $houses = $this->useHouses(4);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$discipline->id}/generate-fixtures", [
            'house_group_ids' => $houses,
        ])->assertStatus(422);
    }

    public function test_a_rejected_request_does_not_change_the_discipline_format(): void
    {
        $discipline = $this->makeDiscipline();
        $houses = $this->useHouses(4);
        $extraHouse = AcademyGroup::create(['academy_id' => $this->academy->id, 'name' => 'Extra', 'type' => 'house']);
        $houses[] = $extraHouse->id;

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$discipline->id}/generate-fixtures", [
            'format' => 'knockout',
            'house_group_ids' => $houses,
        ])->assertStatus(422);

        $discipline->refresh();
        $this->assertSame('none', $discipline->format);
    }

    public function test_a_discipline_from_another_edition_returns_404(): void
    {
        $discipline = $this->makeDiscipline();
        $houses = $this->useHouses(4);

        $edition2 = $this->makeEdition($this->year, 'draft');
        $discipline2 = $this->makeDiscipline(['edition_id' => $edition2->id]);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$discipline2->id}/generate-fixtures", [
            'format' => 'round_robin',
            'house_group_ids' => $houses,
        ])->assertStatus(404);
    }
}
