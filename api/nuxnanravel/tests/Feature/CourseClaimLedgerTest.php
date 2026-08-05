<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseDonateClaim;
use App\Models\CourseMember;
use App\Models\CoursePointAccount;
use App\Models\User;
use App\Services\CourseClaimService;
use App\Services\CourseDonateService;
use App\Services\CoursePointWithdrawalService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CourseClaimLedgerTest extends TestCase
{
    use RefreshDatabase;

    private function setupClaimTest(): array
    {
        $owner = User::factory()->create();
        $donor = User::factory()->create(['pp' => 1000]);
        $claimer = User::factory()->create(['pp' => 0, 'suggester_code' => null]);
        $platform = User::factory()->create([
            'personal_code' => config('economy.platform_personal_code', '99999999'),
            'pp' => 0,
        ]);
        $course = Course::factory()->create(['user_id' => $owner->id, 'donation_enabled' => true]);
        CourseMember::create(['course_id' => $course->id, 'user_id' => $claimer->id]);

        return [$owner, $donor, $claimer, $platform, $course];
    }

    public function test_claim_debits_the_course_fund_instead_of_minting_points(): void
    {
        [$owner, $donor, $claimer, $platform, $course] = $this->setupClaimTest();

        $donation = app(CourseDonateService::class)->createPointDonation($donor, $course, 270, [], 'donate-key-1');

        $account = CoursePointAccount::where('course_id', $course->id)->first();
        $this->assertNotNull($account);
        $this->assertEquals(270, $account->balance);
        $this->assertEquals(270, $donation->remaining_points);

        $claim = app(CourseClaimService::class)->claimSpecific($claimer, $course, $donation);

        $account = $account->fresh();
        $donation = $donation->fresh();
        $claimer = $claimer->fresh();
        $platform = $platform->fresh();

        $this->assertEquals(50, $account->balance);

        // `amount` is an unsigned column in MySQL, so a debit must be stored positive
        // with the direction carried by the type and the before/after balances.
        // SQLite (used by this suite) would silently accept a negative and let it
        // blow up only in production, so assert the convention explicitly.
        $debitRow = DB::table('course_point_transactions')
            ->where('type', 'student_claim')->latest('id')->first();
        $this->assertNotNull($debitRow);
        $this->assertGreaterThan(0, $debitRow->amount);
        $this->assertLessThan($debitRow->balance_before, $debitRow->balance_after);
        $this->assertEquals(0, $donation->remaining_points);
        $this->assertEquals(210, $claimer->pp);
        $this->assertEquals(10, $platform->pp);

        $pointsGrantedToUsers = $claimer->pp + $platform->pp;
        $netFundDecrease = 270 - $account->balance;
        $this->assertEquals($pointsGrantedToUsers, $netFundDecrease);
    }

    public function test_claim_is_rejected_when_the_fund_cannot_cover_it(): void
    {
        [$owner, $donor, $claimer, $platform, $course] = $this->setupClaimTest();

        $donation = app(CourseDonateService::class)->createPointDonation($donor, $course, 270, [], 'donate-key-2');

        CoursePointAccount::where('course_id', $course->id)->update(['balance' => 0]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('insufficient_pool');

        try {
            app(CourseClaimService::class)->claimSpecific($claimer, $course, $donation);
        } finally {
            $this->assertEquals(0, $claimer->fresh()->pp);
            $this->assertEquals(0, CourseDonateClaim::count());
            $this->assertEquals(270, $donation->fresh()->remaining_points);
        }
    }

    public function test_donated_points_are_reserved_and_cannot_be_withdrawn(): void
    {
        [$owner, $donor, $claimer, $platform, $course] = $this->setupClaimTest();
        $donor->update(['pp' => 35000]);

        $donation = app(CourseDonateService::class)->createPointDonation($donor, $course, 30000, [], 'donate-key-3');

        $account = CoursePointAccount::where('course_id', $course->id)->first();
        $this->assertNotNull($account);
        $this->assertEquals(30000, $account->balance);
        $this->assertEquals(30000, $account->reserved_balance);
        $this->assertEquals(0, $account->available_balance);

        $this->expectException(DomainException::class);
        app(CoursePointWithdrawalService::class)->request($owner, $course, 24000, null, null);
    }

    public function test_non_member_cannot_claim(): void
    {
        [$owner, $donor, $claimer, $platform, $course] = $this->setupClaimTest();

        $donation = app(CourseDonateService::class)->createPointDonation($donor, $course, 270, [], 'donate-key-4');

        $nonMember = User::factory()->create(['pp' => 0]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('not_a_course_member');

        try {
            app(CourseClaimService::class)->claimSpecific($nonMember, $course, $donation);
        } finally {
            $this->assertEquals(0, $nonMember->fresh()->pp);
            $this->assertEquals(0, CourseDonateClaim::count());
            $this->assertEquals(270, $donation->fresh()->remaining_points);
        }
    }

    public function test_non_member_cannot_view_claim_history(): void
    {
        [$owner, $donor, $claimer, $platform, $course] = $this->setupClaimTest();
        $nonMember = User::factory()->create();

        $this->actingAs($nonMember, 'api')
            ->getJson('/api/courses/'.$course->id.'/donations/claims')
            ->assertForbidden()
            ->assertJsonPath('code', 'not_a_course_member');
    }

    public function test_member_sees_newest_first_paginated_claim_history_and_summary(): void
    {
        [$owner, $donor, $claimer, $platform, $course] = $this->setupClaimTest();
        $other = User::factory()->create(['pp' => 0, 'suggester_code' => null]);
        CourseMember::create(['course_id' => $course->id, 'user_id' => $other->id]);
        $firstDonation = app(CourseDonateService::class)->createPointDonation($donor, $course, 270, ['donor_display_name' => 'Donor'], 'ledger-history-1');
        $first = app(CourseClaimService::class)->claimSpecific($claimer, $course, $firstDonation);
        $second = CourseDonateClaim::create([
            'course_id' => $course->id, 'course_donate_id' => $firstDonation->id, 'claimer_id' => $other->id,
            'amount_claimer' => 8, 'amount_course' => 1, 'amount_suggester' => 0, 'amount_platform' => 0,
            'claimer_transaction_id' => $first->claimer_transaction_id,
            'course_transaction_id' => $first->course_transaction_id,
            'platform_transaction_id' => $first->platform_transaction_id,
            'claimed_at' => now()->addSecond(),
        ]);

        $this->actingAs($claimer, 'api')
            ->getJson('/api/courses/'.$course->id.'/donations/claims?per_page=1')
            ->assertOk()
            ->assertJsonPath('claims.0.id', $second->id)
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.last_page', 2)
            ->assertJsonPath('pagination.per_page', 1)
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonPath('pagination.has_more', true)
            ->assertJsonPath('summary.total_claims', 2)
            ->assertJsonPath('summary.my_claims_count', 1)
            ->assertJsonPath('summary.my_points_total', 210)
            ->assertJsonPath('claims.0.is_mine', false);
        $this->assertTrue($first->fresh()->claimer_id === $claimer->id);
    }

    public function test_claim_history_masks_anonymous_donor_and_marks_my_claim(): void
    {
        [$owner, $donor, $claimer, $platform, $course] = $this->setupClaimTest();
        $donation = app(CourseDonateService::class)->createPointDonation($donor, $course, 270, ['anonymous' => true, 'donor_display_name' => 'Secret'], 'ledger-history-2');
        $claim = app(CourseClaimService::class)->claimSpecific($claimer, $course, $donation);

        $this->actingAs($claimer, 'api')
            ->getJson('/api/courses/'.$course->id.'/donations/claims')
            ->assertOk()
            ->assertJsonPath('claims.0.donor_display_name', 'ผู้ไม่ประสงค์ออกนาม')
            ->assertJsonPath('claims.0.is_mine', true)
            ->assertJsonPath('summary.my_points_total', $claim->amount_claimer);
    }
}
