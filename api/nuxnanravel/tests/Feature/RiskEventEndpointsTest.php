<?php

namespace Tests\Feature;

use App\Models\PlearndAdmin;
use App\Models\RiskEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskEventEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        PlearndAdmin::create(['user_id' => $user->id]);

        return $user;
    }

    private function event(): RiskEvent
    {
        return RiskEvent::create([
            'rule_name' => 'reconcile_user_wallet', 'subject_type' => (new User)->getMorphClass(),
            'subject_id' => User::factory()->create()->id, 'severity' => 'high', 'score' => 80,
            'evidence' => ['stored' => 5, 'expected' => 0, 'diff' => 5], 'status' => 'open',
        ]);
    }

    public function test_admin_can_list_open_events(): void
    {
        $this->event();
        $this->actingAs($this->admin(), 'api')->getJson('/api/plearnd-admin/risk-events')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_non_admin_gets_403(): void
    {
        $this->actingAs(User::factory()->create(['email_verified_at' => now()]), 'api')->getJson('/api/plearnd-admin/risk-events')->assertForbidden();
    }

    public function test_admin_can_acknowledge(): void
    {
        $event = $this->event();
        $this->actingAs($this->admin(), 'api')->patchJson("/api/plearnd-admin/risk-events/{$event->id}/acknowledge")->assertOk()->assertJsonPath('data.status', 'acknowledged');
    }

    public function test_admin_can_resolve_with_note(): void
    {
        $event = $this->event();
        $this->actingAs($admin = $this->admin(), 'api')->patchJson("/api/plearnd-admin/risk-events/{$event->id}/resolve", ['resolution_note' => 'Reviewed'])->assertOk()->assertJsonPath('data.status', 'resolved');
        $this->assertDatabaseHas('risk_events', ['id' => $event->id, 'resolved_by' => $admin->id]);
    }

    public function test_resolve_requires_note(): void
    {
        $event = $this->event();
        $this->actingAs($this->admin(), 'api')->patchJson("/api/plearnd-admin/risk-events/{$event->id}/resolve")->assertUnprocessable();
    }
}
