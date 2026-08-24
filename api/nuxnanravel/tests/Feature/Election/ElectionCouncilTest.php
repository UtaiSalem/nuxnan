<?php

namespace Tests\Feature\Election;

use App\Constants\AcademyGroupPermissions;
use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\AcademyGroupPermission;
use App\Models\Election;
use App\Models\ElectionParty;
use App\Models\ElectionPartyMember;
use App\Models\ElectionResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElectionCouncilTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_creates_council_and_returns_data_id(): void
    {
        [$election, $actor, $party] = $this->context();
        $response = $this->actingAs($actor, 'api')->postJson($this->url($election));
        $response->assertCreated()->assertJsonPath('data.id', fn ($id) => (bool) $id);
        $this->assertDatabaseHas('academy_groups', ['type' => 'student_council']);
        $this->assertSame($election->id, AcademyGroup::first()->settings['election_id']);
    }

    public function test_members_keep_roles_and_leader_is_group_admin(): void
    {
        [$election, $actor, $party, $leader, $member] = $this->context();
        $this->actingAs($actor, 'api')->postJson($this->url($election))->assertCreated();
        $this->assertDatabaseHas('academy_group_members', ['user_id' => $leader->id, 'role' => 'leader', 'status' => 2]);
        $this->assertDatabaseHas('academy_group_members', ['user_id' => $member->id, 'role' => 'member', 'status' => 2]);
        $this->assertDatabaseHas('academy_group_admins', ['user_id' => $leader->id, 'role' => 'leader']);
    }

    public function test_permissions_are_seeded(): void
    {
        [$election, $actor] = $this->context();
        $this->actingAs($actor, 'api')->postJson($this->url($election))->assertCreated();
        $this->assertSame(count(AcademyGroupPermissions::PERMISSIONS), AcademyGroupPermission::count());
    }

    public function test_unpublished_election_is_refused(): void
    {
        [$election, $actor] = $this->context(false);
        $this->actingAs($actor, 'api')->postJson($this->url($election))->assertUnprocessable()->assertJsonPath('message', fn ($message) => is_string($message) && $message !== '');
    }

    public function test_tied_winners_are_refused_with_names_and_votes(): void
    {
        [$election, $actor, $party] = $this->context();
        $other = ElectionParty::create(['election_id' => $election->id, 'name' => 'พรรคสอง', 'status' => 'approved', 'number' => 2, 'applied_by' => $actor->id]);
        ElectionResult::create(['election_id' => $election->id, 'party_id' => $other->id, 'votes' => 8, 'rank' => 1, 'is_winner' => true, 'published_at' => now()]);
        $response = $this->actingAs($actor, 'api')->postJson($this->url($election));
        $response->assertUnprocessable()->assertJsonPath('message', fn ($message) => str_contains($message, $party->name) && str_contains($message, '8'));
    }

    public function test_repeated_call_returns_existing_group_fields_and_does_not_duplicate(): void
    {
        [$election, $actor] = $this->context();
        $first = $this->actingAs($actor, 'api')->postJson($this->url($election))->assertCreated();
        $second = $this->actingAs($actor, 'api')->postJson($this->url($election));
        $second->assertUnprocessable()->assertJsonPath('group_id', $first->json('data.id'))->assertJsonPath('group_name', $first->json('data.name'));
        $this->assertSame(1, AcademyGroup::where('type', 'student_council')->count());
    }

    public function test_missing_leader_is_refused_readably(): void
    {
        [$election, $actor] = $this->context();
        ElectionPartyMember::where('party_id', $election->results->first()->party_id)->update(['role' => 'member']);
        $this->actingAs($actor, 'api')->postJson($this->url($election))->assertUnprocessable()->assertJsonPath('message', fn ($message) => str_contains($message, 'หัวหน้าพรรค'));
    }

    public function test_view_only_user_cannot_create_council(): void
    {
        [$election] = $this->context();
        $this->actingAs(User::factory()->create(), 'api')->postJson($this->url($election))->assertForbidden();
    }

    private function context(bool $published = true): array
    {
        $actor = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $actor->id]);
        $election = Election::create(['academy_id' => $academy->id, 'title' => 'เลือกตั้งสภา', 'created_by' => $actor->id, 'status' => 'published', 'published_at' => $published ? now() : null, 'allow_abstain' => true]);
        $party = ElectionParty::create(['election_id' => $election->id, 'name' => 'พรรคหนึ่ง', 'status' => 'approved', 'number' => 1, 'applied_by' => $actor->id]);
        $leader = User::factory()->create();
        $member = User::factory()->create();
        ElectionPartyMember::create(['party_id' => $party->id, 'user_id' => $leader->id, 'role' => 'leader']);
        ElectionPartyMember::create(['party_id' => $party->id, 'user_id' => $member->id, 'role' => 'member']);
        ElectionResult::create(['election_id' => $election->id, 'party_id' => $party->id, 'votes' => 8, 'rank' => 1, 'is_winner' => true, 'published_at' => $published ? now() : null]);

        return [$election, $actor, $party, $leader, $member];
    }

    private function url(Election $election): string
    {
        return "/api/academies/{$election->academy_id}/elections/{$election->id}/council";
    }
}
