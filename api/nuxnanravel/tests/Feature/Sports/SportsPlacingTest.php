<?php

namespace Tests\Feature\Sports;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\SportsDiscipline;
use App\Models\SportsDisciplineResult;
use App\Models\SportsEdition;
use App\Models\SportsEditionHouse;
use App\Models\SportsHouseStanding;
use App\Models\SportsMatch;
use App\Models\SportsMatchParticipant;
use App\Models\SportsScoreEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SportsPlacingTest extends TestCase
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

    private function finishedMatch(SportsDiscipline $d, int $roundOrder, int $matchNumber, array $participants, ?int $winner = null): SportsMatch
    {
        $match = SportsMatch::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'discipline_id' => $d->id,
            'round_order' => $roundOrder,
            'match_number' => $matchNumber,
            'status' => 'finished',
            'winner_house_group_id' => $winner,
        ]);

        $slot = 1;
        foreach ($participants as $p) {
            SportsMatchParticipant::create([
                'match_id' => $match->id,
                'house_group_id' => $p['house_group_id'],
                'slot' => $slot++,
                'score' => $p['score'] ?? null,
                'time_ms' => $p['time_ms'] ?? null,
                'status' => $p['status'] ?? 'ok',
            ]);
        }

        return $match;
    }

    public function test_knockout_suggests_first_second_and_third_from_the_bracket(): void
    {
        $d = $this->makeDiscipline(['format' => 'knockout']);
        $houses = $this->useHouses(4);

        $this->finishedMatch($d, 2, 1, [
            ['house_group_id' => $houses[0]],
            ['house_group_id' => $houses[1]],
        ], $houses[0]);

        $this->finishedMatch($d, 2, 2, [
            ['house_group_id' => $houses[2]],
            ['house_group_id' => $houses[3]],
        ], $houses[2]);

        $res = $this->actingAs($this->actor, 'api')->getJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$d->id}/suggested-placings");
        $res->assertStatus(200);

        $placings = $res->json('placings');
        $this->assertCount(3, $placings);

        $this->assertEquals(1, $placings[0]['placing']);
        $this->assertEquals($houses[0], $placings[0]['house_group_id']);

        $this->assertEquals(2, $placings[1]['placing']);
        $this->assertEquals($houses[1], $placings[1]['house_group_id']);

        $this->assertEquals(3, $placings[2]['placing']);
        $this->assertEquals($houses[2], $placings[2]['house_group_id']);
    }

    public function test_knockout_without_a_third_place_match_gives_both_semifinal_losers_third(): void
    {
        $d = $this->makeDiscipline(['format' => 'knockout']);
        $houses = $this->useHouses(4);

        $this->finishedMatch($d, 2, 1, [
            ['house_group_id' => $houses[0]],
            ['house_group_id' => $houses[1]],
        ], $houses[0]);

        $this->finishedMatch($d, 1, 1, [
            ['house_group_id' => $houses[0]],
            ['house_group_id' => $houses[2]],
        ], $houses[0]);

        $this->finishedMatch($d, 1, 2, [
            ['house_group_id' => $houses[1]],
            ['house_group_id' => $houses[3]],
        ], $houses[1]);

        $res = $this->actingAs($this->actor, 'api')->getJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$d->id}/suggested-placings");
        $res->assertStatus(200);

        $placings = collect($res->json('placings'))->sortBy('house_group_id')->values()->all();
        $this->assertCount(4, $placings);

        $this->assertEquals(1, collect($placings)->where('house_group_id', $houses[0])->first()['placing']);
        $this->assertEquals(2, collect($placings)->where('house_group_id', $houses[1])->first()['placing']);

        $h2P = collect($placings)->where('house_group_id', $houses[2])->first()['placing'];
        $h3P = collect($placings)->where('house_group_id', $houses[3])->first()['placing'];

        $this->assertEquals(3, $h2P);
        $this->assertEquals(3, $h3P);
    }

    public function test_knockout_with_an_unfinished_final_suggests_nothing(): void
    {
        $d = $this->makeDiscipline(['format' => 'knockout']);
        $houses = $this->useHouses(2);

        $match = $this->finishedMatch($d, 1, 1, [
            ['house_group_id' => $houses[0]],
            ['house_group_id' => $houses[1]],
        ]);
        $match->update(['status' => 'scheduled', 'winner_house_group_id' => null]);

        $res = $this->actingAs($this->actor, 'api')->getJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$d->id}/suggested-placings");
        $res->assertStatus(200);
        $this->assertEmpty($res->json('placings'));
    }

    public function test_round_robin_ranks_by_points_then_goal_difference(): void
    {
        $d = $this->makeDiscipline(['format' => 'round_robin']);
        $houses = $this->useHouses(4);

        $this->finishedMatch($d, 1, 1, [
            ['house_group_id' => $houses[0], 'score' => 3],
            ['house_group_id' => $houses[1], 'score' => 1],
        ]); // h0 wins (3 pts)

        $this->finishedMatch($d, 1, 2, [
            ['house_group_id' => $houses[2], 'score' => 2],
            ['house_group_id' => $houses[3], 'score' => 0],
        ]); // h2 wins (3 pts)

        $this->finishedMatch($d, 2, 1, [
            ['house_group_id' => $houses[0], 'score' => 0],
            ['house_group_id' => $houses[2], 'score' => 0],
        ]); // draw (1 pt each, h0: 4pts, diff +2. h2: 4pts, diff +2.)

        $this->finishedMatch($d, 2, 2, [
            ['house_group_id' => $houses[0], 'score' => 5],
            ['house_group_id' => $houses[3], 'score' => 0],
        ]); // h0 wins, h0: 7pts, diff +7

        $res = $this->actingAs($this->actor, 'api')->getJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$d->id}/suggested-placings");

        $placings = collect($res->json('placings'));
        $this->assertEquals(1, $placings->where('house_group_id', $houses[0])->first()['placing']);
        $this->assertEquals(2, $placings->where('house_group_id', $houses[2])->first()['placing']);
    }

    public function test_round_robin_with_everything_equal_gives_a_shared_placing(): void
    {
        $d = $this->makeDiscipline(['format' => 'round_robin']);
        $houses = $this->useHouses(3);

        $this->finishedMatch($d, 1, 1, [
            ['house_group_id' => $houses[0], 'score' => 0],
            ['house_group_id' => $houses[1], 'score' => 0],
        ]);
        $this->finishedMatch($d, 1, 2, [
            ['house_group_id' => $houses[1], 'score' => 0],
            ['house_group_id' => $houses[2], 'score' => 0],
        ]);
        $this->finishedMatch($d, 1, 3, [
            ['house_group_id' => $houses[2], 'score' => 0],
            ['house_group_id' => $houses[0], 'score' => 0],
        ]);

        $res = $this->actingAs($this->actor, 'api')->getJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$d->id}/suggested-placings");
        $placings = $res->json('placings');

        $this->assertCount(3, $placings);
        $this->assertEquals(1, $placings[0]['placing']);
        $this->assertEquals(1, $placings[1]['placing']);
        $this->assertEquals(1, $placings[2]['placing']);
    }

    public function test_heats_rank_by_time_and_skip_dq(): void
    {
        $d = $this->makeDiscipline(['format' => 'heats']);
        $houses = $this->useHouses(4);

        $this->finishedMatch($d, 1, 1, [
            ['house_group_id' => $houses[0], 'time_ms' => 12000],
            ['house_group_id' => $houses[1], 'time_ms' => 11000],
            ['house_group_id' => $houses[2], 'time_ms' => 10000, 'status' => 'dq'],
            ['house_group_id' => $houses[3], 'time_ms' => null],
        ]);

        $res = $this->actingAs($this->actor, 'api')->getJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$d->id}/suggested-placings");

        $placings = $res->json('placings');
        $this->assertCount(2, $placings);

        $this->assertEquals(1, $placings[0]['placing']);
        $this->assertEquals($houses[1], $placings[0]['house_group_id']);

        $this->assertEquals(2, $placings[1]['placing']);
        $this->assertEquals($houses[0], $placings[1]['house_group_id']);
    }

    public function test_suggesting_placings_writes_nothing(): void
    {
        $d = $this->makeDiscipline(['format' => 'knockout']);
        $houses = $this->useHouses(2);

        $this->finishedMatch($d, 1, 1, [
            ['house_group_id' => $houses[0]],
            ['house_group_id' => $houses[1]],
        ], $houses[0]);

        $c1 = SportsScoreEntry::count();
        $c2 = SportsDisciplineResult::count();
        $c3 = SportsMatch::count();

        $this->actingAs($this->actor, 'api')->getJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$d->id}/suggested-placings")->assertStatus(200);

        $this->assertEquals($c1, SportsScoreEntry::count());
        $this->assertEquals($c2, SportsDisciplineResult::count());
        $this->assertEquals($c3, SportsMatch::count());
    }

    public function test_confirming_placings_awards_points_from_the_scoring_table(): void
    {
        $d = $this->makeDiscipline(['format' => 'heats', 'scoring_table' => ['1' => 9, '2' => 8, '3' => 7]]);
        $houses = $this->useHouses(3);

        $res = $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$d->id}/confirm-placings", [
            'placings' => [
                ['house_group_id' => $houses[0], 'placing' => 1],
                ['house_group_id' => $houses[1], 'placing' => 2],
                ['house_group_id' => $houses[2], 'placing' => 3],
            ],
        ]);

        $res->assertStatus(200);

        $entries = SportsScoreEntry::where('discipline_id', $d->id)->get();
        $this->assertCount(3, $entries);

        $this->assertEquals(9, $entries->where('house_group_id', $houses[0])->first()->points);
        $this->assertEquals(8, $entries->where('house_group_id', $houses[1])->first()->points);
        $this->assertEquals(7, $entries->where('house_group_id', $houses[2])->first()->points);

        $results = SportsDisciplineResult::where('discipline_id', $d->id)->get();
        $this->assertCount(3, $results);
        foreach ($results as $result) {
            $this->assertNotNull($result->score_entry_id);
        }
    }

    public function test_confirmed_entries_carry_the_result_reference(): void
    {
        $d = $this->makeDiscipline(['format' => 'heats']);
        $houses = $this->useHouses(1);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$d->id}/confirm-placings", [
            'placings' => [
                ['house_group_id' => $houses[0], 'placing' => 1],
            ],
        ]);

        $result = SportsDisciplineResult::first();
        $entry = SportsScoreEntry::first();

        $this->assertEquals('sports_discipline_results', $entry->ref_type);
        $this->assertEquals($result->id, $entry->ref_id);
    }

    public function test_confirming_twice_voids_the_old_entries_instead_of_doubling(): void
    {
        $d = $this->makeDiscipline(['format' => 'heats', 'scoring_table' => ['1' => 9, '2' => 8]]);
        $houses = $this->useHouses(2);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$d->id}/confirm-placings", [
            'placings' => [
                ['house_group_id' => $houses[0], 'placing' => 1],
                ['house_group_id' => $houses[1], 'placing' => 2],
            ],
        ])->assertStatus(200);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$d->id}/confirm-placings", [
            'placings' => [
                ['house_group_id' => $houses[0], 'placing' => 2],
                ['house_group_id' => $houses[1], 'placing' => 1],
            ],
        ])->assertStatus(200);

        $this->assertEquals(2, SportsScoreEntry::whereNull('voided_at')->count());
        $this->assertEquals(2, SportsScoreEntry::whereNotNull('voided_at')->count());
        $this->assertEquals(2, SportsDisciplineResult::count());

        $standings = SportsHouseStanding::where('edition_id', $this->edition->id)->get();
        $this->assertEquals(8, $standings->where('house_group_id', $houses[0])->first()->total_points);
        $this->assertEquals(9, $standings->where('house_group_id', $houses[1])->first()->total_points);
    }

    public function test_a_house_dropped_from_the_new_confirmation_loses_its_result_row(): void
    {
        $d = $this->makeDiscipline(['format' => 'heats', 'scoring_table' => ['1' => 9, '2' => 8]]);
        $houses = $this->useHouses(2);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$d->id}/confirm-placings", [
            'placings' => [
                ['house_group_id' => $houses[0], 'placing' => 1],
                ['house_group_id' => $houses[1], 'placing' => 2],
            ],
        ])->assertStatus(200);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$d->id}/confirm-placings", [
            'placings' => [
                ['house_group_id' => $houses[0], 'placing' => 1],
            ],
        ])->assertStatus(200);

        $this->assertEquals(1, SportsDisciplineResult::count());
        $this->assertEquals($houses[0], SportsDisciplineResult::first()->house_group_id);

        $standings = SportsHouseStanding::where('edition_id', $this->edition->id)->get();
        $this->assertEquals(0, $standings->where('house_group_id', $houses[1])->first()->total_points);
    }

    public function test_a_tie_gets_the_same_points(): void
    {
        $d = $this->makeDiscipline(['format' => 'heats', 'scoring_table' => ['1' => 9, '2' => 8]]);
        $houses = $this->useHouses(2);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$d->id}/confirm-placings", [
            'placings' => [
                ['house_group_id' => $houses[0], 'placing' => 1],
                ['house_group_id' => $houses[1], 'placing' => 1],
            ],
        ])->assertStatus(200);

        $entries = SportsScoreEntry::where('discipline_id', $d->id)->get();
        $this->assertEquals(9, $entries->where('house_group_id', $houses[0])->first()->points);
        $this->assertEquals(9, $entries->where('house_group_id', $houses[1])->first()->points);
    }

    public function test_a_placing_outside_the_scoring_table_scores_zero(): void
    {
        $d = $this->makeDiscipline(['format' => 'heats', 'scoring_table' => ['1' => 9]]);
        $houses = $this->useHouses(1);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$d->id}/confirm-placings", [
            'placings' => [
                ['house_group_id' => $houses[0], 'placing' => 99],
            ],
        ])->assertStatus(200);

        $this->assertEquals(0, SportsScoreEntry::first()->points);
    }

    public function test_a_house_outside_the_edition_is_rejected(): void
    {
        $d = $this->makeDiscipline(['format' => 'heats']);
        $houses = $this->useHouses(1);
        $extraHouse = AcademyGroup::create(['academy_id' => $this->academy->id, 'name' => 'Extra', 'type' => 'house']);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$d->id}/confirm-placings", [
            'placings' => [
                ['house_group_id' => $extraHouse->id, 'placing' => 1],
            ],
        ])->assertStatus(422);

        $this->assertEquals(0, SportsScoreEntry::count());
        $this->assertEquals(0, SportsDisciplineResult::count());
    }

    public function test_a_discipline_from_another_edition_returns_404(): void
    {
        $d = $this->makeDiscipline(['format' => 'heats']);
        $houses = $this->useHouses(1);

        $edition2 = $this->makeEdition($this->year, 'draft');
        $d2 = $this->makeDiscipline(['edition_id' => $edition2->id]);

        $this->actingAs($this->actor, 'api')->getJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$d2->id}/suggested-placings")->assertStatus(404);

        $this->actingAs($this->actor, 'api')->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/disciplines/{$d2->id}/confirm-placings", [
            'placings' => [
                ['house_group_id' => $houses[0], 'placing' => 1],
            ],
        ])->assertStatus(404);
    }
}
