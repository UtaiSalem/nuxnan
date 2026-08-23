<?php

namespace Tests\Feature\Election;

use App\Models\Academy;
use App\Models\Election;
use App\Models\ElectionBallot;
use App\Models\ElectionParty;
use App\Models\ElectionStation;
use App\Models\ElectionVoter;
use App\Models\ElectionVoterReceipt;
use App\Models\User;
use App\Services\Election\ElectionResultService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ElectionResultTest extends TestCase
{
    use RefreshDatabase;

    public function test_close_and_count_requires_voting_status(): void
    {
        [$e,$a] = $this->context('closed');
        $this->expectException(DomainException::class);
        app(ElectionResultService::class)->closeAndCount($e, $a);
    }

    public function test_stale_issued_receipts_expire_during_close(): void
    {
        [$e,$a] = $this->context();
        $voter = ElectionVoter::create(['election_id' => $e->id, 'user_id' => User::factory()->create()->id, 'display_name' => 'Voter', 'voter_type' => 'student']);
        $station = ElectionStation::create(['election_id' => $e->id, 'name' => 'S']);
        ElectionVoterReceipt::create(['election_id' => $e->id, 'election_voter_id' => $voter->id, 'user_id' => $voter->user_id, 'station_id' => $station->id, 'issued_by' => $a->id, 'status' => 'issued', 'issued_at' => now()->subMinutes(2), 'token_expires_at' => now()->subMinute()]);
        app(ElectionResultService::class)->closeAndCount($e, $a);
        $this->assertDatabaseHas('election_voter_receipts', ['election_id' => $e->id, 'status' => 'expired']);
    }

    public function test_mismatch_names_counts_and_writes_no_results(): void
    {
        [$e,$a] = $this->context();
        $this->vote($e, 1);
        DB::table('election_ballots')->delete();
        $this->expectExceptionMessage('ballots=0');
        $this->expectException(DomainException::class);
        app(ElectionResultService::class)->closeAndCount($e, $a);
    }

    public function test_mixed_tallies_are_correct(): void
    {
        [$e,$a,$p1,$p2] = $this->context();
        $this->vote($e, 4, $p1);
        $this->vote($e, 2, $p2);
        $this->vote($e, 1, null);
        app(ElectionResultService::class)->closeAndCount($e, $a);
        $this->assertSame([4, 2, 1], DB::table('election_results')->where('election_id', $e->id)->orderByDesc('votes')->pluck('votes')->all());
    }

    public function test_abstain_is_unranked_and_not_winner(): void
    {
        [$e,$a] = $this->context();
        $this->vote($e, 1, null);
        app(ElectionResultService::class)->closeAndCount($e, $a);
        $r = DB::table('election_results')->where('election_id', $e->id)->whereNull('party_id')->first();
        $this->assertNull($r->rank);
        $this->assertFalse((bool) $r->is_winner);
    }

    public function test_ties_share_rank(): void
    {
        [$e,$a,$p1,$p2] = $this->context();
        $p3 = ElectionParty::create(['election_id' => $e->id, 'name' => 'P3', 'status' => 'approved', 'number' => 3, 'applied_by' => $a->id]);
        foreach (range(1, 25) as $i) {
            $this->vote($e, 1, $i <= 10 ? $p1 : ($i <= 20 ? $p2 : $p3));
        }
        app(ElectionResultService::class)->closeAndCount($e, $a);
        $rows = DB::table('election_results')->whereNotNull('party_id')->orderByDesc('votes')->get();
        $this->assertSame([1, 1, 3], $rows->pluck('rank')->all());
        $this->assertSame(2, $rows->where('is_winner', 1)->count());
    }

    public function test_clear_winner_is_rank_one(): void
    {
        [$e,$a,$p1,$p2] = $this->context();
        $this->vote($e, 2, $p1);
        $this->vote($e, 1, $p2);
        app(ElectionResultService::class)->closeAndCount($e, $a);
        $this->assertDatabaseHas('election_results', ['party_id' => $p1->id, 'rank' => 1, 'is_winner' => 1]);
    }

    public function test_counting_twice_does_not_duplicate_abstain(): void
    {
        [$e,$a] = $this->context();
        $this->vote($e, 1, null);
        app(ElectionResultService::class)->closeAndCount($e, $a);
        $this->assertCount(1, DB::table('election_results')->where('election_id', $e->id)->get());
    }

    public function test_results_are_unavailable_before_publication(): void
    {
        [$e,$a] = $this->context();
        $this->assertNotSame(200, $this->getJson("/api/academies/{$e->academy_id}/elections/{$e->id}/results")->status());
    }

    public function test_http_publish_exposes_results_and_rejects_republication(): void
    {
        [$e, $a, $party] = $this->context();
        $this->vote($e, 1, $party);
        $base = "/api/academies/{$e->academy_id}/elections/{$e->id}";
        $this->actingAs($a, 'api')->postJson("{$base}/close-and-count")->assertOk();
        $this->actingAs($a, 'api')->getJson("{$base}/results")->assertNotFound();

        $this->actingAs($a, 'api')->postJson("{$base}/publish")->assertOk();
        $this->actingAs($a, 'api')->getJson("{$base}/results")
            ->assertOk()
            ->assertJsonPath('data.0.party_id', $party->id)
            ->assertJsonPath('data.0.votes', 1);
        $this->actingAs($a, 'api')->postJson("{$base}/publish")->assertStatus(422);
    }

    public function test_publish_sets_timestamp_and_status(): void
    {
        [$e,$a] = $this->context();
        $this->vote($e, 1);
        app(ElectionResultService::class)->closeAndCount($e, $a);
        app(ElectionResultService::class)->publish($e, $a);
        $this->assertSame('published', $e->fresh()->status);
        $this->assertNotNull(DB::table('election_results')->first()->published_at);
    }

    public function test_publish_twice_is_refused(): void
    {
        [$e,$a] = $this->context();
        $this->vote($e, 1);
        $s = app(ElectionResultService::class);
        $s->closeAndCount($e, $a);
        $s->publish($e, $a);
        $this->expectException(DomainException::class);
        $s->publish($e->fresh(), $a);
    }

    public function test_publish_refuses_integrity_mismatch(): void
    {
        [$e,$a] = $this->context();
        $this->vote($e, 1);
        $s = app(ElectionResultService::class);
        $s->closeAndCount($e, $a);
        DB::table('election_ballots')->delete();
        $this->expectException(DomainException::class);
        $s->publish($e->fresh(), $a);
    }

    public function test_published_results_are_frozen(): void
    {
        [$e,$a] = $this->context();
        $this->vote($e, 1);
        $s = app(ElectionResultService::class);
        $s->closeAndCount($e, $a);
        $s->publish($e, $a);
        $before = $s->results($e->fresh());
        DB::table('election_ballots')->delete();
        $this->assertEquals($before->toArray(), $s->results($e->fresh())->toArray());
    }

    public function test_turnout_has_no_party_tallies(): void
    {
        [$e,$a] = $this->context();
        $data = app(ElectionResultService::class)->turnout($e);
        $this->assertArrayNotHasKey('party_id', $data);
        $this->assertArrayNotHasKey('votes', $data);
    }

    public function test_turnout_breaks_down_by_grade_and_station(): void
    {
        [$e,$a] = $this->context();
        $data = app(ElectionResultService::class)->turnout($e);
        $this->assertArrayHasKey('by_grade_level', $data);
        $this->assertArrayHasKey('by_station', $data);
    }

    private function context(string $status = 'voting'): array
    {
        $a = User::factory()->create();
        $ac = Academy::factory()->create(['user_id' => $a->id]);
        $e = Election::create(['academy_id' => $ac->id, 'title' => 'E', 'created_by' => $a->id, 'status' => $status, 'allow_abstain' => true, 'voter_roll_locked_at' => now()]);
        $p1 = ElectionParty::create(['election_id' => $e->id, 'name' => 'P1', 'status' => 'approved', 'number' => 1, 'applied_by' => $a->id]);
        $p2 = ElectionParty::create(['election_id' => $e->id, 'name' => 'P2', 'status' => 'approved', 'number' => 2, 'applied_by' => $a->id]);

        return [$e, $a, $p1, $p2];
    }

    private function vote(Election $e, int $n, ?ElectionParty $party = null): void
    {
        for ($i = 0; $i < $n; $i++) {
            $voter = ElectionVoter::create(['election_id' => $e->id, 'user_id' => User::factory()->create()->id, 'display_name' => 'Voter', 'voter_type' => 'student']);
            $station = ElectionStation::create(['election_id' => $e->id, 'name' => 'S'.$i.Str::random(3)]);
            $id = (string) Str::uuid();
            ElectionBallot::create(['uuid' => $id, 'election_id' => $e->id, 'party_id' => $party?->id]);
            ElectionVoterReceipt::create(['election_id' => $e->id, 'election_voter_id' => $voter->id, 'user_id' => $voter->user_id, 'station_id' => $station->id, 'issued_by' => $voter->user_id, 'status' => 'cast', 'issued_at' => now()->subMinute(), 'cast_at' => now()]);
        }
    }
}
