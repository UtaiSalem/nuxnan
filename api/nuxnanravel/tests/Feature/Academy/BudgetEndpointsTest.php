<?php

namespace Tests\Feature\Academy;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Budget;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * งบประมาณโรงเรียน — ทุก endpoint ต้องทำงานบนสคีมาจริงของตาราง `budgets`
 * (`expense_category_id`, `is_active`, `period` · ไม่มี `status`/`category_id`/`created_by`
 * และ `expenses` ไม่มี `budget_id`)
 *
 * รายจ่ายของงบ = รายจ่ายของโรงเรียนเดียวกัน ที่ `expense_date` อยู่ในช่วง start_date..end_date
 * และอยู่ในหมวดของงบ (ถ้างบไม่ระบุหมวด = ทุกหมวด)
 *
 * รันได้ทั้ง sqlite และ MySQL จริง: `php artisan test -c phpunit.mysql.xml --filter=BudgetEndpointsTest`
 */
class BudgetEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private Academy $academy;

    private User $owner;

    private AcademicYear $year;

    private ExpenseCategory $utilities;

    private ExpenseCategory $supplies;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->academy = Academy::factory()->create(['user_id' => $this->owner->id]);
        $this->year = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2569',
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ]);
        $this->utilities = ExpenseCategory::create(['academy_id' => $this->academy->id, 'name' => 'ค่าน้ำค่าไฟ']);
        $this->supplies = ExpenseCategory::create(['academy_id' => $this->academy->id, 'name' => 'วัสดุสำนักงาน']);
    }

    private function url(string $path = ''): string
    {
        return "/api/academies/{$this->academy->id}/budgets{$path}";
    }

    private function makeBudget(array $overrides = []): Budget
    {
        return Budget::create(array_merge([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year->id,
            'expense_category_id' => $this->utilities->id,
            'name' => 'งบค่าสาธารณูปโภค',
            'allocated_amount' => 10000,
            'spent_amount' => 0,
            'remaining_amount' => 10000,
            'start_date' => '2026-06-01',
            'end_date' => '2026-09-30',
            'is_active' => true,
        ], $overrides));
    }

    private function makeExpense(array $overrides = []): Expense
    {
        return Expense::create(array_merge([
            'academy_id' => $this->academy->id,
            'expense_category_id' => $this->utilities->id,
            'academic_year_id' => $this->year->id,
            'title' => 'ค่าไฟ',
            'amount' => 1500,
            'expense_date' => '2026-07-15',
            'status' => Expense::STATUS_APPROVED,
            'requested_by' => $this->owner->id,
        ], $overrides));
    }

    public function test_index_lists_budgets_with_category(): void
    {
        $this->makeBudget();

        $response = $this->actingAs($this->owner, 'api')->getJson($this->url());

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'งบค่าสาธารณูปโภค')
            ->assertJsonPath('data.0.is_active', true)
            ->assertJsonPath('data.0.category.name', 'ค่าน้ำค่าไฟ');
    }

    public function test_index_filters_by_category_and_active_flag(): void
    {
        $this->makeBudget(['name' => 'A']);
        $this->makeBudget(['name' => 'B', 'expense_category_id' => $this->supplies->id]);
        $this->makeBudget(['name' => 'C', 'is_active' => false]);

        $this->actingAs($this->owner, 'api')
            ->getJson($this->url()."?expense_category_id={$this->supplies->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'B');

        $this->actingAs($this->owner, 'api')
            ->getJson($this->url().'?is_active=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'C');
    }

    public function test_store_persists_with_real_columns(): void
    {
        $response = $this->actingAs($this->owner, 'api')->postJson($this->url(), [
            'academic_year_id' => $this->year->id,
            'expense_category_id' => $this->utilities->id,
            'name' => 'งบค่าน้ำค่าไฟ เทอม 1',
            'allocated_amount' => 25000,
            'period' => 'semester',
            'start_date' => '2026-05-16',
            'end_date' => '2026-10-10',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.category.name', 'ค่าน้ำค่าไฟ');

        $this->assertDatabaseHas('budgets', [
            'academy_id' => $this->academy->id,
            'expense_category_id' => $this->utilities->id,
            'name' => 'งบค่าน้ำค่าไฟ เทอม 1',
            'period' => 'semester',
            'is_active' => true,
        ]);

        $budget = Budget::firstOrFail();
        $this->assertSame('25000.00', $budget->remaining_amount);
        $this->assertSame('0.00', $budget->spent_amount);
    }

    public function test_store_without_category_and_period_uses_defaults(): void
    {
        $this->actingAs($this->owner, 'api')->postJson($this->url(), [
            'academic_year_id' => $this->year->id,
            'name' => 'งบรวม',
            'allocated_amount' => 1000,
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ])->assertCreated();

        $this->assertDatabaseHas('budgets', [
            'name' => 'งบรวม',
            'expense_category_id' => null,
            'period' => 'yearly',
            'is_active' => true,
        ]);
    }

    public function test_store_rejects_unknown_period(): void
    {
        $this->actingAs($this->owner, 'api')->postJson($this->url(), [
            'academic_year_id' => $this->year->id,
            'name' => 'งบ',
            'allocated_amount' => 1000,
            'period' => 'weekly',
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ])->assertStatus(422)->assertJsonValidationErrors('period');
    }

    public function test_show_returns_budget_with_its_expenses(): void
    {
        $budget = $this->makeBudget();
        $this->makeExpense(['title' => 'ค่าไฟ ก.ค.']);

        $response = $this->actingAs($this->owner, 'api')->getJson($this->url("/{$budget->id}"));

        $response->assertOk()
            ->assertJsonPath('data.id', $budget->id)
            ->assertJsonPath('data.category.name', 'ค่าน้ำค่าไฟ')
            ->assertJsonCount(1, 'data.expenses')
            ->assertJsonPath('data.expenses.0.title', 'ค่าไฟ ก.ค.');
    }

    public function test_update_accepts_expense_category_and_recomputes_remaining(): void
    {
        $budget = $this->makeBudget(['spent_amount' => 4000, 'remaining_amount' => 6000]);

        $this->actingAs($this->owner, 'api')->patchJson($this->url("/{$budget->id}"), [
            'expense_category_id' => $this->supplies->id,
            'allocated_amount' => 8000,
        ])->assertOk()->assertJsonPath('data.category.name', 'วัสดุสำนักงาน');

        $budget->refresh();
        $this->assertSame($this->supplies->id, $budget->expense_category_id);
        $this->assertSame('4000.00', $budget->remaining_amount);
    }

    public function test_update_rejects_end_date_before_existing_start_date(): void
    {
        $budget = $this->makeBudget();

        $this->actingAs($this->owner, 'api')->patchJson($this->url("/{$budget->id}"), [
            'end_date' => '2026-05-01',
        ])->assertStatus(422)->assertJsonValidationErrors('end_date');
    }

    public function test_close_then_activate_toggles_is_active(): void
    {
        $budget = $this->makeBudget();

        $this->actingAs($this->owner, 'api')->postJson($this->url("/{$budget->id}/close"))->assertOk();
        $this->assertFalse($budget->fresh()->is_active);

        $this->actingAs($this->owner, 'api')->postJson($this->url("/{$budget->id}/activate"))->assertOk();
        $this->assertTrue($budget->fresh()->is_active);
    }

    public function test_activate_and_close_refuse_when_already_in_that_state(): void
    {
        $active = $this->makeBudget(['name' => 'เปิดอยู่']);
        $closed = $this->makeBudget(['name' => 'ปิดอยู่', 'is_active' => false]);

        $this->actingAs($this->owner, 'api')->postJson($this->url("/{$active->id}/activate"))->assertStatus(400);
        $this->actingAs($this->owner, 'api')->postJson($this->url("/{$closed->id}/close"))->assertStatus(400);

        $this->assertTrue($active->fresh()->is_active);
        $this->assertFalse($closed->fresh()->is_active);
    }

    public function test_expenses_lists_only_matching_category_and_date_range(): void
    {
        $budget = $this->makeBudget();
        $inRange = $this->makeExpense(['title' => 'ในช่วง']);
        $this->makeExpense(['title' => 'วันแรก', 'expense_date' => '2026-06-01']);
        $this->makeExpense(['title' => 'วันสุดท้าย', 'expense_date' => '2026-09-30']);
        $this->makeExpense(['title' => 'ก่อนช่วง', 'expense_date' => '2026-05-31']);
        $this->makeExpense(['title' => 'หลังช่วง', 'expense_date' => '2026-10-01']);
        $this->makeExpense(['title' => 'หมวดอื่น', 'expense_category_id' => $this->supplies->id]);

        $other = Academy::factory()->create();
        $otherCategory = ExpenseCategory::create(['academy_id' => $other->id, 'name' => 'ค่าน้ำค่าไฟ']);
        $this->makeExpense(['title' => 'โรงเรียนอื่น', 'academy_id' => $other->id, 'expense_category_id' => $otherCategory->id, 'academic_year_id' => null]);

        $response = $this->actingAs($this->owner, 'api')->getJson($this->url("/{$budget->id}/expenses"));

        $response->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('data.0.category.name', 'ค่าน้ำค่าไฟ');

        $this->assertSame(
            ['วันสุดท้าย', 'ในช่วง', 'วันแรก'],
            collect($response->json('data'))->pluck('title')->all(),
            'เรียงตาม expense_date ล่าสุดก่อน'
        );

        $this->actingAs($this->owner, 'api')
            ->getJson($this->url("/{$budget->id}/expenses?status=pending"))
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $this->assertNotNull($inRange->fresh());
    }

    public function test_budget_without_category_covers_every_category(): void
    {
        $budget = $this->makeBudget(['expense_category_id' => null]);
        $this->makeExpense();
        $this->makeExpense(['expense_category_id' => $this->supplies->id]);

        $this->actingAs($this->owner, 'api')
            ->getJson($this->url("/{$budget->id}/expenses"))
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_update_spent_sums_only_approved_and_paid_expenses(): void
    {
        $budget = $this->makeBudget();
        $this->makeExpense(['amount' => 1000, 'status' => Expense::STATUS_APPROVED]);
        $this->makeExpense(['amount' => 500, 'status' => Expense::STATUS_PAID]);
        $this->makeExpense(['amount' => 9999, 'status' => Expense::STATUS_PENDING]);
        $this->makeExpense(['amount' => 9999, 'status' => Expense::STATUS_REJECTED]);

        $budget->updateSpent();

        $budget->refresh();
        $this->assertSame('1500.00', $budget->spent_amount);
        $this->assertSame('8500.00', $budget->remaining_amount);
    }

    public function test_destroy_deletes_budget_and_keeps_expenses(): void
    {
        $budget = $this->makeBudget();
        $expense = $this->makeExpense();

        $this->actingAs($this->owner, 'api')->deleteJson($this->url("/{$budget->id}"))->assertOk();

        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }

    public function test_summary_counts_active_inactive_and_exceeded(): void
    {
        $this->makeBudget(['name' => 'ปกติ', 'allocated_amount' => 1000, 'spent_amount' => 400, 'remaining_amount' => 600]);
        $this->makeBudget(['name' => 'เกินงบ', 'allocated_amount' => 1000, 'spent_amount' => 1200, 'remaining_amount' => -200]);
        $this->makeBudget(['name' => 'ปิดแล้ว', 'allocated_amount' => 1000, 'spent_amount' => 0, 'remaining_amount' => 1000, 'is_active' => false]);

        $response = $this->actingAs($this->owner, 'api')->getJson($this->url('/summary'));

        $response->assertOk()
            ->assertJsonPath('data.total_budgets', 3)
            ->assertJsonPath('data.by_status.active', 2)
            ->assertJsonPath('data.by_status.inactive', 1)
            ->assertJsonPath('data.by_status.exceeded', 1);

        $exceeded = collect($response->json('data.budgets_with_usage'))->firstWhere('name', 'เกินงบ');
        $this->assertTrue($exceeded['is_exceeded']);
        $this->assertTrue($exceeded['is_active']);
    }

    public function test_add_funds_increases_allocated_and_remaining(): void
    {
        $budget = $this->makeBudget(['spent_amount' => 10500, 'remaining_amount' => -500]);

        $this->actingAs($this->owner, 'api')
            ->postJson($this->url("/{$budget->id}/add-funds"), ['amount' => 2000])
            ->assertOk();

        $budget->refresh();
        $this->assertSame('12000.00', $budget->allocated_amount);
        $this->assertSame('1500.00', $budget->remaining_amount);
    }
}
