<?php

namespace Tests\Feature\Election;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Election;
use App\Models\ElectionBallot;
use App\Models\ElectionParty;
use App\Models\ElectionStation;
use App\Models\ElectionVoter;
use App\Models\ElectionVoterReceipt;
use App\Models\MemberActivityLog;
use App\Models\User;
use App\Services\Election\ElectionBallotService;
use App\Services\Election\ElectionStationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ElectionBallotTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_token_is_rejected_without_creating_ballot(): void
    {
        [$academy, $actor, $election, $station, $voter, $party] = $this->context();
        $token = $this->issue($station, $voter, $actor);
        ElectionVoterReceipt::first()->update(['token_expires_at' => now()->subSecond()]);
        $this->assertRejected($election, $token, $party->id, $actor);
    }

    public function test_voided_receipt_is_rejected_without_creating_ballot(): void
    {
        [$academy, $actor, $election, $station, $voter, $party] = $this->context();
        $token = $this->issue($station, $voter, $actor);
        ElectionVoterReceipt::first()->update(['status' => ElectionVoterReceipt::STATUS_VOID]);
        $this->assertRejected($election, $token, $party->id, $actor);
    }

    public function test_closed_station_is_rejected_without_creating_ballot(): void
    {
        [$academy, $actor, $election, $station, $voter, $party] = $this->context();
        $token = $this->issue($station, $voter, $actor);
        $station->update(['is_open' => false]);
        $this->assertRejected($election, $token, $party->id, $actor);
    }

    public function test_non_voting_election_is_rejected_without_creating_ballot(): void
    {
        [$academy, $actor, $election, $station, $voter, $party] = $this->context();
        $token = $this->issue($station, $voter, $actor);
        $election->update(['status' => 'closed']);
        $this->assertRejected($election, $token, $party->id, $actor);
    }

    public function test_unknown_token_is_rejected_without_creating_ballot(): void
    {
        [$academy, $actor, $election, $station, $voter, $party] = $this->context();
        $this->assertRejected($election, 'garbage-token', $party->id, $actor);
    }

    public function test_abstention_is_rejected_when_election_disallows_it(): void
    {
        [$academy, $actor, $election, $station, $voter, $party] = $this->context(['allow_abstain' => false]);
        $token = $this->issue($station, $voter, $actor);
        $this->assertRejected($election, $token, null, $actor);
    }

    public function test_party_from_different_election_is_rejected(): void
    {
        [$academy, $actor, $election, $station, $voter, $party] = $this->context();
        $other = Election::create(['academy_id' => $academy->id, 'title' => 'Other', 'created_by' => $actor->id, 'status' => 'voting', 'allow_abstain' => true, 'voter_roll_locked_at' => now()]);
        $otherParty = ElectionParty::create(['election_id' => $other->id, 'name' => 'Other P', 'status' => ElectionParty::STATUS_APPROVED, 'number' => 2, 'applied_by' => $actor->id]);
        $token = $this->issue($station, $voter, $actor);
        $this->assertRejected($election, $token, $otherParty->id, $actor);
    }

    public function test_pending_party_is_rejected(): void
    {
        [$academy, $actor, $election, $station, $voter, $party] = $this->context();
        $party->update(['status' => ElectionParty::STATUS_PENDING]);
        $token = $this->issue($station, $voter, $actor);
        $this->assertRejected($election, $token, $party->id, $actor);
    }

    public function test_withdrawn_party_is_rejected(): void
    {
        [$academy, $actor, $election, $station, $voter, $party] = $this->context();
        $party->update(['status' => ElectionParty::STATUS_WITHDRAWN]);
        $token = $this->issue($station, $voter, $actor);
        $this->assertRejected($election, $token, $party->id, $actor);
    }

    public function test_valid_token_casts_ballot_and_consumes_receipt(): void
    {
        [$academy, $actor, $election, $station, $voter, $party] = $this->context();
        $token = $this->issue($station, $voter, $actor);
        $result = app(ElectionBallotService::class)->cast($election, $token, $party->id, $actor);
        $this->assertSame(['success' => true], $result);
        $this->assertSame(1, ElectionBallot::where('election_id', $election->id)->count());
        $this->assertDatabaseHas('election_voter_receipts', ['status' => 'cast', 'token_hash' => null]);
    }

    public function test_consumed_token_is_rejected_without_second_ballot(): void
    {
        [$academy, $actor, $election, $station, $voter, $party] = $this->context();
        $token = $this->issue($station, $voter, $actor);
        app(ElectionBallotService::class)->cast($election, $token, $party->id, $actor);
        $this->assertRejected($election, $token, $party->id, $actor, 1);
        $this->assertSame(1, ElectionBallot::where('election_id', $election->id)->count());
    }

    public function test_abstention_casts_when_election_allows_it(): void
    {
        [$academy, $actor, $election, $station, $voter, $party] = $this->context();
        $token = $this->issue($station, $voter, $actor);
        app(ElectionBallotService::class)->cast($election, $token, null, $actor);
        $this->assertNull(ElectionBallot::first()->party_id);
    }

    public function test_cast_receipt_has_zero_seconds(): void
    {
        [$academy, $actor, $election, $station, $voter, $party] = $this->context();
        $token = $this->issue($station, $voter, $actor);
        app(ElectionBallotService::class)->cast($election, $token, $party->id, $actor);
        $this->assertSame(0, ElectionVoterReceipt::first()->fresh()->cast_at->second);
    }

    public function test_ten_casts_produce_correct_party_tallies(): void
    {
        [$academy, $actor, $election, $station, $voter, $party] = $this->context();
        $partyTwo = ElectionParty::create(['election_id' => $election->id, 'name' => 'P2', 'status' => ElectionParty::STATUS_APPROVED, 'number' => 2, 'applied_by' => $actor->id]);
        foreach (range(1, 10) as $i) {
            $user = User::factory()->create();
            $newVoter = ElectionVoter::create(['election_id' => $election->id, 'user_id' => $user->id, 'display_name' => $user->name, 'voter_type' => 'student']);
            $token = $this->issue($station, $newVoter, $actor);
            app(ElectionBallotService::class)->cast($election, $token, $i <= 6 ? $party->id : ($i <= 9 ? $partyTwo->id : null), $actor);
        }
        $this->assertSame(10, ElectionBallot::where('election_id', $election->id)->count());
        $this->assertSame(10, ElectionVoterReceipt::where('election_id', $election->id)->where('status', 'cast')->count());
        $this->assertSame(6, ElectionBallot::where('party_id', $party->id)->count());
        $this->assertSame(3, ElectionBallot::where('party_id', $partyTwo->id)->count());
        $this->assertSame(1, ElectionBallot::whereNull('party_id')->count());
    }

    public function test_ballot_schema_contains_only_secret_columns(): void
    {
        $this->assertSame(['election_id', 'party_id', 'uuid'], collect(Schema::getColumnListing('election_ballots'))->sort()->values()->all());
    }

    public function test_casting_writes_no_voter_choice_activity_log(): void
    {
        [$academy, $actor, $election, $station, $voter, $party] = $this->context();
        $token = $this->issue($station, $voter, $actor);
        $before = MemberActivityLog::count();
        app(ElectionBallotService::class)->cast($election, $token, $party->id, $actor);
        $newLogs = MemberActivityLog::where('id', '>', $before)->get();
        foreach ($newLogs as $log) {
            $values = array_merge($log->old_values ?? [], $log->new_values ?? []);
            $this->assertArrayNotHasKey('party_id', $values);
            $this->assertFalse(isset($values['user_id']) && array_key_exists('party_id', $values));
        }
        $this->assertCount(0, $newLogs);
    }

    public function test_sequential_ballots_have_distinct_uuidv4_values(): void
    {
        [$academy, $actor, $election, $station, $voter, $party] = $this->context();
        foreach (range(1, 2) as $i) {
            $user = $i === 1 ? $voter->user : User::factory()->create();
            $newVoter = $i === 1 ? $voter : ElectionVoter::create(['election_id' => $election->id, 'user_id' => $user->id, 'display_name' => $user->name, 'voter_type' => 'student']);
            app(ElectionBallotService::class)->cast($election, $this->issue($station, $newVoter, $actor), $party->id, $actor);
        }
        $uuids = ElectionBallot::pluck('uuid')->all();
        $this->assertNotSame($uuids[0], $uuids[1]);
        foreach ($uuids as $uuid) {
            $this->assertTrue(Str::isUuid($uuid));
            $this->assertSame('4', $uuid[14]);
        }
    }

    public function test_integrity_matches_cast_ballot_counts(): void
    {
        [$academy, $actor, $election, $station, $voter, $party] = $this->context();
        app(ElectionBallotService::class)->cast($election, $this->issue($station, $voter, $actor), $party->id, $actor);
        $this->assertSame(['ballots' => 1, 'cast_receipts' => 1, 'matches' => true], app(ElectionBallotService::class)->verifyIntegrity($election));
    }

    public function test_integrity_detects_deleted_ballot(): void
    {
        [$academy, $actor, $election, $station, $voter, $party] = $this->context();
        app(ElectionBallotService::class)->cast($election, $this->issue($station, $voter, $actor), $party->id, $actor);
        DB::table('election_ballots')->delete();
        $this->assertFalse(app(ElectionBallotService::class)->verifyIntegrity($election)['matches']);
    }

    private function assertRejected(Election $election, string $token, ?int $partyId, User $actor, int $expectedBallots = 0): void
    {
        try {
            app(ElectionBallotService::class)->cast($election, $token, $partyId, $actor);
            $this->fail('Expected DomainException.');
        } catch (\DomainException) {
            $this->assertSame($expectedBallots, ElectionBallot::where('election_id', $election->id)->count());
        }
    }

    private function issue(ElectionStation $station, ElectionVoter $voter, User $actor): string
    {
        return app(ElectionStationService::class)->issue($station, $voter->user_id, $actor)['ballot_token'];
    }

    private function context(array $electionOverrides = []): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $role = AcademyRole::create(['academy_id' => $academy->id, 'name' => uniqid(), 'display_name_th' => 'Test', 'permissions' => ['elections.station']]);
        $actor = User::factory()->create();
        AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $actor->id, 'academy_role_id' => $role->id, 'status' => 2]);
        $election = Election::create(array_merge(['academy_id' => $academy->id, 'title' => 'Election', 'created_by' => $owner->id, 'status' => 'voting', 'allow_abstain' => true, 'voter_roll_locked_at' => now()], $electionOverrides));
        $party = ElectionParty::create(['election_id' => $election->id, 'name' => 'P', 'status' => ElectionParty::STATUS_APPROVED, 'number' => 1, 'applied_by' => $actor->id]);
        $station = ElectionStation::create(['election_id' => $election->id, 'name' => 'Station', 'is_open' => true]);
        $voter = ElectionVoter::create(['election_id' => $election->id, 'user_id' => $actor->id, 'display_name' => $actor->name, 'voter_type' => 'student']);

        return [$academy, $actor, $election, $station, $voter, $party];
    }
}
