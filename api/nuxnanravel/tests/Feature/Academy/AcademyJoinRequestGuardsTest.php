<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyJoinRequestGuardsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Academy}
     */
    private function setupAcademy(): array
    {
        foreach (AcademyRole::SYSTEM_ROLES as $name => $data) {
            AcademyRole::create(array_merge($data, ['academy_id' => null, 'name' => $name, 'is_system' => true, 'is_active' => true]));
        }
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        return [$owner, $academy];
    }

    private function memberWithRole(Academy $academy, string $roleName): array
    {
        $role = AcademyRole::where('name', $roleName)->whereNull('academy_id')->firstOrFail();
        $user = User::factory()->create();
        $member = AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $role->id,
            'role' => $role->name,
            'status' => 2,
        ]);

        return [$user, $member];
    }

    private function pendingRequest(Academy $academy): AcademyMember
    {
        return AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => User::factory()->create()->id,
            'status' => 1,
        ]);
    }

    public function test_owner_can_view_pending_requests(): void
    {
        [$owner, $academy] = $this->setupAcademy();
        $this->pendingRequest($academy);

        $this->actingAs($owner, 'api')->getJson("/api/academies/{$academy->id}/pending-requests")
            ->assertOk()->assertJsonCount(1, 'pendingRequests');
    }

    public function test_member_with_manage_permission_can_view_pending_requests(): void
    {
        [$owner, $academy] = $this->setupAcademy();
        [$admin] = $this->memberWithRole($academy, 'admin');
        $this->pendingRequest($academy);

        $this->actingAs($admin, 'api')->getJson("/api/academies/{$academy->id}/pending-requests")
            ->assertOk()->assertJsonCount(1, 'pendingRequests');
    }

    public function test_student_member_cannot_view_pending_requests(): void
    {
        [$owner, $academy] = $this->setupAcademy();
        [$student] = $this->memberWithRole($academy, 'student');
        $this->pendingRequest($academy);

        $this->actingAs($student, 'api')->getJson("/api/academies/{$academy->id}/pending-requests")
            ->assertForbidden();
    }

    public function test_outsider_cannot_view_pending_requests(): void
    {
        [$owner, $academy] = $this->setupAcademy();
        $outsider = User::factory()->create();

        $this->actingAs($outsider, 'api')->getJson("/api/academies/{$academy->id}/pending-requests")
            ->assertForbidden();
    }

    public function test_owner_can_bulk_approve_requests(): void
    {
        [$owner, $academy] = $this->setupAcademy();
        $a = $this->pendingRequest($academy);
        $b = $this->pendingRequest($academy);

        $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/members/bulk-action", [
            'member_ids' => [$a->id, $b->id],
            'action' => 'approve',
        ])->assertOk()->assertJsonPath('success_count', 2);

        $this->assertDatabaseHas('academy_members', ['id' => $a->id, 'status' => 2]);
        $this->assertDatabaseHas('academy_members', ['id' => $b->id, 'status' => 2]);
    }

    public function test_student_member_cannot_bulk_approve_requests(): void
    {
        [$owner, $academy] = $this->setupAcademy();
        [$student] = $this->memberWithRole($academy, 'student');
        $a = $this->pendingRequest($academy);

        $this->actingAs($student, 'api')->postJson("/api/academies/{$academy->id}/members/bulk-action", [
            'member_ids' => [$a->id],
            'action' => 'approve',
        ])->assertForbidden();

        $this->assertDatabaseHas('academy_members', ['id' => $a->id, 'status' => 1]);
    }
}
