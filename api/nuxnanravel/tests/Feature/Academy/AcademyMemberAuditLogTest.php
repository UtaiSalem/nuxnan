<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\MemberActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyMemberAuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function makeAcademy(): array
    {
        foreach (AcademyRole::SYSTEM_ROLES as $name => $data) {
            AcademyRole::create(array_merge($data, ['academy_id' => null, 'name' => $name, 'is_system' => true, 'is_active' => true]));
        } $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $admin = User::factory()->create();
        $role = AcademyRole::where('name', 'admin')->whereNull('academy_id')->firstOrFail();
        $adminMember = AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $admin->id, 'academy_role_id' => $role->id, 'role' => 'admin', 'status' => 2]);
        $target = AcademyMember::create(['academy_id' => $academy->id, 'user_id' => User::factory()->create()->id, 'status' => 1]);

        return [$owner, $academy, $admin, $adminMember, $target];
    }

    private function assertLog(Academy $academy, string $action, string $text): void
    {
        $this->assertDatabaseHas('member_activity_logs', ['academy_id' => $academy->id, 'action' => $action, 'action_category' => MemberActivityLog::CATEGORY_MEMBER]);
        $this->assertDatabaseHas('member_activity_logs', ['academy_id' => $academy->id, 'description' => $text]);
    }

    public function test_accepting_member_logs_activity(): void
    {
        [$owner, $academy, $admin, $am, $target] = $this->makeAcademy();
        $this->actingAs($admin, 'api')->postJson("/api/academies/{$academy->id}/members/{$target->id}/accept");
        $this->assertLog($academy, MemberActivityLog::ACTION_APPROVE, 'อนุมัติสมาชิก');
    }

    public function test_rejecting_member_logs_activity(): void
    {
        [$owner, $academy, $admin, $am, $target] = $this->makeAcademy();
        $this->actingAs($admin, 'api')->postJson("/api/academies/{$academy->id}/members/{$target->id}/reject");
        $this->assertLog($academy, MemberActivityLog::ACTION_REJECT, 'ปฏิเสธคำขอสมาชิก');
    }

    public function test_suspending_member_logs_activity_with_reason(): void
    {
        [$owner, $academy, $admin, $am, $target] = $this->makeAcademy();
        $this->actingAs($admin, 'api')->postJson("/api/academies/{$academy->id}/members/{$target->id}/suspend", ['reason' => 'policy']);
        $this->assertLog($academy, MemberActivityLog::ACTION_SUSPEND, 'ระงับสมาชิก');
    }

    public function test_unsuspending_member_logs_activity(): void
    {
        [$owner, $academy, $admin, $am, $target] = $this->makeAcademy();
        $target->update(['status' => 5]);
        $this->actingAs($admin, 'api')->postJson("/api/academies/{$academy->id}/members/{$target->id}/unsuspend");
        $this->assertLog($academy, MemberActivityLog::ACTION_UNSUSPEND, 'ยกเลิกการระงับสมาชิก');
    }

    public function test_removing_member_logs_activity(): void
    {
        [$owner, $academy, $admin, $am, $target] = $this->makeAcademy();
        $this->actingAs($owner, 'api')->deleteJson("/api/academies/{$academy->id}/members/{$target->id}");
        $this->assertLog($academy, MemberActivityLog::ACTION_REMOVE, 'ลบสมาชิก');
    }

    public function test_updating_member_logs_activity_with_diff(): void
    {
        [$owner, $academy, $admin, $am, $target] = $this->makeAcademy();
        $this->actingAs($admin, 'api')->patchJson("/api/academies/{$academy->id}/members/{$target->id}", ['note_comment' => 'changed']);
        $this->assertLog($academy, MemberActivityLog::ACTION_PROFILE_UPDATE, 'อัปเดตข้อมูลสมาชิก');
    }
}
