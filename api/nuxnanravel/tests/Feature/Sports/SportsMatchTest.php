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
use App\Models\SportsMatchParticipant;
use App\Models\SportsScoreEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SportsMatchTest extends TestCase
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

    public function test_a_match_can_hold_more_than_two_houses(): void
    {
        $discipline = $this->makeDiscipline();

        $response = $this->actingAs($this->actor, 'api')
            ->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/matches", [
                'discipline_id' => $discipline->id,
                'participants' => [
                    ['house_group_id' => $this->houses[0]],
                    ['house_group_id' => $this->houses[1]],
                    ['house_group_id' => $this->houses[2]],
                    ['house_group_id' => $this->houses[3]],
                ],
            ])
            ->assertSuccessful();

        $this->assertCount(4, $response->json('participants'));
    }

    public function test_time_in_milliseconds_round_trips_exactly(): void
    {
        $discipline = $this->makeDiscipline();

        $match = SportsMatch::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'discipline_id' => $discipline->id,
        ]);

        $this->actingAs($this->actor, 'api')
            ->putJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/matches/{$match->id}/result", [
                'participants' => [
                    ['house_group_id' => $this->houses[0], 'time_ms' => 12345],
                ],
            ])
            ->assertSuccessful();

        $participant = SportsMatchParticipant::where('match_id', $match->id)->where('house_group_id', $this->houses[0])->first();
        $this->assertSame(12345, $participant->time_ms);
    }

    public function test_recording_a_result_never_writes_a_score_entry(): void
    {
        $discipline = $this->makeDiscipline();

        $match = SportsMatch::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'discipline_id' => $discipline->id,
        ]);

        $this->assertSame(0, SportsScoreEntry::count());

        $this->actingAs($this->actor, 'api')
            ->putJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/matches/{$match->id}/result", [
                'participants' => [
                    ['house_group_id' => $this->houses[0], 'placing' => 1],
                    ['house_group_id' => $this->houses[1], 'placing' => 2],
                ],
            ])
            ->assertSuccessful();

        $this->assertSame(0, SportsScoreEntry::count());
    }

    public function test_recording_a_result_sets_finished_and_winner(): void
    {
        $discipline = $this->makeDiscipline();

        $match = SportsMatch::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'discipline_id' => $discipline->id,
            'status' => 'scheduled',
        ]);

        $this->actingAs($this->actor, 'api')
            ->putJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/matches/{$match->id}/result", [
                'participants' => [
                    ['house_group_id' => $this->houses[0], 'placing' => 1],
                    ['house_group_id' => $this->houses[1], 'placing' => 2],
                ],
            ])
            ->assertSuccessful();

        $match->refresh();
        $this->assertSame('finished', $match->status);
        $this->assertSame($this->houses[0], $match->winner_house_group_id);
    }

    public function test_a_tie_for_first_leaves_the_winner_empty(): void
    {
        $discipline = $this->makeDiscipline();

        $match = SportsMatch::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'discipline_id' => $discipline->id,
        ]);

        $this->actingAs($this->actor, 'api')
            ->putJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/matches/{$match->id}/result", [
                'participants' => [
                    ['house_group_id' => $this->houses[0], 'placing' => 1],
                    ['house_group_id' => $this->houses[1], 'placing' => 1],
                ],
            ])
            ->assertSuccessful();

        $match->refresh();
        $this->assertNull($match->winner_house_group_id);
    }

    public function test_recording_a_result_twice_updates_instead_of_duplicating(): void
    {
        $discipline = $this->makeDiscipline();

        $match = SportsMatch::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'discipline_id' => $discipline->id,
        ]);

        $this->actingAs($this->actor, 'api')
            ->putJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/matches/{$match->id}/result", [
                'participants' => [
                    ['house_group_id' => $this->houses[0], 'placing' => 1],
                    ['house_group_id' => $this->houses[1], 'placing' => 2],
                ],
            ])
            ->assertSuccessful();

        $this->assertSame(2, SportsMatchParticipant::where('match_id', $match->id)->count());

        $this->actingAs($this->actor, 'api')
            ->putJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/matches/{$match->id}/result", [
                'participants' => [
                    ['house_group_id' => $this->houses[0], 'placing' => 2],
                    ['house_group_id' => $this->houses[1], 'placing' => 1],
                ],
            ])
            ->assertSuccessful();

        $this->assertSame(2, SportsMatchParticipant::where('match_id', $match->id)->count());
        $p = SportsMatchParticipant::where('match_id', $match->id)->where('house_group_id', $this->houses[1])->first();
        $this->assertSame(1, $p->placing);
    }

    public function test_the_winner_is_advanced_into_the_next_match(): void
    {
        $discipline = $this->makeDiscipline();

        $nextMatch = SportsMatch::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'discipline_id' => $discipline->id,
        ]);

        $match = SportsMatch::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'discipline_id' => $discipline->id,
            'next_match_id' => $nextMatch->id,
            'next_match_slot' => 3,
        ]);

        $this->actingAs($this->actor, 'api')
            ->putJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/matches/{$match->id}/result", [
                'participants' => [
                    ['house_group_id' => $this->houses[0], 'placing' => 1],
                    ['house_group_id' => $this->houses[1], 'placing' => 2],
                ],
            ])
            ->assertSuccessful();

        $nextParticipant = SportsMatchParticipant::where('match_id', $nextMatch->id)->first();
        $this->assertNotNull($nextParticipant);
        $this->assertSame($this->houses[0], $nextParticipant->house_group_id);
        $this->assertSame(3, $nextParticipant->slot);
    }

    public function test_changing_the_winner_replaces_the_advanced_house(): void
    {
        $discipline = $this->makeDiscipline();

        $nextMatch = SportsMatch::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'discipline_id' => $discipline->id,
        ]);

        $match = SportsMatch::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'discipline_id' => $discipline->id,
            'next_match_id' => $nextMatch->id,
            'next_match_slot' => 3,
        ]);

        $this->actingAs($this->actor, 'api')
            ->putJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/matches/{$match->id}/result", [
                'participants' => [
                    ['house_group_id' => $this->houses[0], 'placing' => 1],
                    ['house_group_id' => $this->houses[1], 'placing' => 2],
                ],
            ])
            ->assertSuccessful();

        $this->actingAs($this->actor, 'api')
            ->putJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/matches/{$match->id}/result", [
                'participants' => [
                    ['house_group_id' => $this->houses[0], 'placing' => 2],
                    ['house_group_id' => $this->houses[1], 'placing' => 1],
                ],
            ])
            ->assertSuccessful();

        $nextParticipants = SportsMatchParticipant::where('match_id', $nextMatch->id)->get();
        $this->assertCount(1, $nextParticipants);
        $this->assertSame($this->houses[1], $nextParticipants->first()->house_group_id);
    }

    public function test_dq_participants_are_accepted_without_a_time(): void
    {
        $discipline = $this->makeDiscipline();

        $match = SportsMatch::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'discipline_id' => $discipline->id,
        ]);

        $this->actingAs($this->actor, 'api')
            ->putJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/matches/{$match->id}/result", [
                'participants' => [
                    ['house_group_id' => $this->houses[0], 'status' => 'dq'],
                ],
            ])
            ->assertSuccessful();

        $p = SportsMatchParticipant::where('match_id', $match->id)->first();
        $this->assertSame('dq', $p->status);
        $this->assertNull($p->time_ms);
    }

    public function test_a_house_outside_the_edition_is_rejected(): void
    {
        $discipline = $this->makeDiscipline();
        $extraHouse = AcademyGroup::create(['academy_id' => $this->academy->id, 'name' => 'Extra', 'type' => 'house']);

        $this->actingAs($this->actor, 'api')
            ->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/matches", [
                'discipline_id' => $discipline->id,
                'participants' => [
                    ['house_group_id' => $extraHouse->id],
                ],
            ])
            ->assertStatus(422);

        $match = SportsMatch::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'discipline_id' => $discipline->id,
        ]);

        $this->actingAs($this->actor, 'api')
            ->putJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/matches/{$match->id}/result", [
                'participants' => [
                    ['house_group_id' => $extraHouse->id],
                ],
            ])
            ->assertStatus(422);
    }

    public function test_duplicate_house_in_one_match_is_rejected(): void
    {
        $discipline = $this->makeDiscipline();

        $this->actingAs($this->actor, 'api')
            ->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/matches", [
                'discipline_id' => $discipline->id,
                'participants' => [
                    ['house_group_id' => $this->houses[0]],
                    ['house_group_id' => $this->houses[0]],
                ],
            ])
            ->assertStatus(422);
    }

    public function test_a_match_from_another_academy_returns_404(): void
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

        $discipline = $this->makeDiscipline();
        $match = SportsMatch::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'discipline_id' => $discipline->id,
        ]);

        $this->actingAs($otherActor, 'api')
            ->getJson("/api/academies/{$otherAcademy->id}/sports-editions/{$this->edition->id}/matches")
            ->assertStatus(404);

        $this->actingAs($otherActor, 'api')
            ->putJson("/api/academies/{$otherAcademy->id}/sports-editions/{$this->edition->id}/matches/{$match->id}/result", [
                'participants' => [
                    ['house_group_id' => $this->houses[0]],
                ],
            ])
            ->assertStatus(404);
    }

    public function test_write_routes_require_sports_manage(): void
    {
        $discipline = $this->makeDiscipline();

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

        $match = SportsMatch::create([
            'edition_id' => $this->edition->id,
            'academy_id' => $this->academy->id,
            'discipline_id' => $discipline->id,
        ]);

        $this->actingAs($viewer, 'api')
            ->getJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/matches")
            ->assertStatus(200);

        $this->actingAs($viewer, 'api')
            ->postJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/matches", [
                'discipline_id' => $discipline->id,
            ])
            ->assertForbidden();

        $this->actingAs($viewer, 'api')
            ->putJson("/api/academies/{$this->academy->id}/sports-editions/{$this->edition->id}/matches/{$match->id}/result", [
                'participants' => [
                    ['house_group_id' => $this->houses[0]],
                ],
            ])
            ->assertForbidden();
    }
}
