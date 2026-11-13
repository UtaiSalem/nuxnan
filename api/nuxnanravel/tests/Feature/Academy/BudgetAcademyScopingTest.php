<?php

namespace Tests\Feature\Academy;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Budget;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * งบประมาณต้องอยู่ในโรงเรียนของ URL เสมอ
 *
 * route `{academy}/budgets/{budget}` ไม่ได้ผูก `{budget}` เข้ากับ `{academy}` ⇒ เจ้าของโรงเรียน A
 * เคยอ่าน/แก้/ปิด/ลบ/โอนงบของโรงเรียน B ได้ด้วยการเปลี่ยนเลข id (IDOR)
 */
class BudgetAcademyScopingTest extends TestCase
{
    use RefreshDatabase;

    private function academyWithBudget(string $name, float $allocated = 10000): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $year = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2569',
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ]);
        $budget = Budget::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'name' => $name,
            'allocated_amount' => $allocated,
            'spent_amount' => 0,
            'remaining_amount' => $allocated,
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ]);

        return [$academy, $owner, $budget, $year];
    }

    public function test_owner_cannot_reach_another_academys_budget(): void
    {
        [$mine, $owner] = $this->academyWithBudget('งบของฉัน');
        [, , $theirs] = $this->academyWithBudget('งบของคนอื่น');

        $base = "/api/academies/{$mine->id}/budgets/{$theirs->id}";

        $this->actingAs($owner, 'api')->getJson($base)->assertNotFound();
        $this->actingAs($owner, 'api')->getJson("{$base}/expenses")->assertNotFound();
        $this->actingAs($owner, 'api')->patchJson($base, ['name' => 'ยึดแล้ว'])->assertNotFound();
        $this->actingAs($owner, 'api')->postJson("{$base}/close")->assertNotFound();
        $this->actingAs($owner, 'api')->postJson("{$base}/activate")->assertNotFound();
        $this->actingAs($owner, 'api')->postJson("{$base}/add-funds", ['amount' => 5])->assertNotFound();
        $this->actingAs($owner, 'api')->postJson("{$base}/transfer", ['to_budget_id' => $theirs->id, 'amount' => 5])->assertNotFound();
        $this->actingAs($owner, 'api')->deleteJson($base)->assertNotFound();

        $theirs->refresh();
        $this->assertSame('งบของคนอื่น', $theirs->name);
        $this->assertTrue($theirs->is_active);
        $this->assertSame('10000.00', $theirs->allocated_amount);
    }

    public function test_transfer_moves_funds_between_budgets_of_the_same_academy(): void
    {
        [$academy, $owner, $from, $year] = $this->academyWithBudget('ต้นทาง', 10000);
        $to = Budget::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'name' => 'ปลายทาง',
            'allocated_amount' => 1000,
            'spent_amount' => 0,
            'remaining_amount' => 1000,
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ]);

        $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/budgets/{$from->id}/transfer", [
                'to_budget_id' => $to->id,
                'amount' => 2500,
            ])
            ->assertOk()
            ->assertJsonPath('data.from.id', $from->id)
            ->assertJsonPath('data.to.id', $to->id);

        $this->assertSame('7500.00', $from->fresh()->allocated_amount);
        $this->assertSame('7500.00', $from->fresh()->remaining_amount);
        $this->assertSame('3500.00', $to->fresh()->allocated_amount);
        $this->assertSame('3500.00', $to->fresh()->remaining_amount);
    }

    public function test_transfer_cannot_move_funds_into_another_academys_budget(): void
    {
        [$mine, $owner, $from] = $this->academyWithBudget('ของฉัน', 10000);
        [, , $theirs] = $this->academyWithBudget('ของคนอื่น', 1000);

        $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$mine->id}/budgets/{$from->id}/transfer", [
                'to_budget_id' => $theirs->id,
                'amount' => 2500,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('to_budget_id');

        $this->assertSame('10000.00', $from->fresh()->allocated_amount);
        $this->assertSame('1000.00', $theirs->fresh()->allocated_amount);
    }

    public function test_transfer_to_itself_is_rejected(): void
    {
        [$academy, $owner, $from] = $this->academyWithBudget('ต้นทาง', 10000);

        $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/budgets/{$from->id}/transfer", [
                'to_budget_id' => $from->id,
                'amount' => 100,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('to_budget_id');

        $this->assertSame('10000.00', $from->fresh()->allocated_amount);
    }

    public function test_transfer_refuses_more_than_remaining(): void
    {
        [$academy, $owner, $from, $year] = $this->academyWithBudget('ต้นทาง', 1000);
        $to = Budget::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'name' => 'ปลายทาง',
            'allocated_amount' => 0,
            'spent_amount' => 0,
            'remaining_amount' => 0,
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ]);

        $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/budgets/{$from->id}/transfer", [
                'to_budget_id' => $to->id,
                'amount' => 1000.01,
            ])
            ->assertStatus(400);

        $this->assertSame('1000.00', $from->fresh()->allocated_amount);
        $this->assertSame('0.00', $to->fresh()->allocated_amount);
    }

    public function test_store_rejects_category_and_academic_year_of_another_academy(): void
    {
        [$mine, $owner] = $this->academyWithBudget('ของฉัน');
        [$theirs, , , $theirYear] = $this->academyWithBudget('ของคนอื่น');
        $theirCategory = ExpenseCategory::create(['academy_id' => $theirs->id, 'name' => 'หมวดของคนอื่น']);

        $this->actingAs($owner, 'api')->postJson("/api/academies/{$mine->id}/budgets", [
            'academic_year_id' => $theirYear->id,
            'expense_category_id' => $theirCategory->id,
            'name' => 'งบปลอม',
            'allocated_amount' => 100,
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ])->assertStatus(422)->assertJsonValidationErrors(['academic_year_id', 'expense_category_id']);

        $this->assertDatabaseMissing('budgets', ['name' => 'งบปลอม']);
    }

    public function test_index_and_summary_only_show_own_academy(): void
    {
        [$mine, $owner] = $this->academyWithBudget('ของฉัน');
        $this->academyWithBudget('ของคนอื่น');

        $this->actingAs($owner, 'api')->getJson("/api/academies/{$mine->id}/budgets")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'ของฉัน');

        $this->actingAs($owner, 'api')->getJson("/api/academies/{$mine->id}/budgets/summary")
            ->assertOk()
            ->assertJsonPath('data.total_budgets', 1);
    }
}
