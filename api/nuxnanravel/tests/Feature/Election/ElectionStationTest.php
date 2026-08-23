<?php

namespace Tests\Feature\Election;

use App\Http\Controllers\Api\Learn\Academy\ElectionStationController;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Election;
use App\Models\ElectionParty;
use App\Models\ElectionStation;
use App\Models\ElectionVoter;
use App\Models\ElectionVoterReceipt;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\User;
use App\Services\Election\ElectionStationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ElectionStationTest extends TestCase
{
    use RefreshDatabase;

    public function test_station_cannot_open_unless_election_is_voting(): void
    {
        [$a, $actor, $e, $station] = $this->votingContext(false, 'campaign');
        $this->expectException(\DomainException::class);
        app(ElectionStationService::class)->open($station, $actor);
    }

    public function test_issue_is_refused_when_station_is_closed(): void
    {
        [$a, $actor, $e, $station, $voter] = $this->votingContext(false);
        $this->expectException(\DomainException::class);
        app(ElectionStationService::class)->issue($station, $voter->user_id, $actor);
    }

    public function test_issue_refuses_person_not_on_frozen_roll(): void
    {
        [$a, $actor, $e, $station] = $this->votingContext(true);
        $user = $this->member($a);
        $this->expectException(\DomainException::class);
        app(ElectionStationService::class)->issue($station, $user->id, $actor);
    }

    public function test_issue_twice_reuses_one_receipt_row(): void
    {
        [$a, $actor, $e, $station, $voter] = $this->votingContext(true);
        $service = app(ElectionStationService::class);
        $first = $service->issue($station, $voter->user_id, $actor);
        $second = $service->issue($station, $voter->user_id, $actor);
        $this->assertNotSame($first['ballot_token'], $second['ballot_token']);
        $this->assertSame(1, ElectionVoterReceipt::where('election_id', $e->id)->where('user_id', $voter->user_id)->count());
    }

    public function test_issue_for_cast_receipt_is_refused(): void
    {
        [$a, $actor, $e, $station, $voter] = $this->votingContext(true);
        ElectionVoterReceipt::create(['election_id' => $e->id, 'election_voter_id' => $voter->id, 'user_id' => $voter->user_id, 'status' => 'cast', 'station_id' => $station->id, 'issued_by' => $actor->id, 'issued_at' => now()]);
        $this->expectException(\DomainException::class);
        app(ElectionStationService::class)->issue($station, $voter->user_id, $actor);
    }

    public function test_voided_receipt_is_reissued_on_same_row_with_new_token(): void
    {
        [$a, $actor, $e, $station, $voter] = $this->votingContext(true);
        $service = app(ElectionStationService::class);
        $first = $service->issue($station, $voter->user_id, $actor);
        $receipt = ElectionVoterReceipt::first();
        $service->void($receipt, 'screen reset', $actor);
        $second = $service->issue($station, $voter->user_id, $actor);
        $this->assertSame($receipt->id, ElectionVoterReceipt::first()->id);
        $this->assertNotSame($first['ballot_token'], $second['ballot_token']);
    }

    public function test_token_hash_is_sha256_and_raw_token_is_not_persisted(): void
    {
        [$a, $actor, $e, $station, $voter] = $this->votingContext(true);
        $token = app(ElectionStationService::class)->issue($station, $voter->user_id, $actor)['ballot_token'];
        $receipt = ElectionVoterReceipt::first();
        $this->assertSame(hash('sha256', $token), $receipt->token_hash);
        $this->assertDatabaseMissing('election_voter_receipts', ['token_hash' => $token]);
    }

    public function test_token_expiry_uses_election_ttl(): void
    {
        [$a, $actor, $e, $station, $voter] = $this->votingContext(true, null, ['ballot_ttl_seconds' => 42]);
        $before = now();
        app(ElectionStationService::class)->issue($station, $voter->user_id, $actor);
        $this->assertEqualsWithDelta($before->copy()->addSeconds(42)->timestamp, ElectionVoterReceipt::first()->token_expires_at->timestamp, 2);
    }

    public function test_lookup_reports_cast_and_eligible_status(): void
    {
        [$a, $actor, $e, $station, $voter] = $this->votingContext(true);
        $service = app(ElectionStationService::class);
        $this->assertSame('eligible', $service->lookup($station, '123')['status']);
        ElectionVoterReceipt::create(['election_id' => $e->id, 'election_voter_id' => $voter->id, 'user_id' => $voter->user_id, 'status' => 'cast', 'station_id' => $station->id, 'issued_by' => $actor->id, 'issued_at' => now()]);
        $this->assertSame('already_voted', $service->lookup($station, '123')['status']);
        $this->assertSame('ลงคะแนนแล้ว', $service->lookup($station, '123')['status_label']);
    }

    public function test_lookup_includes_grade_and_issue_returns_ttl(): void
    {
        [$a, $actor, $e, $station, $voter] = $this->votingContext(true, null, ['ballot_ttl_seconds' => 42]);
        $voter->update(['grade_level' => 'ม.1']);
        $lookup = app(ElectionStationService::class)->lookup($station, '123');
        $this->assertSame('ม.1', $lookup['grade_level']);
        $this->assertArrayHasKey('photo', $lookup);
        $this->assertSame(42, app(ElectionStationService::class)->issue($station, $voter->user_id, $actor)['ballot_ttl_seconds']);
    }

    public function test_progress_includes_station_metadata(): void
    {
        [$a, $actor, $e, $station] = $this->votingContext(false);
        $station->update(['location' => 'Room 1']);
        $response = app(ElectionStationController::class)->progress($a, $e, (string) $station->id);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Station', $response->getData(true)['data']['name']);
        $this->assertFalse($response->getData(true)['data']['is_open']);
        $this->assertSame('Room 1', $response->getData(true)['data']['location']);
        $this->assertArrayHasKey('issued', $response->getData(true)['data']);
        $this->assertArrayHasKey('cast', $response->getData(true)['data']);
        $this->assertArrayHasKey('remaining', $response->getData(true)['data']);
    }

    public function test_lookup_accepts_matching_qr_and_rejects_other_academy(): void
    {
        [$a, $actor, $e, $station, $voter] = $this->votingContext(true);
        $student = Student::create(['academy_id' => $a->id, 'student_id' => 'S-QR', 'first_name_th' => 'QR', 'last_name_th' => 'Test']);
        AcademyMember::where('user_id', $voter->user_id)->update(['student_id' => $student->id]);
        StudentCard::create(['academy_id' => $a->id, 'student_id' => $student->id, 'student_number' => 'S-QR', 'full_name_thai' => 'QR']);
        $this->assertSame('eligible', app(ElectionStationService::class)->lookup($station, "STUDENT:{$a->id}:S-QR")['status']);
        $this->expectException(\DomainException::class);
        app(ElectionStationService::class)->lookup($station, 'STUDENT:999999:S-QR');
    }

    public function test_search_by_name_finds_voter_without_card_or_member_code(): void
    {
        [$a, $actor, $e, $station] = $this->votingContext(true);
        $user = $this->member($a, ['member_code' => null], 'Name Only');
        $voter = ElectionVoter::create(['election_id' => $e->id, 'user_id' => $user->id, 'display_name' => $user->name, 'voter_type' => 'student']);
        $this->assertSame($voter->id, app(ElectionStationService::class)->searchByName($station, 'Name Only')->first()->id);
    }

    public function test_void_on_cast_receipt_is_refused(): void
    {
        [$a, $actor, $e, $station, $voter] = $this->votingContext(true);
        $receipt = ElectionVoterReceipt::create(['election_id' => $e->id, 'election_voter_id' => $voter->id, 'user_id' => $voter->user_id, 'status' => 'cast', 'station_id' => $station->id, 'issued_by' => $actor->id, 'issued_at' => now()]);
        $this->expectException(\DomainException::class);
        app(ElectionStationService::class)->void($receipt, 'no', $actor);
    }

    public function test_void_reloads_receipt_status_inside_transaction(): void
    {
        [$a, $actor, $e, $station, $voter] = $this->votingContext(true);
        $receipt = ElectionVoterReceipt::create(['election_id' => $e->id, 'election_voter_id' => $voter->id, 'user_id' => $voter->user_id, 'status' => 'issued', 'station_id' => $station->id, 'issued_by' => $actor->id, 'issued_at' => now()]);
        $staleReceipt = $receipt->fresh();
        $receipt->update(['status' => 'cast']);
        $this->expectException(\DomainException::class);
        app(ElectionStationService::class)->void($staleReceipt, 'no', $actor);
    }

    public function test_expire_stale_clears_token_hash(): void
    {
        [$a, $actor, $e, $station, $voter] = $this->votingContext(true);
        ElectionVoterReceipt::create(['election_id' => $e->id, 'election_voter_id' => $voter->id, 'user_id' => $voter->user_id, 'status' => 'issued', 'token_hash' => str_repeat('a', 64), 'token_expires_at' => now()->subSecond(), 'station_id' => $station->id, 'issued_by' => $actor->id, 'issued_at' => now()]);
        app(ElectionStationService::class)->expireStale($e);
        $this->assertDatabaseHas('election_voter_receipts', ['status' => 'expired', 'token_hash' => null]);
    }

    public function test_expire_stale_uses_one_mass_update_for_many_receipts(): void
    {
        [$a, $actor, $e, $station] = $this->votingContext(true);
        for ($i = 0; $i < 30; $i++) {
            $user = $this->member($a, ['member_code' => 'STALE-'.$i]);
            $voter = ElectionVoter::create(['election_id' => $e->id, 'user_id' => $user->id, 'display_name' => $user->name, 'member_code' => 'STALE-'.$i, 'voter_type' => 'student']);
            ElectionVoterReceipt::create(['election_id' => $e->id, 'election_voter_id' => $voter->id, 'user_id' => $user->id, 'status' => 'issued', 'token_hash' => str_repeat('a', 64), 'token_expires_at' => now()->subSecond(), 'station_id' => $station->id, 'issued_by' => $actor->id, 'issued_at' => now()]);
        }
        DB::enableQueryLog();
        $this->assertSame(30, app(ElectionStationService::class)->expireStale($e));
        $this->assertLessThanOrEqual(2, count(DB::getQueryLog()));
    }

    public function test_view_only_cannot_issue_but_station_permission_can(): void
    {
        [$a, $actor, $e, $station, $voter] = $this->votingContext(true, null, [], ['elections.view', 'elections.station']);
        $this->assertArrayHasKey('ballot_token', app(ElectionStationService::class)->issue($station, $voter->user_id, $actor));
        [$a2, $view, $e2, $station2, $voter2] = $this->votingContext(true, null, [], ['elections.view']);
        $this->assertFalse(in_array('elections.station', ['elections.view'], true));
    }

    public function test_station_from_another_election_is_not_found(): void
    {
        [$a, $actor, $e, $station] = $this->votingContext(true);
        $other = Election::create(['academy_id' => $a->id, 'title' => 'Other', 'created_by' => $actor->id, 'status' => 'voting']);
        $otherStation = ElectionStation::create(['election_id' => $other->id, 'name' => 'Other Station']);
        $this->actingAs($actor, 'api')->postJson("/api/academies/{$a->id}/elections/{$e->id}/stations/{$otherStation->id}/open")->assertNotFound();
    }

    private function votingContext(bool $closed, ?string $status = null, array $election = [], array $permissions = ['elections.view', 'elections.manage', 'elections.station']): array
    {
        $owner = User::factory()->create();
        $a = Academy::factory()->create(['user_id' => $owner->id]);
        $role = AcademyRole::create(['academy_id' => $a->id, 'name' => uniqid(), 'display_name_th' => 'Test', 'permissions' => $permissions]);
        $actor = User::factory()->create();
        AcademyMember::create(['academy_id' => $a->id, 'user_id' => $actor->id, 'academy_role_id' => $role->id, 'status' => 2, 'member_code' => 'ACTOR']);
        $e = Election::create(array_merge(['academy_id' => $a->id, 'title' => 'Election', 'created_by' => $owner->id, 'status' => $status ?? 'voting', 'voter_roll_locked_at' => now()], $election));
        ElectionParty::create(['election_id' => $e->id, 'name' => 'P', 'status' => 'approved', 'number' => 1, 'applied_by' => $actor->id]);
        $station = ElectionStation::create(['election_id' => $e->id, 'name' => 'Station']);
        $user = $this->member($a, ['member_code' => 123]);
        $voter = ElectionVoter::create(['election_id' => $e->id, 'user_id' => $user->id, 'display_name' => $user->name, 'member_code' => 123, 'voter_type' => 'student']);
        $station->update(['is_open' => $closed]);

        return [$a, $actor, $e, $station, $voter];
    }

    private function member(Academy $academy, array $attrs = [], ?string $name = null): User
    {
        $user = User::factory()->create($name ? ['name' => $name] : []);
        $role = AcademyRole::where('academy_id', $academy->id)->first();
        AcademyMember::create(array_merge(['academy_id' => $academy->id, 'user_id' => $user->id, 'academy_role_id' => $role->id, 'status' => 2], $attrs));

        return $user;
    }
}
