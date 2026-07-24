<?php

namespace Tests\Feature;

use App\Models\Donate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonateSlipPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_donate_does_not_return_a_slip_path(): void
    {
        $user = User::factory()->create();
        $donate = Donate::factory()->create([
            'status' => 1,
            'remaining_points' => 1080,
            'slip' => 'private.jpg',
        ]);

        $response = $this->actingAs($user, 'api')->getJson("/api/donates/{$donate->id}/get-donate");

        $response->assertOk()->assertJsonMissingPath('donate.slip');
    }

    public function test_widget_excludes_pending_donations(): void
    {
        $user = User::factory()->create();
        Donate::factory()->create(['status' => 0, 'remaining_points' => 1080]);
        $approved = Donate::factory()->create(['status' => 1, 'remaining_points' => 1080]);

        $response = $this->actingAs($user, 'api')->getJson('/api/donates/widget');

        $response->assertOk()
            ->assertJsonPath('donates.0.id', $approved->id)
            ->assertJsonMissing(['status' => 0]);
    }

    public function test_download_slip_requires_authentication(): void
    {
        $donate = Donate::factory()->create(['slip' => 'private.jpg']);

        $this->getJson("/api/plearnd-admin/supports/donates/{$donate->id}/slip")
            ->assertUnauthorized();
    }
}
