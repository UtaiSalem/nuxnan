<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ลบหมวดรายจ่ายของโรงเรียนตัวเอง
 *
 * `ExpenseCategory::expenses()` เคยผูกด้วยคอลัมน์ `category_id` ที่ไม่มีอยู่จริงในตาราง `expenses`
 * (ของจริงคือ `expense_category_id` — ดู migration 2026_02_04_100002_create_finance_tables)
 * ⇒ `destroyCategory` ที่เรียก `$category->expenses()->count()` ตอบ 500 ทุกครั้ง
 * แม้จะเป็นหมวดของโรงเรียนตัวเองและไม่มีรายจ่ายผูกอยู่เลย
 *
 * หมายเหตุ: กลุ่ม route การเงินอยู่หลัง `academy.permission:settings.manage` — เทสต์นี้ยิงด้วย
 * เจ้าของโรงเรียน (academy admin) ซึ่งผ่านด่านนั้นเสมอ
 */
class ExpenseCategoryDestroyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Academy, 1: User}
     */
    private function academyWithOwner(): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);

        return [$academy, $owner];
    }

    private function categoryOf(Academy $academy, string $name): ExpenseCategory
    {
        return ExpenseCategory::create([
            'academy_id' => $academy->id,
            'name' => $name,
            'is_active' => true,
        ]);
    }

    public function test_owner_can_delete_an_unused_category_of_own_academy(): void
    {
        [$academy, $owner] = $this->academyWithOwner();
        $category = $this->categoryOf($academy, 'ค่าน้ำค่าไฟ');

        $response = $this->actingAs($owner, 'api')
            ->deleteJson("/api/academies/{$academy->id}/expenses/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('expense_categories', ['id' => $category->id]);
    }

    public function test_deleting_a_category_that_has_an_expense_is_rejected_and_keeps_the_row(): void
    {
        [$academy, $owner] = $this->academyWithOwner();
        $category = $this->categoryOf($academy, 'ค่าวัสดุสำนักงาน');
        Expense::create([
            'academy_id' => $academy->id,
            'expense_category_id' => $category->id,
            'title' => 'ซื้อกระดาษ A4',
            'amount' => 1200,
            'expense_date' => '2026-11-01',
            'status' => Expense::STATUS_PENDING,
            'requested_by' => $owner->id,
        ]);

        $response = $this->actingAs($owner, 'api')
            ->deleteJson("/api/academies/{$academy->id}/expenses/categories/{$category->id}");

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
        $this->assertStringContainsString('ไม่สามารถลบได้', $response->json('message'));

        $this->assertDatabaseHas('expense_categories', ['id' => $category->id]);
    }
}
