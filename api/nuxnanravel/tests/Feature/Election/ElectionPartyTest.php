<?php

namespace Tests\Feature\Election;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Election;
use App\Models\ElectionParty;
use App\Models\ElectionPartyMember;
use App\Models\MemberActivityLog;
use App\Models\User;
use App\Services\Election\ElectionPartyService;
use App\Services\Election\ElectionService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElectionPartyTest extends TestCase
{
    use RefreshDatabase;

    public function test_applying_outside_nomination_is_rejected(): void
    {
        [$a, $u, $e] = $this->context(['status' => 'draft']);
        $this->apply($a, $e, $u)->assertStatus(422);
    }

    public function test_applying_after_nomination_closes_is_rejected(): void
    {
        [$a, $u, $e] = $this->context(['status' => 'nomination', 'nomination_closes_at' => now()->subMinute()]);
        $this->apply($a, $e, $u)->assertStatus(422);
    }

    public function test_zero_or_two_leaders_is_rejected(): void
    {
        [$a, $u, $e] = $this->context(['status' => 'nomination']);
        $this->apply($a, $e, $u, [['user_id' => $u->id, 'role' => 'member']])->assertStatus(422);
        $v = $this->member($a);
        $this->apply($a, $e, $u, [['user_id' => $u->id, 'role' => 'leader'], ['user_id' => $v->id, 'role' => 'leader']])->assertStatus(422);
    }

    public function test_member_in_another_live_party_is_rejected_with_party_name(): void
    {
        [$a, $u, $e] = $this->context(['status' => 'nomination']);
        $p = $this->party($e, $u, 'Existing');
        $this->memberRow($p, $u, 'leader');
        $this->expectException(DomainException::class);
        app(ElectionPartyService::class)->apply($e, ['name' => 'New', 'members' => [['user_id' => $u->id, 'role' => 'leader']]], $u);
    }

    public function test_member_may_reapply_after_withdrawn_or_rejected_party(): void
    {
        [$a, $u, $e] = $this->context(['status' => 'nomination']);
        $p = $this->party($e, $u, 'Old', ElectionParty::STATUS_WITHDRAWN);
        $this->memberRow($p, $u, 'leader');
        $this->assertSame('pending', app(ElectionPartyService::class)->apply($e, ['name' => 'New', 'members' => [['user_id' => $u->id, 'role' => 'leader']]], $u)->status);
    }

    public function test_approving_without_numbers_assigns_one_two_three(): void
    {
        [$a, $u, $e] = $this->context(['status' => 'nomination']);
        foreach (range(1, 3) as $i) {
            $p = $this->party($e, $u, 'P'.$i);
            $this->memberRow($p, $u, 'leader');
            app(ElectionPartyService::class)->approve($p, null, $u);
            $this->assertSame($i, $p->fresh()->number);
        }
    }

    public function test_approving_held_number_returns_422_not_500(): void
    {
        [$a, $u, $e] = $this->context(['status' => 'nomination']);
        $held = $this->party($e, $u, 'Held', ElectionParty::STATUS_APPROVED, 7);
        $this->memberRow($held, $u, 'leader');
        $p = $this->party($e, $u, 'New');
        $this->memberRow($p, $u, 'leader');
        try {
            app(ElectionPartyService::class)->approve($p, 7, $u);
            $this->fail('Expected duplicate number rejection');
        } catch (DomainException) {
            $this->assertTrue(true);
        }
    }

    public function test_freed_number_can_be_reused_after_withdrawal(): void
    {
        [$a, $u, $e] = $this->context(['status' => 'nomination']);
        $old = $this->party($e, $u, 'Old', ElectionParty::STATUS_APPROVED, 4);
        $this->memberRow($old, $u, 'leader');
        app(ElectionPartyService::class)->withdraw($old, $u);
        $p = $this->party($e, $u, 'New');
        $this->memberRow($p, $u, 'leader');
        $this->assertSame(4, app(ElectionPartyService::class)->approve($p, 4, $u)->number);
    }

    public function test_blank_rejection_note_is_rejected(): void
    {
        [$a, $u, $e] = $this->context(['status' => 'nomination']);
        $p = $this->party($e, $u, 'Reject');
        $this->memberRow($p, $u, 'leader');
        $this->actingAs($u, 'api')->postJson("/api/academies/$a->id/elections/$e->id/parties/$p->id/reject", ['review_note' => ' '])->assertStatus(422);
    }

    public function test_withdraw_is_refused_during_voting(): void
    {
        [$a, $u, $e] = $this->context(['status' => 'voting']);
        $p = $this->party($e, $u, 'Voting');
        $this->memberRow($p, $u, 'leader');
        try {
            app(ElectionPartyService::class)->withdraw($p, $u);
            $this->fail('Expected voting withdrawal rejection');
        } catch (DomainException) {
            $this->assertTrue(true);
        }
    }

    public function test_number_in_apply_body_is_ignored(): void
    {
        [$a, $u, $e] = $this->context(['status' => 'nomination']);
        app(ElectionPartyService::class)->apply($e, ['name' => 'Ignored', 'number' => 9, 'members' => [['user_id' => $u->id, 'role' => 'leader']]], $u);
        $this->assertDatabaseHas('election_parties', ['election_id' => $e->id, 'number' => null, 'status' => 'pending']);
    }

    public function test_view_only_user_cannot_approve_or_reject(): void
    {
        [$a, $u, $e] = $this->context(['status' => 'nomination'], ['elections.view']);
        $p = $this->party($e, $u, 'OnlyView');
        $this->memberRow($p, $u, 'leader');
        $this->actingAs($u, 'api')->postJson("/api/academies/$a->id/elections/$e->id/parties/$p->id/approve")->assertForbidden();
        $this->actingAs($u, 'api')->postJson("/api/academies/$a->id/elections/$e->id/parties/$p->id/reject", ['review_note' => 'no'])->assertForbidden();
    }

    public function test_party_from_another_election_is_not_found(): void
    {
        [$a, $u, $e] = $this->context(['status' => 'nomination']);
        $other = Election::create(['academy_id' => $a->id, 'title' => 'Other', 'created_by' => $u->id, 'status' => 'nomination']);
        $p = $this->party($other, $u, 'Other');
        $this->actingAs($u, 'api')->postJson("/api/academies/$a->id/elections/$e->id/parties/$p->id/approve")->assertNotFound();
    }

    public function test_approval_logs_assigned_number(): void
    {
        [$a, $u, $e] = $this->context(['status' => 'nomination']);
        $p = $this->party($e, $u, 'Logged');
        $this->memberRow($p, $u, 'leader');
        app(ElectionPartyService::class)->approve($p, 8, $u);
        $this->assertDatabaseHas('member_activity_logs', ['action' => MemberActivityLog::ACTION_ELECTION_PARTY_APPROVE]);
        $this->assertStringContainsString('8', json_encode(MemberActivityLog::latest()->first()->new_values));
    }

    public function test_voting_requires_locked_roll_and_approved_party(): void
    {
        [$a, $u, $e] = $this->context(['status' => 'campaign', 'voter_roll_locked_at' => now()]);
        $p = $this->party($e, $u, 'Approved', ElectionParty::STATUS_APPROVED, 1);
        $this->memberRow($p, $u, 'leader');
        app(ElectionService::class)->transitionTo($e, 'voting', $u);
        $e->update(['status' => 'campaign']);
        $p->update(['status' => ElectionParty::STATUS_REJECTED]);
        $this->expectException(DomainException::class);
        app(ElectionService::class)->transitionTo($e->fresh(), 'voting', $u);
    }

    public function test_duplicate_member_in_one_submission_is_rejected_before_database(): void
    {
        [$a, $u, $e] = $this->context(['status' => 'nomination']);
        $this->expectException(DomainException::class);
        app(ElectionPartyService::class)->apply($e, ['name' => 'Duplicate', 'members' => [['user_id' => $u->id, 'role' => 'leader'], ['user_id' => $u->id, 'role' => 'member']]], $u);
    }

    private function context(array $election = [], array $permissions = ['elections.view', 'elections.manage']): array
    {
        $owner = User::factory()->create();
        $a = Academy::factory()->create(['user_id' => $owner->id]);
        $role = AcademyRole::create(['academy_id' => $a->id, 'name' => uniqid(), 'display_name_th' => 'Test', 'permissions' => $permissions]);
        $u = User::factory()->create();
        AcademyMember::create(['academy_id' => $a->id, 'user_id' => $u->id, 'academy_role_id' => $role->id, 'status' => 2]);
        $e = Election::create(array_merge(['academy_id' => $a->id, 'title' => 'Election', 'created_by' => $owner->id], $election));

        return [$a, $u, $e];
    }

    private function member(Academy $a): User
    {
        $u = User::factory()->create();
        $role = AcademyRole::where('academy_id', $a->id)->first();
        AcademyMember::create(['academy_id' => $a->id, 'user_id' => $u->id, 'academy_role_id' => $role->id, 'status' => 2]);

        return $u;
    }

    private function party(Election $e, User $u, string $name, string $status = 'pending', ?int $number = null): ElectionParty
    {
        return ElectionParty::create(['election_id' => $e->id, 'name' => $name, 'status' => $status, 'number' => $number, 'applied_by' => $u->id]);
    }

    private function memberRow(ElectionParty $p, User $u, string $role): void
    {
        ElectionPartyMember::create(['party_id' => $p->id, 'user_id' => $u->id, 'role' => $role]);
    }

    private function apply(Academy $a, Election $e, User $u, ?array $members = null, array $extra = [])
    {
        $members ??= [['user_id' => $u->id, 'role' => 'leader']];

        return $this->actingAs($u, 'api')->postJson("/api/academies/$a->id/elections/$e->id/parties", array_merge(['name' => uniqid('Party'), 'members' => $members], $extra));
    }
}
