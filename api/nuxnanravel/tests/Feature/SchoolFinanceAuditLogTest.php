<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolFinanceAuditLogTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_expense_lifecycle_writes_audit_logs_and_module_fits_column(): void
    {
        [$academy, $user] = $this->academyWithMember(['finance.view', 'finance.manage']);

        $cat = ExpenseCategory::create([
            'academy_id' => $academy->id,
            'name' => 'Test Category',
            'is_active' => true,
        ]);

        // POST /expenses
        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/expenses", [
                'expense_category_id' => $cat->id,
                'title' => 'Test Expense',
                'amount' => 100,
                'expense_date' => '2026-09-01',
            ]);
        $response->assertStatus(201);
        $expenseId = $response->json('data.id');

        // PATCH /expenses/{id}
        $response2 = $this->actingAs($user, 'api')
            ->patchJson("/api/academies/{$academy->id}/expenses/{$expenseId}", [
                'title' => 'Test Expense Updated',
            ]);
        $response2->assertStatus(200);

        // POST /expenses/{id}/approve
        $response3 = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/expenses/{$expenseId}/approve");
        $response3->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => Expense::class,
            'action' => 'created',
        ]);

        foreach (AuditLog::where('entity_type', Expense::class)->get() as $r) {
            $this->assertLessThanOrEqual(50, strlen((string) $r->module));
        }

        $logs = AuditLog::where('entity_type', Expense::class)->get();

        $hasUpdated = $logs->where('action', 'updated')->count() > 0;
        $hasApproved = $logs->where('action', 'approved')->count() > 0;

        $this->assertTrue($hasUpdated, 'Should have updated action');
        $this->assertTrue($hasApproved, 'Should have approved action');
    }

    public function test_audit_service_signatures_used_by_finance_controllers_do_not_throw(): void
    {
        [$academy, $user] = $this->academyWithMember(['finance.view']);

        $cat = ExpenseCategory::create([
            'academy_id' => $academy->id,
            'name' => 'Test Category',
            'is_active' => true,
        ]);

        $expense = Expense::create([
            'academy_id' => $academy->id,
            'expense_category_id' => $cat->id,
            'title' => 'Direct Call Test',
            'amount' => 500,
            'expense_date' => '2026-09-01',
            'status' => 'pending',
            'requested_by' => $user->id,
        ]);

        // Capture count before manual calls
        $countBefore = AuditLog::where('entity_type', Expense::class)->count();

        $svc = app(AuditLogService::class);
        $svc->log(action: 'payroll.create', entity: $expense, module: 'academy', metadata: ['x' => 1]);
        $svc->log(action: 'add_funds', entity: $expense, module: 'finance', metadata: ['amount' => 5]);
        $svc->logApproval($expense, false, 'เหตุผลปฏิเสธ');

        $this->assertTrue(true, 'Service calls did not throw exceptions');

        $logs = AuditLog::where('entity_type', Expense::class)->latest()->take(3)->get();

        $countAfter = AuditLog::where('entity_type', Expense::class)->count();
        $this->assertGreaterThanOrEqual($countBefore + 3, $countAfter);

        foreach ($logs as $log) {
            $this->assertLessThanOrEqual(50, strlen((string) $log->module));
        }
    }
}
