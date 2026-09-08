<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\KpiDefinition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolExpenseSchemaFixTest extends TestCase
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

    public function test_store_expense_persists_with_real_columns(): void
    {
        [$academy, $user] = $this->academyWithMember(['finance.view', 'finance.manage']);

        $cat = ExpenseCategory::create([
            'academy_id' => $academy->id,
            'name' => 'ค่าน้ำค่าไฟ',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/expenses", [
                'expense_category_id' => $cat->id,
                'title' => 'ค่าไฟ',
                'amount' => 1500.50,
                'expense_date' => '2026-09-01',
                'vendor' => 'กฟภ.',
                'description' => 'บิลเดือน 9',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('expenses', [
            'academy_id' => $academy->id,
            'expense_category_id' => $cat->id,
            'title' => 'ค่าไฟ',
            'vendor' => 'กฟภ.',
            'status' => 'pending',
        ]);

        $expense = Expense::first();
        $this->assertEquals($user->id, $expense->requested_by);
    }

    public function test_expense_summary_returns_category_breakdown_without_error(): void
    {
        [$academy, $user] = $this->academyWithMember(['finance.view', 'finance.manage']);

        $cat = ExpenseCategory::create([
            'academy_id' => $academy->id,
            'name' => 'ค่าน้ำค่าไฟ',
            'is_active' => true,
        ]);

        Expense::create([
            'academy_id' => $academy->id,
            'expense_category_id' => $cat->id,
            'title' => 'ค่าไฟ 1',
            'amount' => 1000,
            'expense_date' => '2026-09-01',
            'status' => 'approved',
            'requested_by' => $user->id,
        ]);

        Expense::create([
            'academy_id' => $academy->id,
            'expense_category_id' => $cat->id,
            'title' => 'ค่าไฟ 2',
            'amount' => 500,
            'expense_date' => '2026-09-02',
            'status' => 'approved',
            'requested_by' => $user->id,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/academies/{$academy->id}/expenses/summary");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertIsArray($response->json('data.by_category'));
    }

    public function test_store_expense_category_without_display_order(): void
    {
        [$academy, $user] = $this->academyWithMember(['finance.view', 'finance.manage']);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/expenses/categories", [
                'name' => 'หมวดใหม่',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('expense_categories', [
            'academy_id' => $academy->id,
            'name' => 'หมวดใหม่',
        ]);
    }

    public function test_create_kpi_without_calculation_defaults_to_empty_array(): void
    {
        [$academy, $user] = $this->academyWithMember(['reports.view']);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/academies/{$academy->id}/analytics/kpis", [
                'code' => 'KPI_'.uniqid(),
                'name' => 'อัตราการมาเรียน',
                'category' => KpiDefinition::CATEGORIES[0],
                'metric_type' => KpiDefinition::METRIC_TYPES[0],
                'comparison_direction' => 'higher_is_better',
            ]);

        $this->assertNotEquals(500, $response->status());
        $response->assertStatus(201);

        $kpi = KpiDefinition::first();
        $this->assertEquals([], $kpi->calculation);
    }
}
