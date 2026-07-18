<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyPointAccount;
use App\Models\User;
use App\Services\AcademyDonateService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyDonateTest extends TestCase
{
    use RefreshDatabase;

    private function setupAcademy(int $ownerPoints = 0): array
    {
        $owner = User::factory()->create(['pp' => $ownerPoints]);
        $donor = User::factory()->create(['pp' => 1000]);

        return [$owner, $donor, Academy::factory()->create(['user_id' => $owner->id, 'donation_enabled' => true])];
    }

    public function test_point_donation_deducts_donor_and_credits_academy(): void
    {
        [$owner, $donor, $academy] = $this->setupAcademy();
        $donation = app(AcademyDonateService::class)->createPointDonation($donor, $academy, 250, [], 'academy-1');
        $this->assertEquals(750, $donor->fresh()->pp);
        $this->assertSame(250, AcademyPointAccount::first()->balance);
        $this->assertSame('completed', $donation->status);
    }

    public function test_point_donation_is_idempotent_on_replay(): void
    {
        [, $donor, $academy] = $this->setupAcademy();
        $service = app(AcademyDonateService::class);
        $first = $service->createPointDonation($donor, $academy, 100, [], 'same');
        $second = $service->createPointDonation($donor->fresh(), $academy, 100, [], 'same');
        $this->assertSame($first->id, $second->id);
        $this->assertEquals(900, $donor->fresh()->pp);
        $this->assertSame(100, AcademyPointAccount::first()->balance);
    }

    public function test_self_donation_by_academy_owner_blocked(): void
    {
        [$owner,, $academy] = $this->setupAcademy();
        $this->expectException(DomainException::class);
        app(AcademyDonateService::class)->createPointDonation($owner, $academy, 1, [], null);
    }

    public function test_insufficient_pp_returns_error(): void
    {
        [, $donor, $academy] = $this->setupAcademy();
        $this->expectException(DomainException::class);
        app(AcademyDonateService::class)->createPointDonation($donor, $academy, 1001, [], null);
    }

    public function test_cash_donation_creates_pending_row_and_does_not_credit_academy(): void
    {
        [, $donor, $academy] = $this->setupAcademy();
        $donation = app(AcademyDonateService::class)->createCashDonation($donor, $academy, 50, ['payment_method' => 'bank'], null, null);
        $this->assertSame('pending', $donation->status);
        $this->assertNull(AcademyPointAccount::first());
    }

    public function test_admin_approve_credits_academy(): void
    {
        [$owner, $donor, $academy] = $this->setupAcademy();
        $admin = User::factory()->create();
        $donation = app(AcademyDonateService::class)->createCashDonation($donor, $academy, 50, ['payment_method' => 'bank'], null, null);
        app(AcademyDonateService::class)->approve($donation, $admin, 'ok');
        $this->assertSame(50, AcademyPointAccount::first()->balance);
    }

    public function test_admin_reject_does_not_credit_academy(): void
    {
        [, $donor, $academy] = $this->setupAcademy();
        $admin = User::factory()->create();
        $donation = app(AcademyDonateService::class)->createCashDonation($donor, $academy, 50, ['payment_method' => 'bank'], null, null);
        app(AcademyDonateService::class)->reject($donation, $admin, 'bad slip');
        $this->assertSame('rejected', $donation->fresh()->status);
        $this->assertNull(AcademyPointAccount::first());
    }

    public function test_admin_cannot_approve_donation_to_own_academy(): void
    {
        [$owner, $donor, $academy] = $this->setupAcademy();
        $donation = app(AcademyDonateService::class)->createCashDonation($donor, $academy, 50, ['payment_method' => 'bank'], null, null);
        $this->expectException(DomainException::class);
        app(AcademyDonateService::class)->approve($donation, $owner, null);
    }

    public function test_donation_disabled_globally_blocks(): void
    {
        config(['platform.course_donation.enabled' => false]);
        [, $donor, $academy] = $this->setupAcademy();
        $academy->update(['donation_enabled' => null]);
        $this->expectException(DomainException::class);
        app(AcademyDonateService::class)->createPointDonation($donor, $academy, 1, [], null);
    }
}
