<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the audit-logging bug where LeaveRequestController called
 * AuditLogService::log() positionally, pushing $academy->id (int) into the
 * ?array $oldValues parameter -> TypeError -> HTTP 500 after the row was written.
 *
 * These endpoints touch only real leave_requests columns, so the only 500 source
 * left is the audit-log call itself. Before the fix they returned 500; after,
 * they must return 2xx and persist the correct audit row.
 */
class LeaveRequestAuditLogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Academy, 1: User}
     */
    private function academyWithMember(array $permissions = []): array
    {
        $user = User::factory()->create();
        $academy = Academy::factory()->create();
        $role = AcademyRole::create([
            'academy_id' => $academy->id,
            'name' => 'test-role-'.uniqid(),
            'display_name_th' => 'Test role',
            'permissions' => $permissions,
        ]);
        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $role->id,
            'status' => 2,
        ]);

        return [$academy, $user];
    }

    private function pendingLeave(Academy $academy): LeaveRequest
    {
        $staff = StaffProfile::create([
            'academy_id' => $academy->id,
            'user_id' => User::factory()->create()->id,
            'employee_id' => 'EMP-'.uniqid(),
            'first_name' => 'Somchai',
            'last_name' => 'Test',
            'hire_date' => '2026-01-01',
        ]);

        $type = LeaveType::create([
            'academy_id' => $academy->id,
            'code' => 'SICK'.random_int(100, 999),
            'name' => 'ลาป่วย',
        ]);

        return LeaveRequest::create([
            'staff_profile_id' => $staff->id,
            'leave_type_id' => $type->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-01',
            'total_days' => 1,
            'reason' => 'ไม่สบาย',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);
    }

    public function test_approve_leave_request_returns_2xx_and_writes_audit_log(): void
    {
        [$academy, $user] = $this->academyWithMember(['staff.view']);
        $leave = $this->pendingLeave($academy);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/leave-requests/{$leave->id}/approve", [
                'approver_notes' => 'อนุมัติ',
            ]);

        $response->assertStatus(200);
        $this->assertSame(LeaveRequest::STATUS_APPROVED, $leave->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'leave.approve',
            'entity_type' => LeaveRequest::class,
            'entity_id' => $leave->id,
            'module' => 'academy',
        ]);
    }

    public function test_reject_leave_request_returns_2xx(): void
    {
        [$academy, $user] = $this->academyWithMember(['staff.view']);
        $leave = $this->pendingLeave($academy);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/leave-requests/{$leave->id}/reject", [
                'approver_notes' => 'ไม่อนุมัติ เอกสารไม่ครบ',
            ]);

        $response->assertStatus(200);
        $this->assertSame(LeaveRequest::STATUS_REJECTED, $leave->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'leave.reject',
            'entity_type' => LeaveRequest::class,
            'entity_id' => $leave->id,
            'module' => 'academy',
        ]);
    }

    public function test_cancel_leave_request_returns_2xx(): void
    {
        [$academy, $user] = $this->academyWithMember(['staff.view']);
        $leave = $this->pendingLeave($academy);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/leave-requests/{$leave->id}/cancel");

        $response->assertStatus(200);
        $this->assertSame(LeaveRequest::STATUS_CANCELLED, $leave->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'leave.cancel',
            'entity_type' => LeaveRequest::class,
            'entity_id' => $leave->id,
            'module' => 'academy',
        ]);
    }
}
