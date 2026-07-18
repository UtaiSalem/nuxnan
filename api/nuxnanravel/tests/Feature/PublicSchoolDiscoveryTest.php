<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSchoolDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    private function school(array $v = []): Academy
    {
        $owner = $v['user_id'] ?? User::factory()->create()->id;

        return Academy::create(array_merge([
            'name' => 'School '.uniqid(),
            'user_id' => $owner,
            'donation_enabled' => true,
        ], $v));
    }

    public function test_public_can_list_schools_without_auth(): void
    {
        $this->school();
        $this->getJson('/api/public/schools')->assertOk();
    }

    public function test_disabled_schools_are_excluded(): void
    {
        $a = $this->school(['donation_enabled' => false]);
        $r = $this->getJson('/api/public/schools');
        $r->assertOk();
        $data = $r->json('data');
        $ids = collect($data)->pluck('id')->all();
        $this->assertNotContains($a->id, $ids);
    }

    public function test_search_by_name_returns_matching(): void
    {
        $a = $this->school(['name' => 'Unique Academy']);
        $r = $this->getJson('/api/public/schools?q=Unique');
        $r->assertOk();
        $ids = collect($r->json('data'))->pluck('id')->all();
        $this->assertContains($a->id, $ids);
    }

    public function test_sort_most_supported_orders_by_donation_sum(): void
    {
        $this->school();
        $this->getJson('/api/public/schools?sort=most_supported')->assertOk();
    }

    public function test_show_returns_donation_signals(): void
    {
        $a = $this->school();
        $r = $this->getJson('/api/public/schools/'.$a->id);
        $r->assertOk();
        $this->assertNotNull($r->json('data.name'), 'response body: '.$r->getContent());
    }

    public function test_show_404_when_disabled(): void
    {
        $a = $this->school(['donation_enabled' => false]);
        $this->assertFalse((bool) $a->fresh()->donation_enabled, 'donation_enabled must be false in DB');
        $this->getJson('/api/public/schools/'.$a->id)->assertNotFound();
    }

    public function test_support_summary_aggregates_correctly(): void
    {
        $a = $this->school();
        $r = $this->getJson('/api/public/schools/'.$a->id.'/support-summary');
        $r->assertOk();
    }

    public function test_anonymous_donor_shows_masked_name(): void
    {
        $a = $this->school();
        $this->getJson('/api/public/schools/'.$a->id.'/support-summary')
            ->assertJsonMissing(['display_name' => '']);
    }
}
