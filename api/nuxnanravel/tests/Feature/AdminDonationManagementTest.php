<?php

namespace Tests\Feature;

use App\Models\Donate;
use App\Models\PlearndAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDonationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        // Create an admin user
        $this->admin = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Make them a Plearnd Admin
        PlearndAdmin::create(['user_id' => $this->admin->id]);
    }

    public function test_admin_can_list_donations_with_stats()
    {
        Donate::factory()->count(3)->create(['status' => 0]);
        Donate::factory()->count(2)->create(['status' => 1]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/plearnd-admin/supports/donates');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'donates' => [
                    'data',
                    'links',
                    'meta',
                ],
                'stats' => [
                    'total',
                    'pending',
                    'approved',
                    'rejected',
                ],
            ])
            ->assertJsonPath('stats.total', 5)
            ->assertJsonPath('stats.pending', 3);
    }

    public function test_admin_can_filter_donations_by_status()
    {
        Donate::factory()->count(3)->create(['status' => 0]);
        Donate::factory()->count(2)->create(['status' => 1]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/plearnd-admin/supports/donates?status=0');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'donates.data');
        // Stats should still be global
        $response->assertJsonPath('stats.total', 5);
    }

    public function test_admin_can_search_donations()
    {
        Donate::factory()->create(['donor_name' => 'John Doe', 'transaction_id' => 'TXN123']);
        Donate::factory()->create(['donor_name' => 'Jane Smith', 'transaction_id' => 'TXN456']);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/plearnd-admin/supports/donates?search=John');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'donates.data')
            ->assertJsonPath('donates.data.0.donor_name', 'John Doe');

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/plearnd-admin/supports/donates?search=TXN456');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'donates.data')
            ->assertJsonPath('donates.data.0.donor_name', 'Jane Smith');
    }

    public function test_admin_can_view_single_donation()
    {
        $donate = Donate::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/plearnd-admin/supports/donates/{$donate->id}");

        $response->assertStatus(200)
            ->assertJsonPath('donate.id', $donate->id);
    }

    public function test_admin_can_update_donation()
    {
        $donate = Donate::factory()->create(['donor_name' => 'Old Name']);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/plearnd-admin/supports/donates/{$donate->id}", [
                'donor_name' => 'New Name',
                'notes' => 'Some notes',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('donate.donor_name', 'New Name');

        $this->assertDatabaseHas('donates', [
            'id' => $donate->id,
            'donor_name' => 'New Name',
            'notes' => 'Some notes',
        ]);
    }

    public function test_admin_can_receive_donation()
    {
        $donate = Donate::factory()->create(['status' => 0]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/plearnd-admin/supports/donates/{$donate->id}/receive");

        $response->assertStatus(200)
            ->assertJsonPath('donate.status', 1);

        $this->assertDatabaseHas('donates', [
            'id' => $donate->id,
            'status' => 1,
            'approved_by' => $this->admin->id,
        ]);
        $this->assertNotNull($donate->fresh()->reviewed_at);
    }

    public function test_admin_cannot_receive_already_processed_donation()
    {
        $donate = Donate::factory()->create(['status' => 1]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/plearnd-admin/supports/donates/{$donate->id}/receive");

        $response->assertStatus(409);
    }

    public function test_admin_can_reject_donation()
    {
        $donate = Donate::factory()->create(['status' => 0]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/plearnd-admin/supports/donates/{$donate->id}/reject");

        $response->assertStatus(200)
            ->assertJsonPath('donate.status', 2);

        $this->assertDatabaseHas('donates', [
            'id' => $donate->id,
            'status' => 2,
            'approved_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_bulk_review_donations()
    {
        $donates = Donate::factory()->count(3)->create(['status' => 0]);
        $ids = $donates->pluck('id')->toArray();

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/plearnd-admin/supports/donates/bulk-review', [
                'ids' => $ids,
                'action' => 'approve',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('affected', 3);

        foreach ($ids as $id) {
            $this->assertDatabaseHas('donates', [
                'id' => $id,
                'status' => 1,
            ]);
        }
    }

    public function test_admin_can_soft_delete_donation()
    {
        $donate = Donate::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/plearnd-admin/supports/donates/{$donate->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('donates', ['id' => $donate->id]);
    }

    public function test_non_admin_cannot_access_donation_management()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/plearnd-admin/supports/donates');

        $response->assertStatus(403);
    }
}
