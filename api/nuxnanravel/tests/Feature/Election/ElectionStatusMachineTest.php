<?php

namespace Tests\Feature\Election;

use App\Models\Academy;
use App\Models\Election;
use App\Models\ElectionParty;
use App\Models\MemberActivityLog;
use App\Models\User;
use App\Services\Election\ElectionService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElectionStatusMachineTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_legal_transition_succeeds(): void
    {
        [$election, $user] = $this->context();
        foreach ([['draft', 'nomination'], ['nomination', 'campaign'], ['campaign', 'voting'], ['voting', 'closed'], ['closed', 'published']] as [$from, $to]) {
            $election->update(['status' => $from, 'voter_roll_locked_at' => $from === 'campaign' ? now() : $election->voter_roll_locked_at]);
            if ($to === 'voting') {
                ElectionParty::create(['election_id' => $election->id, 'number' => 1, 'name' => 'Approved', 'status' => 'approved', 'applied_by' => $user->id]);
            }
            $this->assertSame($to, app(ElectionService::class)->transitionTo($election, $to, $user)->status);
        }
    }

    public function test_reopening_a_closed_ballot_box_is_rejected(): void
    {
        [$election, $user] = $this->context(['status' => 'closed']);
        $this->expectException(DomainException::class);
        app(ElectionService::class)->transitionTo($election, 'voting', $user);
    }

    public function test_published_is_terminal_and_cannot_be_cancelled_or_changed(): void
    {
        [$election, $user] = $this->context(['status' => 'published']);
        foreach (['cancelled', 'draft'] as $to) {
            try {
                app(ElectionService::class)->transitionTo($election, $to, $user);
                $this->fail('published transition unexpectedly succeeded');
            } catch (DomainException $exception) {
                $this->assertStringContainsString('published', $exception->getMessage());
            }
        }
    }

    public function test_cancelled_is_terminal(): void
    {
        [$election, $user] = $this->context(['status' => 'cancelled']);
        $this->expectException(DomainException::class);
        app(ElectionService::class)->transitionTo($election, 'nomination', $user);
    }

    public function test_campaign_to_voting_requires_locked_voter_roll(): void
    {
        [$election, $user] = $this->context(['status' => 'campaign']);
        $this->expectExceptionMessage('ล็อกบัญชีผู้มีสิทธิ์');
        app(ElectionService::class)->transitionTo($election, 'voting', $user);
    }

    public function test_campaign_to_voting_requires_approved_party(): void
    {
        [$election, $user] = $this->context(['status' => 'campaign', 'voter_roll_locked_at' => now()]);
        $this->expectExceptionMessage('พรรคที่ได้รับอนุมัติ');
        app(ElectionService::class)->transitionTo($election, 'voting', $user);
    }

    public function test_successful_status_change_logs_from_and_to(): void
    {
        [$election, $user] = $this->context();
        app(ElectionService::class)->transitionTo($election, 'nomination', $user);
        $log = MemberActivityLog::where('action', MemberActivityLog::ACTION_ELECTION_STATUS_CHANGE)->latest()->first();
        $this->assertSame(['from' => 'draft', 'to' => 'nomination'], array_intersect_key($log->new_values, ['from' => true, 'to' => true]));
    }

    private function context(array $attributes = []): array
    {
        $user = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $user->id]);

        return [Election::create(array_merge(['academy_id' => $academy->id, 'title' => 'Election', 'created_by' => $user->id], $attributes)), $user];
    }
}
