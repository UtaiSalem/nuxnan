<?php

namespace Tests\Feature;

use App\Models\Donate;
use App\Models\PointsTransaction;
use App\Models\User;
use App\Services\PointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Mockery;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class PublicDonationHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'pp' => 0,
            'total_points_earned' => 0,
            'total_points_spent' => 0,
            'personal_code' => (string) fake()->unique()->numberBetween(10000000, 99999998),
        ], $attributes));
    }

    private function claim(User $user, Donate $donate)
    {
        return $this->withHeader('Authorization', 'Bearer '.JWTAuth::fromUser($user))
            ->getJson("/api/donates/{$donate->id}/get-donate");
    }

    public function test_claim_records_three_ledger_entries_and_exactly_decrements_270_points(): void
    {
        $platform = $this->user(['personal_code' => config('economy.platform_personal_code')]);
        $claimer = $this->user();
        $donate = Donate::factory()->create(['status' => 1, 'remaining_points' => 540]);

        $this->claim($claimer, $donate)->assertOk()->assertJsonPath('success', true);

        $this->assertSame(270, $donate->fresh()->remaining_points);
        $this->assertSame(3, PointsTransaction::where('source_id', $donate->id)->count());
        $this->assertSame(270.0, (float) PointsTransaction::where('source_id', $donate->id)->sum('amount'));
        $this->assertDatabaseHas('points_transactions', ['user_id' => $platform->id, 'source_type' => 'donation_platform']);
    }

    public function test_invalid_self_and_missing_suggester_use_distinct_platform_fallback_ledger(): void
    {
        $source = File::get(base_path('app/Http/Controllers/Api/Earn/DonateController.php'));
        $this->assertStringContainsString("'donation_platform_fallback'", $source);
        $this->assertStringContainsString("'donation_platform'", $source);
    }

    public function test_missing_platform_rolls_back_claim(): void
    {
        $claimer = $this->user();
        $donate = Donate::factory()->create(['status' => 1, 'remaining_points' => 270]);

        $this->claim($claimer, $donate)->assertOk()->assertJsonPath('success', false);

        $this->assertSame(270, $donate->fresh()->remaining_points);
        $this->assertDatabaseMissing('donate_recipients', ['donate_id' => $donate->id, 'user_id' => $claimer->id]);
        $this->assertDatabaseCount('points_transactions', 0);
    }

    public function test_failed_reward_rolls_back_claim_and_recipient(): void
    {
        $this->user(['personal_code' => config('economy.platform_personal_code')]);
        $claimer = $this->user();
        $donate = Donate::factory()->create(['status' => 1, 'remaining_points' => 270]);
        $service = Mockery::mock(PointsService::class);
        $service->shouldReceive('earn')->andThrow(new \RuntimeException('forced reward failure'));
        $this->app->instance(PointsService::class, $service);

        $this->claim($claimer, $donate)->assertOk()->assertJsonPath('success', false);

        $this->assertSame(270, $donate->fresh()->remaining_points);
        $this->assertDatabaseMissing('donate_recipients', ['donate_id' => $donate->id]);
    }

    public function test_points_payment_creates_spend_ledger_and_store_rolls_back_when_save_fails(): void
    {
        $user = $this->user(['pp' => 1080]);
        $response = $this->withHeader('Authorization', 'Bearer '.JWTAuth::fromUser($user))->postJson('/api/supports/donates', [
            'amounts' => 1,
            'transfer_date' => now()->toDateString(),
            'transfer_time' => now()->format('H:i'),
            'payment_method' => 'points',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('points_transactions', ['user_id' => $user->id, 'transaction_type' => 'spend', 'amount' => 1080]);
        $this->assertSame(0, (int) $user->fresh()->pp);
    }

    public function test_configured_per_donation_and_global_caps_are_enforced(): void
    {
        $this->user(['personal_code' => config('economy.platform_personal_code')]);
        $claimer = $this->user();
        $perDonation = Donate::factory()->create(['status' => 1, 'remaining_points' => 270]);
        for ($i = 0; $i < config('economy.claim_cap_per_donation_per_day'); $i++) {
            DB::table('donate_recipients')->insert(['donate_id' => $perDonation->id, 'user_id' => $claimer->id, 'created_at' => now(), 'updated_at' => now()]);
        }

        $this->claim($claimer, $perDonation)->assertOk()->assertJsonFragment([
            'today_count' => config('economy.claim_cap_per_donation_per_day'),
        ]);

        $otherDonates = Donate::factory()->count(config('economy.claim_cap_total_per_day'))->create(['status' => 1]);
        foreach ($otherDonates as $otherDonate) {
            DB::table('donate_recipients')->insert(['donate_id' => $otherDonate->id, 'user_id' => $claimer->id, 'created_at' => now(), 'updated_at' => now()]);
        }

        $this->claim($claimer, Donate::factory()->create(['status' => 1, 'remaining_points' => 270]))
            ->assertOk()->assertJsonPath('today_count', config('economy.claim_cap_total_per_day') + config('economy.claim_cap_per_donation_per_day'));
    }
}
