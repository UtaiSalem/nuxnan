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
 * Covers two pre-existing defects in LeaveRequestController:
 *
 * 1. Audit-logging bug — log() was called positionally, pushing $academy->id
 *    (int) into the ?array $oldValues parameter -> TypeError -> HTTP 500 after
 *    the row was written. Guarded by the approve/reject/cancel tests.
 *
 * 2. Schema drift — store()/storeLeaveType()/staffLeaveBalance() referenced
 *    columns that do not exist (days_per_year, allow_negative, is_half_day,
 *    half_day_type, attachment_path). The controller now uses the real columns
 *    (max_days_per_year, leave_period, document_path). Guarded by the store /
 *    store-leave-type / half-day / balance tests.
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

    private function staff(Academy $academy): StaffProfile
    {
        return StaffProfile::create([
            'academy_id' => $academy->id,
            'user_id' => User::factory()->create()->id,
            'employee_id' => 'EMP-'.uniqid(),
            'first_name' => 'Somchai',
            'last_name' => 'Test',
            'hire_date' => '2026-01-01',
        ]);
    }

    private function leaveType(Academy $academy, ?int $maxDays = null): LeaveType
    {
        return LeaveType::create([
            'academy_id' => $academy->id,
            'code' => 'LV'.random_int(1000, 9999),
            'name' => 'ลาป่วย',
            'max_days_per_year' => $maxDays,
        ]);
    }

    private function pendingLeave(Academy $academy): LeaveRequest
    {
        return LeaveRequest::create([
            'staff_profile_id' => $this->staff($academy)->id,
            'leave_type_id' => $this->leaveType($academy)->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-01',
            'total_days' => 1,
            'reason' => 'ไม่สบาย',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);
    }

    // ── Audit-logging bug (approve / reject / cancel) ──────────────────────

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

    // ── Schema drift (store / storeLeaveType / half-day / balance) ─────────

    public function test_store_leave_request_returns_201_using_real_columns(): void
    {
        [$academy, $user] = $this->academyWithMember(['staff.view']);
        $staff = $this->staff($academy);
        $type = $this->leaveType($academy, 10);
        $date = now()->addWeek()->toDateString();

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/leave-requests", [
                'staff_profile_id' => $staff->id,
                'leave_type_id' => $type->id,
                'start_date' => $date,
                'end_date' => $date,
                'reason' => 'พักผ่อน',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('leave_requests', [
            'staff_profile_id' => $staff->id,
            'leave_type_id' => $type->id,
            'leave_period' => LeaveRequest::PERIOD_FULL_DAY,
            'total_days' => 1,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'leave.request',
            'entity_type' => LeaveRequest::class,
            'module' => 'academy',
        ]);
    }

    public function test_store_half_day_leave_counts_as_half(): void
    {
        [$academy, $user] = $this->academyWithMember(['staff.view']);
        $staff = $this->staff($academy);
        $type = $this->leaveType($academy, 10);
        $date = now()->addWeek()->toDateString();

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/leave-requests", [
                'staff_profile_id' => $staff->id,
                'leave_type_id' => $type->id,
                'start_date' => $date,
                'end_date' => $date,
                'leave_period' => LeaveRequest::PERIOD_MORNING,
                'reason' => 'ธุระครึ่งวัน',
            ]);

        $response->assertStatus(201);
        $this->assertSame('0.50', (string) $response->json('data.total_days'));
        $this->assertDatabaseHas('leave_requests', [
            'staff_profile_id' => $staff->id,
            'leave_period' => LeaveRequest::PERIOD_MORNING,
            'total_days' => 0.5,
        ]);
    }

    public function test_store_rejects_when_balance_insufficient(): void
    {
        [$academy, $user] = $this->academyWithMember(['staff.view']);
        $staff = $this->staff($academy);
        $type = $this->leaveType($academy, 1); // quota 1 day
        $start = now()->addWeek();

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/leave-requests", [
                'staff_profile_id' => $staff->id,
                'leave_type_id' => $type->id,
                'start_date' => $start->toDateString(),
                'end_date' => $start->copy()->addDay()->toDateString(), // 2 days > quota
                'reason' => 'ลายาว',
            ]);

        $response->assertStatus(400);
        $this->assertDatabaseMissing('leave_requests', [
            'staff_profile_id' => $staff->id,
            'leave_type_id' => $type->id,
        ]);
    }

    public function test_store_leave_type_returns_201_using_real_columns(): void
    {
        [$academy, $user] = $this->academyWithMember(['staff.view']);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/leave-requests/leave-types", [
                'code' => 'VAC'.random_int(100, 999),
                'name' => 'ลาพักร้อน',
                'max_days_per_year' => 10,
                'is_paid' => true,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('leave_types', [
            'academy_id' => $academy->id,
            'name' => 'ลาพักร้อน',
            'max_days_per_year' => 10,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'leave_type.create',
            'entity_type' => LeaveType::class,
            'module' => 'academy',
        ]);
    }
}
