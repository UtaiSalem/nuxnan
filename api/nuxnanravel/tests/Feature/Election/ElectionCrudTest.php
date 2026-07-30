<?php

namespace Tests\Feature\Election;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Election;
use App\Models\ElectionBallot;
use App\Models\ElectionStation;
use App\Models\ElectionVoter;
use App\Models\ElectionVoterReceipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElectionCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_update_returns_422(): void
    {
        [$academy, $user, $election] = $this->context(['status' => 'published']);
        $this->actingAs($user, 'api')->putJson("/api/academies/{$academy->id}/elections/{$election->id}", ['title' => 'Changed'])->assertStatus(422);
    }

    public function test_destroy_with_ballot_returns_422(): void
    {
        [$academy, $user, $election] = $this->context();
        ElectionBallot::create(['election_id' => $election->id]);
        $this->actingAs($user, 'api')->deleteJson("/api/academies/{$academy->id}/elections/{$election->id}")->assertStatus(422);
    }

    public function test_store_always_creates_draft(): void
    {
        [$academy, $user] = $this->context();
        $this->actingAs($user, 'api')->postJson("/api/academies/{$academy->id}/elections", ['title' => 'New', 'status' => 'published'])->assertCreated();
        $this->assertDatabaseHas('elections', ['academy_id' => $academy->id, 'title' => 'New', 'status' => 'draft']);
    }

    public function test_view_only_user_cannot_mutate_election(): void
    {
        [$academy, $user, $election] = $this->context([], ['elections.view']);
        $base = "/api/academies/{$academy->id}/elections";
        $this->actingAs($user, 'api')->postJson($base, ['title' => 'New'])->assertForbidden();
        $this->actingAs($user, 'api')->putJson("$base/{$election->id}", ['title' => 'New'])->assertForbidden();
        $this->actingAs($user, 'api')->deleteJson("$base/{$election->id}")->assertForbidden();
        $this->actingAs($user, 'api')->postJson("$base/{$election->id}/status", ['status' => 'nomination'])->assertForbidden();
    }

    public function test_user_without_election_permission_cannot_index(): void
    {
        [$academy, $user] = $this->context([], []);
        $this->actingAs($user, 'api')->getJson("/api/academies/{$academy->id}/elections")->assertForbidden();
    }

    public function test_show_from_another_academy_is_not_found(): void
    {
        [$academy, $user] = $this->context();
        [, , $otherElection] = $this->context();
        $this->actingAs($user, 'api')->getJson("/api/academies/{$academy->id}/elections/{$otherElection->id}")->assertNotFound();
    }

    public function test_issued_receipt_is_not_counted_as_cast(): void
    {
        [$academy, $user, $election] = $this->context();
        $station = ElectionStation::create(['election_id' => $election->id, 'name' => 'Station']);
        $voter = ElectionVoter::create(['election_id' => $election->id, 'user_id' => $user->id, 'display_name' => 'Voter', 'voter_type' => 'student']);
        ElectionVoterReceipt::create(['election_id' => $election->id, 'election_voter_id' => $voter->id, 'user_id' => $user->id, 'station_id' => $station->id, 'issued_by' => $user->id, 'issued_at' => now(), 'status' => 'issued']);
        $this->actingAs($user, 'api')->getJson("/api/academies/{$academy->id}/elections")->assertOk()->assertJsonPath('data.data.0.receipts_cast_count', 0);
    }

    private function context(array $election = [], array $permissions = ['elections.view', 'elections.manage']): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $role = AcademyRole::create(['academy_id' => $academy->id, 'name' => uniqid('role'), 'display_name_th' => 'Test', 'permissions' => $permissions]);
        $user = User::factory()->create();
        AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $user->id, 'academy_role_id' => $role->id, 'status' => 2]);
        $electionModel = Election::create(array_merge(['academy_id' => $academy->id, 'title' => 'Election', 'created_by' => $owner->id], $election));

        return [$academy, $permissions ? $user : $user, $electionModel];
    }
}
