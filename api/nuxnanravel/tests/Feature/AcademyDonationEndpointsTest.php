<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyPointAccount;
use App\Models\Role;
use App\Models\User;
use App\Services\AcademyDonateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AcademyDonationEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private function makeAcademy(): array
    {
        Role::firstOrCreate(['name' => 'SUPER_ADMIN']);
        $owner = User::factory()->create();
        $donor = User::factory()->create(['pp' => 1000]);

        return [$owner, $donor, Academy::factory()->create(['user_id' => $owner->id, 'donation_enabled' => true])];
    }

    public function test_authenticated_user_can_donate_points(): void
    {
        [, $donor, $academy] = $this->makeAcademy();
        $response = $this->actingAs($donor, 'api')->postJson("/api/academies/{$academy->id}/donations/points", ['points_amount' => 100]);
        $response->assertCreated()->assertJsonPath('data.donation_type', 'point');
        $this->assertEquals(900, $donor->fresh()->pp);
    }

    public function test_cash_donation_accepts_anonymous_sent_as_multipart_string(): void
    {
        [, $donor, $academy] = $this->makeAcademy();
        Storage::fake('local');

        // Multipart form data serializes booleans as the strings "true"/"false".
        $response = $this->actingAs($donor, 'api')->post("/api/academies/{$academy->id}/donations/cash", [
            'cash_amount' => 100,
            'payment_method' => 'bank_transfer',
            'anonymous' => 'true',
            'slip' => UploadedFile::fake()->image('slip.jpg'),
        ]);

        $response->assertSuccessful()->assertJsonPath('data.anonymous', true);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        [, , $academy] = $this->makeAcademy();
        $this->postJson("/api/academies/{$academy->id}/donations/points", ['points_amount' => 1])->assertUnauthorized();
    }

    public function test_donor_cannot_donate_to_own_academy(): void
    {
        [$owner, , $academy] = $this->makeAcademy();
        $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/donations/points", ['points_amount' => 1])->assertForbidden();
    }

    public function test_amount_below_min_returns_422(): void
    {
        [, $donor, $academy] = $this->makeAcademy();
        $this->actingAs($donor, 'api')->postJson("/api/academies/{$academy->id}/donations/points", ['points_amount' => 0])->assertUnprocessable();
    }

    public function test_idempotent_replay_returns_same_donation(): void
    {
        [, $donor, $academy] = $this->makeAcademy();
        $first = $this->actingAs($donor, 'api')->withHeader('Idempotency-Key', 'academy-test-key')->postJson("/api/academies/{$academy->id}/donations/points", ['points_amount' => 100]);
        $second = $this->actingAs($donor->fresh(), 'api')->withHeader('Idempotency-Key', 'academy-test-key')->postJson("/api/academies/{$academy->id}/donations/points", ['points_amount' => 100]);
        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertEquals(900, $donor->fresh()->pp);
    }

    public function test_admin_can_list_and_show(): void
    {
        [, $donor, $academy] = $this->makeAcademy();
        $donation = app(AcademyDonateService::class)->createCashDonation($donor, $academy, 50, ['payment_method' => 'bank'], null, null);
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');
        $this->actingAs($admin, 'api')->getJson('/api/plearnd-admin/academy-donations')->assertOk();
        $this->actingAs($admin, 'api')->getJson("/api/plearnd-admin/academy-donations/{$donation->id}")->assertOk()->assertJsonPath('data.id', $donation->id);
    }

    public function test_admin_approve_cash_credits_academy_balance(): void
    {
        [, $donor, $academy] = $this->makeAcademy();
        $donation = app(AcademyDonateService::class)->createCashDonation($donor, $academy, 50, ['payment_method' => 'bank'], null, null);
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');
        $this->actingAs($admin, 'api')->patchJson("/api/plearnd-admin/academy-donations/{$donation->id}/approve", [])->assertOk();
        $this->assertEquals((int) round(50 * config('economy.donation_pp_per_baht')), AcademyPointAccount::first()->balance);
    }

    public function test_admin_cannot_approve_cash_to_own_academy(): void
    {
        [$owner, $donor, $academy] = $this->makeAcademy();
        $donation = app(AcademyDonateService::class)->createCashDonation($donor, $academy, 50, ['payment_method' => 'bank'], null, null);
        $owner->assignRole('SUPER_ADMIN');
        $this->actingAs($owner, 'api')->patchJson("/api/plearnd-admin/academy-donations/{$donation->id}/approve", [])->assertForbidden();
    }

    public function test_admin_reject_stores_reason(): void
    {
        [, $donor, $academy] = $this->makeAcademy();
        $donation = app(AcademyDonateService::class)->createCashDonation($donor, $academy, 50, ['payment_method' => 'bank'], null, null);
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');
        $this->actingAs($admin, 'api')->patchJson("/api/plearnd-admin/academy-donations/{$donation->id}/reject", ['reason' => 'bad slip'])->assertOk();
        $this->assertSame('bad slip', $donation->fresh()->rejection_reason);
    }

    public function test_show_for_academy_owner_masks_donor_email(): void
    {
        [$owner, $donor, $academy] = $this->makeAcademy();
        app(AcademyDonateService::class)->createPointDonation($donor, $academy, 10, [], null);
        $this->actingAs($owner, 'api')->getJson("/api/academies/{$academy->id}/donations")->assertOk()->assertJsonMissing(['email' => $donor->email]);
    }
}
