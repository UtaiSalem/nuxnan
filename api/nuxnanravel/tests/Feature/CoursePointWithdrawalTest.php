<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CoursePointAccount;
use App\Models\CoursePointTransaction;
use App\Models\CoursePointWithdrawalRequest;
use App\Models\PlearndAdmin;
use App\Models\User;
use App\Services\CoursePointWithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoursePointWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    private function makeSetup(int $balance = 100000): array
    {
        $owner = User::factory()->create(['email_verified_at' => now(), 'pp' => 0]);
        $course = Course::factory()->create(['user_id' => $owner->id]);
        $account = CoursePointAccount::create([
            'course_id' => $course->id,
            'balance' => $balance,
            'reserved_balance' => 0,
        ]);

        return compact('owner', 'course', 'account');
    }

    private function makeAdmin(): User
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        PlearndAdmin::create(['user_id' => $u->id]);

        return $u;
    }

    private function svc(): CoursePointWithdrawalService
    {
        return app(CoursePointWithdrawalService::class);
    }

    public function test_request_creates_pending_and_reserves_balance(): void
    {
        ['owner' => $o, 'course' => $c, 'account' => $a] = $this->makeSetup();
        $r = $this->svc()->request($o, $c, 25000, 'buy books', null);
        $this->assertSame(CoursePointWithdrawalRequest::STATUS_PENDING, $r->status);
        $this->assertSame(25000, (int) $a->fresh()->reserved_balance);
        $this->assertSame(1, CoursePointTransaction::where('type', 'withdrawal_reserve')->count());
    }

    public function test_request_below_minimum_throws(): void
    {
        ['owner' => $o, 'course' => $c] = $this->makeSetup();
        $this->expectException(\DomainException::class);
        $this->svc()->request($o, $c, 100, null, null);
    }

    public function test_request_by_non_owner_throws(): void
    {
        ['course' => $c] = $this->makeSetup();
        $other = User::factory()->create(['email_verified_at' => now()]);
        $this->expectException(\DomainException::class);
        $this->svc()->request($other, $c, 25000, null, null);
    }

    public function test_review_pending_to_reviewing(): void
    {
        ['owner' => $o, 'course' => $c] = $this->makeSetup();
        $r = $this->svc()->request($o, $c, 25000, null, null);
        $admin = $this->makeAdmin();
        $r2 = $this->svc()->review($r, $admin);
        $this->assertSame('reviewing', $r2->status);
        $this->assertSame($admin->id, (int) $r2->reviewed_by);
    }

    public function test_reviewer_cannot_be_requester(): void
    {
        ['owner' => $o, 'course' => $c] = $this->makeSetup();
        PlearndAdmin::create(['user_id' => $o->id]);
        $r = $this->svc()->request($o, $c, 25000, null, null);
        $this->expectException(\DomainException::class);
        $this->svc()->review($r, $o);
    }

    public function test_approve_above_threshold_requires_different_approver(): void
    {
        ['owner' => $o, 'course' => $c] = $this->makeSetup(balance: 200000);
        config(['wallet.course_withdraw.maker_checker_threshold' => 5000]);
        $r = $this->svc()->request($o, $c, 30000, null, null);
        $admin1 = $this->makeAdmin();
        $this->svc()->review($r->fresh(), $admin1);
        $this->expectException(\DomainException::class);
        $this->svc()->approve($r->fresh(), $admin1);
    }

    public function test_reject_releases_reserve(): void
    {
        ['owner' => $o, 'course' => $c, 'account' => $a] = $this->makeSetup();
        $r = $this->svc()->request($o, $c, 25000, null, null);
        $admin = $this->makeAdmin();
        $this->svc()->reject($r->fresh(), $admin, 'not enough proof');
        $this->assertSame(0, (int) $a->fresh()->reserved_balance);
        $this->assertSame(1, CoursePointTransaction::where('type', 'withdrawal_release')->count());
    }

    public function test_mark_paid_debits_balance_and_credits_requester_pp(): void
    {
        config(['wallet.course_withdraw.maker_checker_threshold' => 5000]);
        ['owner' => $o, 'course' => $c, 'account' => $a] = $this->makeSetup(balance: 200000);
        $r = $this->svc()->request($o, $c, 30000, null, null);
        $reviewer = $this->makeAdmin();
        $approver = $this->makeAdmin();
        $payer = $this->makeAdmin();
        $this->svc()->review($r->fresh(), $reviewer);
        $this->svc()->approve($r->fresh(), $approver);
        $this->svc()->markPaid($r->fresh(), $payer, 'REF-1', []);
        $this->assertSame(200000 - 30000, (int) $a->fresh()->balance);
        $this->assertSame(0, (int) $a->fresh()->reserved_balance);
        $this->assertSame(30000, (int) $o->fresh()->pp);
        $this->assertSame(1, CoursePointTransaction::where('type', 'withdrawal_paid')->count());
    }

    public function test_cancel_pending_releases_reserve(): void
    {
        ['owner' => $o, 'course' => $c, 'account' => $a] = $this->makeSetup();
        $r = $this->svc()->request($o, $c, 25000, null, null);
        $this->svc()->cancel($r->fresh(), $o);
        $this->assertSame(0, (int) $a->fresh()->reserved_balance);
        $this->assertSame('cancelled', $r->fresh()->status);
    }

    public function test_cancel_after_approval_throws(): void
    {
        config(['wallet.course_withdraw.maker_checker_threshold' => 5000]);
        ['owner' => $o, 'course' => $c] = $this->makeSetup(balance: 200000);
        $r = $this->svc()->request($o, $c, 30000, null, null);
        $this->svc()->review($r->fresh(), $this->makeAdmin());
        $this->svc()->approve($r->fresh(), $this->makeAdmin());
        $this->expectException(\DomainException::class);
        $this->svc()->cancel($r->fresh(), $o);
    }
}
