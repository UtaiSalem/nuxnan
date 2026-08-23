<?php

namespace Tests\Feature\Election;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Election;
use App\Models\ElectionParty;
use App\Models\ElectionStation;
use App\Models\ElectionVoter;
use App\Models\ElectionVoterReceipt;
use App\Models\Student;
use App\Models\StudentAcademicInfo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ElectionMemberFacingTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidates_returns_only_the_public_six_fields(): void
    {
        [$academy, $actor, $election] = $this->context(['status' => 'voting']);
        $candidate = $this->member($academy, 'Candidate');
        ElectionVoter::create(['election_id' => $election->id, 'user_id' => $candidate->id, 'display_name' => 'Candidate', 'voter_type' => 'staff']);

        $response = $this->actingAs($actor, 'api')->getJson("/api/academies/{$academy->id}/elections/{$election->id}/candidates?q=Can");
        $response->assertOk()->assertJsonStructure(['data' => [['user_id', 'display_name', 'voter_type', 'grade_level', 'classroom_name', 'member_code']]]);
        foreach ($response->json('data') as $row) {
            $this->assertSame(['user_id', 'display_name', 'voter_type', 'grade_level', 'classroom_name', 'member_code'], array_keys($row));
        }
    }

    public function test_candidates_requires_at_least_two_characters(): void
    {
        [$academy, $actor, $election] = $this->context(['status' => 'voting']);
        $response = $this->actingAs($actor, 'api')->getJson("/api/academies/{$academy->id}/elections/{$election->id}/candidates?q=x");
        $response->assertStatus(422)->assertJsonPath('message', fn ($message) => is_string($message) && preg_match('/[^\\x00-\\x7F]/', $message) === 1);
    }

    public function test_candidates_are_filtered_by_election_education_level(): void
    {
        [$academy, $actor, $election] = $this->context(['status' => 'voting', 'education_level' => 2]);
        [$primary, $secondary] = [$this->studentMember($academy, 'Primary', 1), $this->studentMember($academy, 'Secondary', 2)];
        foreach ([$primary, $secondary] as $user) {
            ElectionVoter::create(['election_id' => $election->id, 'user_id' => $user->id, 'display_name' => $user->name, 'voter_type' => 'student']);
        }
        $names = $this->actingAs($actor, 'api')->getJson("/api/academies/{$academy->id}/elections/{$election->id}/candidates?q=ary")->assertOk()->json('data');
        $displayNames = collect($names)->pluck('display_name');
        $this->assertTrue($displayNames->contains('Secondary'));
        $this->assertFalse($displayNames->contains('Primary'));
    }

    public function test_parties_mine_returns_party_for_applicant_and_member_only(): void
    {
        [$academy, $actor, $election] = $this->context(['status' => 'nomination']);
        $teammate = $this->member($academy, 'Teammate');
        $other = $this->member($academy, 'Other');
        $party = ElectionParty::create(['election_id' => $election->id, 'name' => 'Team', 'status' => 'pending', 'applied_by' => $actor->id]);
        $party->members()->create(['user_id' => $actor->id, 'role' => 'leader']);
        $party->members()->create(['user_id' => $teammate->id, 'role' => 'member']);
        $url = "/api/academies/{$academy->id}/elections/{$election->id}/parties/mine";
        $this->actingAs($actor, 'api')->getJson($url)->assertOk()->assertJsonPath('data.id', $party->id)->assertJsonStructure(['data' => ['members' => [['user_id', 'role', 'user' => ['id', 'name']]]]]);
        $this->actingAs($teammate, 'api')->getJson($url)->assertOk()->assertJsonPath('data.id', $party->id);
        $this->actingAs($other, 'api')->getJson($url)->assertOk()->assertJsonPath('data', null);
    }

    public function test_cross_level_party_application_is_rejected(): void
    {
        [$academy, $actor, $election] = $this->context(['status' => 'nomination', 'education_level' => 2]);
        $student = $this->studentMember($academy, 'Primary Applicant', 1);
        $response = $this->actingAs($student, 'api')->postJson("/api/academies/{$academy->id}/elections/{$election->id}/parties", ['name' => 'Nope', 'members' => [['user_id' => $student->id, 'role' => 'leader']]]);
        $response->assertStatus(422)->assertJsonPath('message', fn ($message) => str_contains($message, 'ไม่มีสิทธิ์'));
    }

    public function test_turnout_counts_voted_issued_and_total(): void
    {
        [$academy, $actor, $election] = $this->context(['status' => 'voting']);
        $station = ElectionStation::create(['election_id' => $election->id, 'name' => 'Station']);
        $voters = collect(range(1, 4))->map(fn ($i) => ElectionVoter::create(['election_id' => $election->id, 'user_id' => User::factory()->create()->id, 'display_name' => "Voter $i", 'voter_type' => 'staff']));
        foreach ($voters->take(2) as $i => $voter) {
            ElectionVoterReceipt::create(['election_id' => $election->id, 'election_voter_id' => $voter->id, 'user_id' => $voter->user_id, 'station_id' => $station->id, 'issued_by' => $actor->id, 'status' => $i ? 'cast' : 'issued', 'issued_at' => now()]);
        }
        $this->actingAs($actor, 'api')->getJson("/api/academies/{$academy->id}/elections/{$election->id}/turnout")->assertOk()->assertJsonPath('data.voted', 1)->assertJsonPath('data.issued', 2)->assertJsonPath('data.total', 4)->assertJsonPath('data.percentage', 25)->assertJsonStructure(['data' => ['by_station' => [['station_name']]]]);
    }

    public function test_index_hides_draft_for_viewer_but_owner_sees_it(): void
    {
        [$academy, , $draft] = $this->context(['status' => 'draft']);
        $viewerRole = AcademyRole::create(['academy_id' => $academy->id, 'name' => uniqid(), 'display_name_th' => 'View only', 'permissions' => ['elections.view']]);
        $viewer = User::factory()->create(['name' => 'Viewer']);
        AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $viewer->id, 'academy_role_id' => $viewerRole->id, 'status' => 2, 'member_code' => 'VIEWER']);
        $live = Election::create(['academy_id' => $academy->id, 'title' => 'Live', 'created_by' => $academy->user_id, 'status' => 'voting']);
        $url = "/api/academies/{$academy->id}/elections";
        $this->actingAs($viewer, 'api')->getJson($url)->assertOk()->assertJsonMissing(['id' => $draft->id])->assertJsonFragment(['id' => $live->id]);
        $this->actingAs(User::find($academy->user_id), 'api')->getJson($url)->assertOk()->assertJsonFragment(['id' => $draft->id]);
    }

    public function test_post_method_spoofing_updates_party_logo(): void
    {
        [$academy, $actor, $election] = $this->context(['status' => 'nomination']);
        $party = ElectionParty::create(['election_id' => $election->id, 'name' => 'Old', 'status' => 'pending', 'applied_by' => $actor->id]);
        $party->members()->create(['user_id' => $actor->id, 'role' => 'leader']);
        $response = $this->actingAs($actor, 'api')->post("/api/academies/{$academy->id}/elections/{$election->id}/parties/{$party->id}", ['_method' => 'PUT', 'name' => 'New', 'logo' => UploadedFile::fake()->image('logo.png')]);
        $response->assertOk();
        $this->assertNotNull($party->fresh()->logo_path);
    }

    private function context(array $election = []): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $role = AcademyRole::create(['academy_id' => $academy->id, 'name' => uniqid(), 'display_name_th' => 'Test', 'permissions' => ['elections.view', 'elections.manage']]);
        $actor = User::factory()->create(['name' => 'Actor']);
        AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $actor->id, 'academy_role_id' => $role->id, 'status' => 2, 'member_code' => 'ACTOR']);
        $election = Election::create(array_merge(['academy_id' => $academy->id, 'title' => 'Election', 'created_by' => $owner->id, 'status' => 'voting'], $election));

        return [$academy, $actor, $election, $role];
    }

    private function member(Academy $academy, string $name): User
    {
        $user = User::factory()->create(['name' => $name]);
        AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $user->id, 'academy_role_id' => AcademyRole::where('academy_id', $academy->id)->first()->id, 'status' => 2, 'member_code' => strtoupper($name)]);

        return $user;
    }

    private function studentMember(Academy $academy, string $name, int $level): User
    {
        $user = $this->member($academy, $name);
        $student = Student::create(['academy_id' => $academy->id, 'user_id' => $user->id, 'student_id' => uniqid('S'), 'first_name_th' => $name, 'last_name_th' => 'Student', 'status' => 'active']);
        $member = AcademyMember::where(['academy_id' => $academy->id, 'user_id' => $user->id])->firstOrFail();
        $member->update(['student_id' => $student->id]);
        StudentAcademicInfo::create(['academy_id' => $academy->id, 'student_id' => $student->id, 'education_level' => $level, 'is_current' => true]);

        return $user;
    }
}
