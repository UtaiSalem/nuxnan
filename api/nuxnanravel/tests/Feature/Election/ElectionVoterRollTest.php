<?php

namespace Tests\Feature\Election;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Election;
use App\Models\ElectionParty;
use App\Models\ElectionVoter;
use App\Models\MemberActivityLog;
use App\Models\Student;
use App\Models\User;
use App\Services\Election\ElectionService;
use App\Services\Election\ElectionVoterRollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ElectionVoterRollTest extends TestCase
{
    use RefreshDatabase;

    public function test_locking_snapshots_only_approved_members(): void
    {
        [$a, $actor, $e] = $this->context();
        $this->member($a, ['status' => 2]);
        $this->member($a, ['status' => 3]);
        app(ElectionVoterRollService::class)->lock($e, $actor);
        $this->assertDatabaseHas('election_voters', ['election_id' => $e->id, 'member_code' => null]);
        $this->assertSame(2, ElectionVoter::where('election_id', $e->id)->count());
    }

    public function test_student_and_staff_types_are_snapshotted(): void
    {
        [$a, $actor, $e] = $this->context();
        $student = Student::create(['academy_id' => $a->id, 'student_id' => uniqid('S'), 'first_name_th' => 'Student', 'last_name_th' => 'Test']);
        $this->member($a, ['student_id' => $student->id]);
        $this->member($a);
        app(ElectionVoterRollService::class)->lock($e, $actor);
        $this->assertSame(1, ElectionVoter::where('voter_type', 'student')->count());
        $this->assertSame(2, ElectionVoter::where('voter_type', 'staff')->count());
    }

    public function test_student_without_active_classroom_is_retained_with_null_grade(): void
    {
        [$a, $actor, $e] = $this->context();
        $student = Student::create(['academy_id' => $a->id, 'student_id' => uniqid('S'), 'first_name_th' => 'Student', 'last_name_th' => 'Test']);
        $this->member($a, ['student_id' => $student->id]);
        app(ElectionVoterRollService::class)->lock($e, $actor);
        $this->assertDatabaseHas('election_voters', ['voter_type' => 'student', 'grade_level' => null, 'classroom_name' => null]);
        $this->assertNull(ElectionVoter::where('election_id', $e->id)->value('grade_level'));
    }

    public function test_duplicate_member_rows_collapse_and_counts_match_persisted_voters(): void
    {
        [$a, $actor, $e] = $this->context();
        $student = Student::create(['academy_id' => $a->id, 'student_id' => uniqid('S'), 'first_name_th' => 'Student', 'last_name_th' => 'Test']);
        $user = User::factory()->create();
        $role = AcademyRole::where('academy_id', $a->id)->first();
        AcademyMember::create(['academy_id' => $a->id, 'user_id' => $user->id, 'academy_role_id' => $role->id, 'status' => 2, 'member_code' => 'STAFF']);
        AcademyMember::create(['academy_id' => $a->id, 'user_id' => $user->id, 'academy_role_id' => $role->id, 'status' => 2, 'student_id' => $student->id, 'member_code' => 'STUDENT']);

        $counts = app(ElectionVoterRollService::class)->lock($e, $actor);
        $voter = ElectionVoter::where('election_id', $e->id)->where('user_id', $user->id)->first();

        $this->assertSame(1, ElectionVoter::where('election_id', $e->id)->where('user_id', $user->id)->count());
        $this->assertSame('student', $voter->voter_type);
        $this->assertSame(1, $counts['duplicate_member_rows']);
        $this->assertSame(ElectionVoter::where('election_id', $e->id)->count(), $counts['total']);
        $this->assertSame($counts['duplicate_member_rows'], MemberActivityLog::latest()->first()->new_values['duplicate_member_rows']);
    }

    public function test_display_name_and_member_code_are_copied(): void
    {
        [$a, $actor, $e] = $this->context();
        $user = $this->member($a, ['member_code' => 'M-1']);
        $originalName = $user->name;
        app(ElectionVoterRollService::class)->lock($e, $actor);
        $this->assertDatabaseHas('election_voters', ['user_id' => $user->id, 'display_name' => $user->name, 'member_code' => 'M-1']);
        $user->update(['name' => 'Changed']);
        $this->assertDatabaseHas('election_voters', ['user_id' => $user->id, 'display_name' => $originalName]);
        $this->assertDatabaseMissing('election_voters', ['user_id' => $user->id, 'display_name' => 'Changed']);
    }

    public function test_locking_is_idempotent_and_removes_ineligible_rows(): void
    {
        [$a, $actor, $e] = $this->context();
        $m = $this->member($a);
        app(ElectionVoterRollService::class)->lock($e, $actor);
        app(ElectionVoterRollService::class)->lock($e->fresh(), $actor);
        $this->assertSame(2, ElectionVoter::where('election_id', $e->id)->count());
        AcademyMember::where('user_id', $m->id)->update(['status' => 3]);
        app(ElectionVoterRollService::class)->lock($e->fresh(), $actor);
        $this->assertDatabaseMissing('election_voters', ['election_id' => $e->id, 'user_id' => $m->id]);
    }

    public function test_relocking_voting_is_refused_and_timestamp_is_set(): void
    {
        [$a, $actor, $e] = $this->context(['status' => 'voting']);
        $this->expectException(\DomainException::class);
        app(ElectionVoterRollService::class)->lock($e, $actor);
    }

    public function test_lock_returns_missing_counts_and_logs_them(): void
    {
        [$a, $actor, $e] = $this->context();
        $this->member($a, ['member_code' => null]);
        app(ElectionVoterRollService::class)->lock($e, $actor);
        $this->assertNotNull($e->fresh()->voter_roll_locked_at);
        $log = MemberActivityLog::where('action', MemberActivityLog::ACTION_ELECTION_VOTER_ROLL_LOCK)->latest()->first();
        $this->assertNotNull($log);
        $this->assertArrayHasKey('without_member_code', $log->new_values);
    }

    public function test_missing_member_code_filter_returns_exact_rows(): void
    {
        [$a, $actor, $e] = $this->context();
        $this->member($a, ['member_code' => null]);
        $this->member($a, ['member_code' => 12345]);
        app(ElectionVoterRollService::class)->lock($e, $actor);
        $response = $this->actingAs($actor, 'api')->getJson("/api/academies/$a->id/elections/$e->id/voter-roll?missing=member_code");
        $response->assertOk()->assertJsonCount(1, 'data.data');
    }

    public function test_member_without_user_account_is_skipped_and_reported(): void
    {
        [$a, $actor, $e] = $this->context();
        AcademyMember::create(['academy_id' => $a->id, 'user_id' => null, 'academy_role_id' => AcademyRole::where('academy_id', $a->id)->first()->id, 'status' => 2]);

        $counts = app(ElectionVoterRollService::class)->lock($e, $actor);

        $this->assertSame(1, $counts['skipped_no_user_account']);
        $this->assertSame(1, ElectionVoter::where('election_id', $e->id)->count());
        $this->assertDatabaseHas('member_activity_logs', ['action' => MemberActivityLog::ACTION_ELECTION_VOTER_ROLL_LOCK]);
    }

    public function test_lock_query_count_does_not_scale_with_voter_count(): void
    {
        [$a, $actor, $e] = $this->context();
        for ($i = 0; $i < 40; $i++) {
            $this->member($a);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        app(ElectionVoterRollService::class)->lock($e, $actor);

        $this->assertLessThan(40, count(DB::getQueryLog()));
    }

    public function test_lock_keeps_all_members_with_user_accounts_when_one_is_skipped(): void
    {
        [$a, $actor, $e] = $this->context();
        $included = $this->member($a);
        AcademyMember::create(['academy_id' => $a->id, 'user_id' => null, 'academy_role_id' => AcademyRole::where('academy_id', $a->id)->first()->id, 'status' => 2]);

        app(ElectionVoterRollService::class)->lock($e, $actor);

        $this->assertDatabaseHas('election_voters', ['election_id' => $e->id, 'user_id' => $included->id]);
        $this->assertSame(2, ElectionVoter::where('election_id', $e->id)->count());
    }

    public function test_view_only_can_read_stats_but_cannot_lock(): void
    {
        [$a, $actor, $e] = $this->context([], ['elections.view']);
        $this->actingAs($actor, 'api')->postJson("/api/academies/$a->id/elections/$e->id/voter-roll/lock")->assertForbidden();
        $this->actingAs($actor, 'api')->getJson("/api/academies/$a->id/elections/$e->id/voter-roll/stats")->assertOk();
    }

    public function test_locked_roll_allows_voting_with_approved_party(): void
    {
        [$a, $actor, $e] = $this->context(['status' => 'campaign']);
        app(ElectionVoterRollService::class)->lock($e, $actor);
        $party = ElectionParty::create(['election_id' => $e->id, 'name' => 'P', 'status' => 'approved', 'number' => 1, 'applied_by' => $actor->id]);
        app(ElectionService::class)->transitionTo($e->fresh(), 'voting', $actor);
        $this->assertSame('voting', $e->fresh()->status);
    }

    private function context(array $election = [], array $permissions = ['elections.view', 'elections.manage']): array
    {
        $owner = User::factory()->create();
        $a = Academy::factory()->create(['user_id' => $owner->id]);
        $role = AcademyRole::create(['academy_id' => $a->id, 'name' => uniqid(), 'display_name_th' => 'Test', 'permissions' => $permissions]);
        $actor = User::factory()->create();
        AcademyMember::create(['academy_id' => $a->id, 'user_id' => $actor->id, 'academy_role_id' => $role->id, 'status' => 2, 'member_code' => 'ACTOR']);
        $e = Election::create(array_merge(['academy_id' => $a->id, 'title' => 'Election', 'created_by' => $owner->id, 'status' => 'draft'], $election));

        return [$a, $actor, $e];
    }

    private function member(Academy $a, array $attrs = []): User
    {
        $u = User::factory()->create();
        $role = AcademyRole::where('academy_id', $a->id)->first();
        AcademyMember::create(array_merge(['academy_id' => $a->id, 'user_id' => $u->id, 'academy_role_id' => $role->id, 'status' => 2], $attrs));

        return $u;
    }
}
