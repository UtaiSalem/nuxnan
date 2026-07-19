<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyMemberGuardsTest extends TestCase
{
    use RefreshDatabase;

    private function setupAcademy(bool $admin = true): array
    {
        foreach (AcademyRole::SYSTEM_ROLES as $name => $data) {
            AcademyRole::create(array_merge($data, ['academy_id' => null, 'name' => $name, 'is_system' => true, 'is_active' => true]));
        }
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $role = AcademyRole::where('name', $admin ? 'admin' : 'student')->whereNull('academy_id')->firstOrFail();
        $user = User::factory()->create();
        $member = AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $user->id, 'academy_role_id' => $role->id, 'role' => $role->name, 'status' => 2]);

        return [$owner, $academy, $user, $member];
    }

    public function test_acceptmember_requires_permission(): void
    {
        [$owner, $academy, $user, $member] = $this->setupAcademy(false);
        $this->actingAs($user, 'api')->postJson("/api/academies/{$academy->id}/members/{$member->id}/accept")->assertForbidden();
    }

    public function test_rejectmember_requires_permission(): void
    {
        [$owner, $academy, $user, $member] = $this->setupAcademy(false);
        $this->actingAs($user, 'api')->postJson("/api/academies/{$academy->id}/members/{$member->id}/reject")->assertForbidden();
    }

    public function test_admin_cannot_suspend_self(): void
    {
        [$owner, $academy, $admin, $member] = $this->setupAcademy();
        $member->update(['user_id' => $admin->id]);
        $this->actingAs($admin, 'api')->postJson("/api/academies/{$academy->id}/members/{$member->id}/suspend")->assertForbidden();
    }

    public function test_admin_cannot_remove_self(): void
    {
        [$owner, $academy, $admin, $member] = $this->setupAcademy();
        $member->update(['user_id' => $admin->id]);
        $this->actingAs($admin, 'api')->deleteJson("/api/academies/{$academy->id}/members/{$member->id}")->assertForbidden();
    }

    public function test_owner_can_self_update(): void
    {
        [$owner, $academy] = $this->setupAcademy();
        $member = AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $owner->id, 'status' => 2]);
        $this->actingAs($owner, 'api')->patchJson("/api/academies/{$academy->id}/members/{$member->id}/identity", ['member_code' => 'OWN'])->assertOk();
    }

    public function test_admin_cannot_update_owner_member(): void
    {
        [$owner, $academy, $admin] = $this->setupAcademy();
        $member = AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $owner->id, 'status' => 2]);
        $this->actingAs($admin, 'api')->patchJson("/api/academies/{$academy->id}/members/{$member->id}", ['note_comment' => 'x'])->assertForbidden();
    }

    public function test_bulk_action_skips_self_target(): void
    {
        [$owner, $academy, $admin, $member] = $this->setupAcademy();
        $member->update(['user_id' => $admin->id]);
        $other = AcademyMember::create(['academy_id' => $academy->id, 'user_id' => User::factory()->create()->id, 'status' => 2]);
        $this->actingAs($admin, 'api')->postJson("/api/academies/{$academy->id}/members/bulk-action", ['member_ids' => [$member->id, $other->id], 'action' => 'suspend'])->assertOk()->assertJsonPath('skipped.0.member_id', $member->id)->assertJsonPath('skipped.0.reason', 'self');
    }

    public function test_bulk_action_skips_owner_target(): void
    {
        [$owner, $academy, $admin, $member] = $this->setupAcademy();
        $ownerMember = AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $owner->id, 'status' => 2]);
        $this->actingAs($admin, 'api')->postJson("/api/academies/{$academy->id}/members/bulk-action", ['member_ids' => [$ownerMember->id, $member->id], 'action' => 'suspend'])->assertOk()->assertJsonPath('skipped.0.member_id', $ownerMember->id)->assertJsonPath('skipped.0.reason', 'owner');
    }
}
