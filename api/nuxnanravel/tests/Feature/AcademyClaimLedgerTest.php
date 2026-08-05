<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyDonateClaim;
use App\Models\AcademyPointAccount;
use App\Models\User;
use App\Services\AcademyClaimService;
use App\Services\AcademyDonateService;
use App\Services\AcademyPointWithdrawalService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AcademyClaimLedgerTest extends TestCase
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
        $academy = Academy::factory()->create(['user_id' => $owner->id, 'donation_enabled' => true]);

        return [$owner, $donor, $claimer, $platform, $academy];
    }

    public function test_claim_debits_the_academy_fund_instead_of_minting_points(): void
    {
        [$owner, $donor, $claimer, $platform, $academy] = $this->setupClaimTest();

        $donation = app(AcademyDonateService::class)->createPointDonation($donor, $academy, 270, [], 'donate-key-1');

        $account = AcademyPointAccount::where('academy_id', $academy->id)->first();
        $this->assertNotNull($account);
        $this->assertEquals(270, $account->balance);
        $this->assertEquals(270, $donation->remaining_points);

        $claim = app(AcademyClaimService::class)->claimSpecific($claimer, $academy, $donation);

        $account = $account->fresh();
        $donation = $donation->fresh();
        $claimer = $claimer->fresh();
        $platform = $platform->fresh();

        $this->assertEquals(50, $account->balance);

        // `amount` is an unsigned column in MySQL, so a debit must be stored positive
        // with the direction carried by the type and the before/after balances.
        // SQLite (used by this suite) would silently accept a negative and let it
        // blow up only in production, so assert the convention explicitly.
        $debitRow = DB::table('academy_point_transactions')
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
        [$owner, $donor, $claimer, $platform, $academy] = $this->setupClaimTest();

        $donation = app(AcademyDonateService::class)->createPointDonation($donor, $academy, 270, [], 'donate-key-2');

        AcademyPointAccount::where('academy_id', $academy->id)->update(['balance' => 0]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('insufficient_pool');

        try {
            app(AcademyClaimService::class)->claimSpecific($claimer, $academy, $donation);
        } finally {
            $this->assertEquals(0, $claimer->fresh()->pp);
            $this->assertEquals(0, AcademyDonateClaim::count());
            $this->assertEquals(270, $donation->fresh()->remaining_points);
        }
    }

    public function test_donated_points_are_reserved_and_cannot_be_withdrawn(): void
    {
        [$owner, $donor, $claimer, $platform, $academy] = $this->setupClaimTest();
        $donor->update(['pp' => 35000]);

        $donation = app(AcademyDonateService::class)->createPointDonation($donor, $academy, 30000, [], 'donate-key-3');

        $account = AcademyPointAccount::where('academy_id', $academy->id)->first();
        $this->assertNotNull($account);
        $this->assertEquals(30000, $account->balance);
        $this->assertEquals(30000, $account->reserved_balance);
        $this->assertEquals(0, $account->available_balance);

        $this->expectException(DomainException::class);
        app(AcademyPointWithdrawalService::class)->request($owner, $academy, 24000, null, null);
    }

    public function test_claiming_releases_the_reservation(): void
    {
        [$owner, $donor, $claimer, $platform, $academy] = $this->setupClaimTest();

        $donation = app(AcademyDonateService::class)->createPointDonation($donor, $academy, 270, [], 'donate-key-4');

        $account = AcademyPointAccount::where('academy_id', $academy->id)->first();
        $this->assertEquals(270, $account->reserved_balance);

        app(AcademyClaimService::class)->claimSpecific($claimer, $academy, $donation);

        $account = $account->fresh();
        $this->assertEquals(50, $account->balance);
        $this->assertEquals(0, $account->reserved_balance);
        $this->assertEquals(50, $account->available_balance);
    }
}
