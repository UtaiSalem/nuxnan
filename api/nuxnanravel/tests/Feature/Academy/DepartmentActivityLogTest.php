<?php

namespace Tests\Feature\Academy;

use App\Http\Controllers\Api\Learn\Academy\MemberActivityLogController;
use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\AcademyGroupMember;
use App\Models\AcademyMember;
use App\Models\MemberActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_department_logs_academy(): void
    {
        [$academy, $owner] = $this->context();
        $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/departments", ['name' => 'ฝ่ายใหม่'])->assertCreated();
        $this->assertDatabaseHas('member_activity_logs', ['academy_id' => $academy->id, 'action' => MemberActivityLog::ACTION_DEPARTMENT_CREATE]);
    }

    public function test_delete_department_logs_old_name(): void
    {
        [$academy, $owner, $department] = $this->context();
        $department->members()->detach();
        $this->actingAs($owner, 'api')->deleteJson("/api/academies/{$academy->id}/departments/{$department->id}")->assertOk();
        $this->assertSame('ฝ่ายทดสอบ', MemberActivityLog::where('action', MemberActivityLog::ACTION_DEPARTMENT_DELETE)->firstOrFail()->old_values['name']);
    }

    public function test_bulk_add_logs_actual_added_count(): void
    {
        [$academy, $owner, $department] = $this->context();
        $users = User::factory()->count(2)->create();
        $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/departments/{$department->id}/members/bulk", ['user_ids' => $users->pluck('id')->all()])->assertOk();
        $this->assertSame(2, MemberActivityLog::where('action', MemberActivityLog::ACTION_DEPARTMENT_MEMBER_ADD)->firstOrFail()->new_values['member_count']);
    }

    public function test_permission_log_records_toggled_keys(): void
    {
        [$academy, $owner, $department] = $this->context();
        $this->actingAs($owner, 'api')->putJson("/api/academies/{$academy->id}/departments/{$department->id}/permissions", ['permission_keys' => ['students.view', 'groups.view']])->assertOk();
        $log = MemberActivityLog::where('action', MemberActivityLog::ACTION_DEPARTMENT_PERMISSION_UPDATE)->firstOrFail();
        $this->assertSame(['students.view', 'groups.view'], $log->new_values['turned_on']);
        $this->assertSame([], $log->new_values['turned_off']);
    }

    public function test_department_actions_are_available_for_filtering(): void
    {
        $response = app(MemberActivityLogController::class)->getAvailableActions();
        $values = collect($response->getData(true)['actions'])->pluck('value');
        foreach (['CREATE', 'UPDATE', 'DELETE', 'SETUP', 'MEMBER_ADD', 'MEMBER_REMOVE', 'MEMBER_ROLE_CHANGE', 'PERMISSION_UPDATE'] as $suffix) {
            $this->assertTrue($values->contains(constant(MemberActivityLog::class.'::ACTION_DEPARTMENT_'.$suffix)));
        }
    }

    private function context(): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $department = AcademyGroup::create(['academy_id' => $academy->id, 'name' => 'ฝ่ายทดสอบ', 'type' => 'department']);
        AcademyMember::create(['academy_id' => $academy->id, 'user_id' => $owner->id, 'academy_role_id' => null, 'status' => 2]);
        AcademyGroupMember::create(['academy_group_id' => $department->id, 'user_id' => $owner->id, 'status' => 2]);

        return [$academy, $owner, $department];
    }
}
