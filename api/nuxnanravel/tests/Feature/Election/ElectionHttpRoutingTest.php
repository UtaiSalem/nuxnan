<?php

namespace Tests\Feature\Election;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Election;
use App\Models\ElectionParty;
use App\Models\ElectionStation;
use App\Models\ElectionVoter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElectionHttpRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_station_routes_bind_models_and_return_data(): void
    {
        [$academy, $actor, $election, $station, $voter, $party] = $this->context();
        $base = "/api/academies/{$academy->id}/elections/{$election->id}";

        $this->actingAs($actor, 'api')->postJson("$base/stations/{$station->id}/open")->assertOk()->assertJsonPath('data.is_open', true);
        $this->actingAs($actor, 'api')->getJson("$base/stations/{$station->id}/progress")->assertOk()->assertJsonPath('data.name', 'Station');
        $this->actingAs($actor, 'api')->postJson("$base/stations/{$station->id}/lookup", ['identifier' => (string) $voter->member_code])->assertOk()->assertJsonPath('data.status', 'eligible');
        $this->actingAs($actor, 'api')->getJson("$base/stations/{$station->id}/search?q=Voter")->assertOk()->assertJsonCount(1, 'data.data');
        $this->actingAs($actor, 'api')->getJson("$base/parties")->assertOk()->assertJsonPath('data.0.id', $party->id);
    }

    public function test_real_cast_route_creates_exactly_one_secret_ballot(): void
    {
        [$academy, $actor, $election, $station, $voter, $party] = $this->context();
        $this->actingAs($actor, 'api')->postJson("/api/academies/{$academy->id}/elections/{$election->id}/stations/{$station->id}/open")->assertOk();
        $issue = $this->actingAs($actor, 'api')->postJson("/api/academies/{$academy->id}/elections/{$election->id}/stations/{$station->id}/issue", ['user_id' => $voter->user_id])->assertOk();
        $token = $issue->json('data.ballot_token');
        $before = $election->ballots()->count();
        $this->actingAs($actor, 'api')->postJson("/api/academies/{$academy->id}/elections/{$election->id}/cast", ['ballot_token' => $token, 'party_id' => $party->id])->assertOk();
        $this->assertSame($before + 1, $election->ballots()->count());
    }

    public function test_admin_station_index_is_scoped_and_counted_through_http(): void
    {
        [$academy, $actor, $election, $station] = $this->context();
        $other = Election::create(['academy_id' => $academy->id, 'title' => 'Other', 'created_by' => $actor->id, 'status' => 'voting']);
        ElectionStation::create(['election_id' => $other->id, 'name' => 'Other Station']);

        $response = $this->actingAs($actor, 'api')->getJson("/api/academies/{$academy->id}/elections/{$election->id}/stations");
        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $station->id);
        $response->assertJsonPath('data.0.issued_count', 0)->assertJsonPath('data.0.cast_count', 0);
    }

    public function test_station_and_party_from_another_election_are_not_bound(): void
    {
        [$academy, $actor, $election, $station, , $party] = $this->context();
        $other = Election::create(['academy_id' => $academy->id, 'title' => 'Other', 'created_by' => $actor->id, 'status' => 'voting']);
        $otherStation = ElectionStation::create(['election_id' => $other->id, 'name' => 'Other Station']);
        $otherParty = ElectionParty::create(['election_id' => $other->id, 'name' => 'Other Party', 'status' => 'pending', 'applied_by' => $actor->id]);

        $this->actingAs($actor, 'api')->postJson("/api/academies/{$academy->id}/elections/{$election->id}/stations/{$otherStation->id}/open")->assertNotFound();
        $this->actingAs($actor, 'api')->postJson("/api/academies/{$academy->id}/elections/{$election->id}/parties/{$otherParty->id}/approve", ['number' => 2])->assertNotFound();
        $this->assertDatabaseHas('election_parties', ['id' => $party->id, 'status' => 'approved']);
    }

    public function test_party_approval_route_updates_the_persisted_party(): void
    {
        [$academy, $actor, $election] = $this->context();
        $party = ElectionParty::create(['election_id' => $election->id, 'name' => 'Pending', 'status' => 'pending', 'applied_by' => $actor->id]);

        $this->actingAs($actor, 'api')->postJson("/api/academies/{$academy->id}/elections/{$election->id}/parties/{$party->id}/approve", ['number' => 2])
            ->assertOk()->assertJsonPath('data.status', 'approved')->assertJsonPath('data.number', 2);
        $this->assertDatabaseHas('election_parties', ['id' => $party->id, 'status' => 'approved', 'number' => 2]);
    }

    public function test_view_only_member_cannot_open_station_over_http(): void
    {
        [$academy, , $election, $station] = $this->context();
        $viewer = User::factory()->create();
        $role = AcademyRole::create(['academy_id' => $academy->id, 'name' => uniqid(), 'display_name_th' => 'View', 'permissions' => ['elections.view']]);
        AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $viewer->id, 'academy_role_id' => $role->id, 'status' => 2, 'member_code' => 'VIEWER']);

        $this->actingAs($viewer, 'api')->postJson("/api/academies/{$academy->id}/elections/{$election->id}/stations/{$station->id}/open")->assertForbidden();
    }

    public function test_lookup_user_id_prefers_the_matching_member_over_decoy_member_code(): void
    {
        [$academy, $actor, $election, $station] = $this->context();
        $target = User::factory()->create(['name' => 'Target']);
        AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $target->id, 'status' => 2, 'member_code' => null]);
        AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $actor->id, 'status' => 2, 'member_code' => (string) $target->id]);
        ElectionVoter::create(['election_id' => $election->id, 'user_id' => $target->id, 'display_name' => 'Target', 'voter_type' => 'staff']);

        $this->actingAs($actor, 'api')->postJson("/api/academies/{$academy->id}/elections/{$election->id}/stations/{$station->id}/lookup", ['user_id' => $target->id])
            ->assertOk()->assertJsonPath('data.user_id', $target->id)->assertJsonPath('data.name', 'Target');
    }

    private function context(): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $role = AcademyRole::create(['academy_id' => $academy->id, 'name' => uniqid(), 'display_name_th' => 'Test', 'permissions' => ['elections.view', 'elections.manage', 'elections.station']]);
        $actor = User::factory()->create();
        AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $actor->id, 'academy_role_id' => $role->id, 'status' => 2, 'member_code' => 'ACTOR']);
        $election = Election::create(['academy_id' => $academy->id, 'title' => 'Election', 'created_by' => $owner->id, 'status' => 'voting', 'voter_roll_locked_at' => now()]);
        $party = ElectionParty::create(['election_id' => $election->id, 'name' => 'Party', 'status' => 'approved', 'number' => 1, 'applied_by' => $actor->id]);
        $station = ElectionStation::create(['election_id' => $election->id, 'name' => 'Station']);
        $user = User::factory()->create(['name' => 'Voter']);
        AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $user->id, 'academy_role_id' => $role->id, 'status' => 2, 'member_code' => 123]);
        $voter = ElectionVoter::create(['election_id' => $election->id, 'user_id' => $user->id, 'display_name' => 'Voter', 'member_code' => 123, 'voter_type' => 'student']);

        return [$academy, $actor, $election, $station, $voter, $party];
    }
}
