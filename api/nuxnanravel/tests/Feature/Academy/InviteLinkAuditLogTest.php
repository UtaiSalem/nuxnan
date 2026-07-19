<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyInviteLink;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\MemberActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InviteLinkAuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function makeAcademy(): array
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

    public function test_create_logs_activity(): void
    {
        [$academy, $admin] = $this->makeAcademy();
        $this->actingAs($admin, 'api')->postJson("/api/academies/{$academy->id}/invite-links", ['name' => 'Audit'])->assertCreated();
        $this->assertDatabaseHas('member_activity_logs', ['academy_id' => $academy->id, 'action' => MemberActivityLog::ACTION_INVITE_LINK_CREATE]);
    }

    public function test_delete_logs_activity(): void
    {
        [$academy, $admin] = $this->makeAcademy();
        $link = AcademyInviteLink::create(['academy_id' => $academy->id, 'created_by' => $admin->id, 'name' => 'Delete']);
        $this->actingAs($admin, 'api')->deleteJson("/api/academies/{$academy->id}/invite-links/{$link->id}")->assertOk();
        $this->assertDatabaseHas('member_activity_logs', ['academy_id' => $academy->id, 'action' => MemberActivityLog::ACTION_INVITE_LINK_DELETE]);
    }

    public function test_toggle_logs_activity(): void
    {
        [$academy, $admin] = $this->makeAcademy();
        $link = AcademyInviteLink::create(['academy_id' => $academy->id, 'created_by' => $admin->id, 'name' => 'Toggle']);
        $this->actingAs($admin, 'api')->postJson("/api/academies/{$academy->id}/invite-links/{$link->id}/toggle-active")->assertOk();
        $this->assertDatabaseHas('member_activity_logs', ['academy_id' => $academy->id, 'action' => MemberActivityLog::ACTION_INVITE_LINK_TOGGLE]);
    }
}
