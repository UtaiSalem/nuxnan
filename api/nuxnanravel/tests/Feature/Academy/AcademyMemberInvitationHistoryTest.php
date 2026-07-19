<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\MemberActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyMemberInvitationHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        foreach (AcademyRole::SYSTEM_ROLES as $name => $data) {
            AcademyRole::create(array_merge($data, ['academy_id' => null, 'name' => $name, 'is_system' => true, 'is_active' => true]));
        }
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $admin = User::factory()->create();
        $role = AcademyRole::where('name', 'admin')->whereNull('academy_id')->firstOrFail();
        AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $admin->id, 'academy_role_id' => $role->id, 'role' => 'admin', 'status' => 2]);

        return [$academy, $admin];
    }

    public function test_admin_can_list_invitation_history(): void
    {
        [$academy, $admin] = $this->context();
        foreach ([MemberActivityLog::ACTION_INVITE, MemberActivityLog::ACTION_ACCEPT_INVITE, MemberActivityLog::ACTION_DECLINE_INVITE] as $action) {
            MemberActivityLog::create(['academy_id' => $academy->id, 'user_id' => $admin->id, 'action' => $action, 'description' => $action]);
        }
        $this->actingAs($admin, 'api')->getJson("/api/academies/{$academy->id}/members/invitations")->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_non_manager_cannot_list_invitation_history(): void
    {
        [$academy] = $this->context();
        $member = User::factory()->create();
        AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $member->id, 'status' => 2]);
        $this->actingAs($member, 'api')->getJson("/api/academies/{$academy->id}/members/invitations")->assertForbidden();
    }

    public function test_can_filter_by_action(): void
    {
        [$academy, $admin] = $this->context();
        MemberActivityLog::create(['academy_id' => $academy->id, 'user_id' => $admin->id, 'action' => MemberActivityLog::ACTION_INVITE]);
        MemberActivityLog::create(['academy_id' => $academy->id, 'user_id' => $admin->id, 'action' => MemberActivityLog::ACTION_ACCEPT_INVITE]);
        $this->actingAs($admin, 'api')->getJson("/api/academies/{$academy->id}/members/invitations?action=invite")->assertOk()->assertJsonCount(1, 'data');
    }
}
