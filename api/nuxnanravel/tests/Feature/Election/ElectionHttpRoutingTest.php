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
