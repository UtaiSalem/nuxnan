<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\MemberActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyRoleAuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function setupAcademy(): array
    {
        foreach (AcademyRole::SYSTEM_ROLES as $name => $data) {
            AcademyRole::create(array_merge($data, ['academy_id' => null, 'name' => $name, 'is_system' => true, 'is_active' => true]));
        }
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        return [$owner, $academy];
    }

    private function role(Academy $academy): AcademyRole
    {
        return AcademyRole::create(['academy_id' => $academy->id, 'name' => fake()->unique()->lexify('role_????'), 'display_name_th' => 'บทบาททดสอบ', 'display_name_en' => 'Test role', 'description' => 'desc', 'permissions' => ['members.view'], 'is_system' => false, 'is_active' => true, 'sort_order' => 99, 'color' => 'gray', 'icon' => 'test']);
    }

    private function member(Academy $academy, AcademyRole $role): AcademyMember
    {
        return AcademyMember::create(['academy_id' => $academy->id, 'user_id' => User::factory()->create()->id, 'academy_role_id' => $role->id, 'role' => $role->name, 'status' => 2]);
    }

    public function test_creating_role_logs_activity(): void
    {
        [$owner, $academy] = $this->setupAcademy();
        $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/roles", ['name' => 'new_role', 'display_name_th' => 'บทบาทใหม่', 'permissions' => ['members.view']])->assertCreated();
        $this->assertDatabaseHas('member_activity_logs', ['academy_id' => $academy->id, 'action' => MemberActivityLog::ACTION_ROLE_CREATE, 'action_category' => 'role', 'description' => "สร้างบทบาท 'บทบาทใหม่'"]);
    }

    public function test_updating_role_logs_activity_with_old_and_new_values(): void
    {
        [$owner, $academy] = $this->setupAcademy();
        $role = $this->role($academy);
        $this->actingAs($owner, 'api')->putJson("/api/academies/{$academy->id}/roles/{$role->id}", ['display_name_th' => 'แก้แล้ว', 'permissions' => ['members.manage']])->assertOk();
        $log = MemberActivityLog::where('action', MemberActivityLog::ACTION_ROLE_UPDATE)->first();
        $this->assertNotNull($log);
        $this->assertSame('บทบาททดสอบ', $log->old_values['display_name_th']);
        $this->assertSame('แก้แล้ว', $log->new_values['display_name_th']);
    }

    public function test_deleting_role_logs_activity_with_reassigned_count(): void
    {
        [$owner, $academy] = $this->setupAcademy();
        $role = $this->role($academy);
        $this->member($academy, $role);
        $this->actingAs($owner, 'api')->deleteJson("/api/academies/{$academy->id}/roles/{$role->id}")->assertOk();
        $log = MemberActivityLog::where('action', MemberActivityLog::ACTION_ROLE_DELETE)->first();
        $this->assertNotNull($log);
        $this->assertSame(1, $log->old_values['reassigned_count']);
    }

    public function test_assigning_role_to_member_logs_activity(): void
    {
        [$owner, $academy] = $this->setupAcademy();
        $role = $this->role($academy);
        $member = $this->member($academy, AcademyRole::where('name', 'student')->whereNull('academy_id')->first());
        $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/members/{$member->id}/role", ['role_id' => $role->id])->assertOk();
        $this->assertDatabaseHas('member_activity_logs', ['academy_member_id' => $member->id, 'target_user_id' => $member->user_id, 'action' => MemberActivityLog::ACTION_ROLE_ASSIGN, 'action_category' => 'role']);
    }

    public function test_bulk_assigning_role_logs_activity(): void
    {
        [$owner, $academy] = $this->setupAcademy();
        $role = $this->role($academy);
        $ids = [$this->member($academy, AcademyRole::where('name', 'student')->whereNull('academy_id')->first())->id, $this->member($academy, AcademyRole::where('name', 'student')->whereNull('academy_id')->first())->id];
        $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/members/bulk-role", ['role_id' => $role->id, 'member_ids' => $ids])->assertOk();
        $log = MemberActivityLog::where('action', MemberActivityLog::ACTION_ROLE_BULK_ASSIGN)->first();
        $this->assertNotNull($log);
        $this->assertSame(2, $log->new_values['updated_count']);
    }
}
