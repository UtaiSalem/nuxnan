<?php

namespace Tests\Feature;

use App\Models\RevenueSharePolicy;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueSharePolicyAdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'SUPER_ADMIN']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('SUPER_ADMIN');
    }

    private function payload(): array
    {
        return ['scope_type' => 'platform', 'student_pct' => 70, 'course_pct' => 20, 'academy_pct' => 0, 'platform_pct' => 10, 'effective_from' => now()->toDateString()];
    }

    public function test_platform_admin_can_list_policies(): void
    {
        RevenueSharePolicy::create($this->payload() + ['version' => 1, 'created_by' => $this->admin->id]);
        $this->actingAs($this->admin, 'api')->getJson('/api/plearnd-admin/revenue-share-policies')->assertOk()->assertJsonStructure(['data']);
    }

    public function test_platform_admin_can_create_policy_when_sum_100(): void
    {
        $this->actingAs($this->admin, 'api')->postJson('/api/plearnd-admin/revenue-share-policies', $this->payload())->assertCreated();
        $this->assertDatabaseHas('revenue_share_policies', ['student_pct' => 70.00]);
    }

    public function test_create_fails_when_percentages_do_not_sum_to_100(): void
    {
        $this->actingAs($this->admin, 'api')->postJson('/api/plearnd-admin/revenue-share-policies', array_merge($this->payload(), ['platform_pct' => 11]))->assertUnprocessable();
    }

    public function test_update_bumps_version(): void
    {
        $p = RevenueSharePolicy::create($this->payload() + ['version' => 1, 'created_by' => $this->admin->id]);
        $this->actingAs($this->admin, 'api')->patchJson("/api/plearnd-admin/revenue-share-policies/{$p->id}", ['notes' => 'changed'])->assertOk();
        $this->assertDatabaseHas('revenue_share_policies', ['id' => $p->id, 'version' => 2]);
    }

    public function test_non_admin_cannot_create_policy(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api')->postJson('/api/plearnd-admin/revenue-share-policies', $this->payload())->assertForbidden();
    }
}
