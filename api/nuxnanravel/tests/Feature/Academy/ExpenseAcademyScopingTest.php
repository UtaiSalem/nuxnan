<?php

namespace Tests\Feature\Academy;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * รายจ่าย/หมวดรายจ่ายต้องอยู่ในโรงเรียนของ URL เสมอ
 *
 * route `{academy}/expenses/{expense}` และ `{academy}/expenses/categories/{category}` ไม่ได้ผูก
 * `{expense}`/`{category}` เข้ากับ `{academy}` ⇒ เจ้าของ (หรือผู้ถือ settings.manage) ของโรงเรียน A
 * เคยอ่าน/แก้/อนุมัติ/ปฏิเสธ/ทำเครื่องหมายจ่าย/ลบ รายจ่ายและหมวดของโรงเรียน B ได้ด้วยการเปลี่ยนเลข id (IDOR)
 *
 * ชุดหลัง (`academic_year_id` / `parent_id` / `sort_by`) คุม id ที่ "ส่งเข้ามาใน body" ให้อยู่ในโรงเรียนของ URL
 * ด้วย — ก่อนหน้านี้ผูกรายจ่ายเข้าปีการศึกษาของโรงเรียนอื่น หรือซ้อนหมวดใต้หมวดของโรงเรียนอื่นได้
 */
class ExpenseAcademyScopingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Academy, 1: User, 2: ExpenseCategory, 3: Expense}
     */
    private function academyWithExpense(string $title, string $status = Expense::STATUS_PENDING): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $category = ExpenseCategory::create([
            'academy_id' => $academy->id,
            'name' => "หมวดของ {$title}",
            'is_active' => true,
        ]);
        $expense = Expense::create([
            'academy_id' => $academy->id,
            'expense_category_id' => $category->id,
            'title' => $title,
            'amount' => 1000,
            'expense_date' => '2026-09-01',
            'status' => $status,
            'requested_by' => $owner->id,
        ]);

        return [$academy, $owner, $category, $expense];
    }

    private function settingsManagerOf(Academy $academy): User
    {
        $user = User::factory()->create();
        $role = AcademyRole::create([
            'academy_id' => $academy->id,
            'name' => 'role-'.uniqid(),
            'display_name_th' => 'ผู้ดูแลการตั้งค่า',
            'permissions' => ['settings.manage'],
        ]);
        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $role->id,
            'status' => 2,
        ]);

        return $user;
    }

    /**
     * ทุกเส้นที่มี `{expense}` หรือ `{category}` — แยกเป็นเคสละเส้น เพื่อให้รู้ว่าเส้นไหนหลุดถ้าด่านหาย
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: array<string, mixed>}>
     */
    public static function crossAcademyRoutes(): array
    {
        // [HTTP method, เป้าหมาย, ท้าย path, payload]
        // เป้าหมาย approved-expense ใช้กับ mark-paid เพราะถ้าด่านหาย เส้นนี้จะเปลี่ยนสถานะได้จริง (ไม่ใช่แค่ตอบ 400)
        return [
            'show' => ['get', 'expense', '', []],
            'update' => ['patch', 'expense', '', ['title' => 'ยึดแล้ว', 'amount' => 1]],
            'approve' => ['post', 'expense', '/approve', []],
            'reject' => ['post', 'expense', '/reject', ['reason' => 'ไม่ให้']],
            'mark-paid' => ['post', 'approved-expense', '/mark-paid', []],
            'destroy' => ['delete', 'expense', '', []],
            'update category' => ['patch', 'category', '', ['name' => 'ยึดแล้ว', 'is_active' => false]],
            'destroy category' => ['delete', 'category', '', []],
        ];
    }

    #[DataProvider('crossAcademyRoutes')]
    public function test_owner_gets_404_on_another_academys_record(string $method, string $target, string $suffix, array $payload): void
    {
        [$mine, $owner] = $this->academyWithExpense('ของฉัน');

        $this->assertCrossAcademyRequestIsNotFound($owner, $mine, $method, $target, $suffix, $payload);
    }

    #[DataProvider('crossAcademyRoutes')]
    public function test_settings_manager_gets_404_on_another_academys_record(string $method, string $target, string $suffix, array $payload): void
    {
        [$mine] = $this->academyWithExpense('ของฉัน');
        $manager = $this->settingsManagerOf($mine);

        $this->assertCrossAcademyRequestIsNotFound($manager, $mine, $method, $target, $suffix, $payload);
    }

    private function assertCrossAcademyRequestIsNotFound(User $actor, Academy $mine, string $method, string $target, string $suffix, array $payload): void
    {
        $status = $target === 'approved-expense' ? Expense::STATUS_APPROVED : Expense::STATUS_PENDING;
        [, , $theirCategory, $theirExpense] = $this->academyWithExpense('ของคนอื่น', $status);

        $uri = $target === 'category'
            ? "/api/academies/{$mine->id}/expenses/categories/{$theirCategory->id}{$suffix}"
            : "/api/academies/{$mine->id}/expenses/{$theirExpense->id}{$suffix}";

        $this->actingAs($actor, 'api')
            ->json(strtoupper($method), $uri, $payload)
            ->assertNotFound()
            ->assertJsonMissing(['title' => 'ของคนอื่น']);

        // ข้อมูลของโรงเรียนอื่นต้องไม่ถูกแตะเลย
        $theirExpense = $theirExpense->fresh();
        $this->assertNotNull($theirExpense, 'expense of another academy was deleted');
        $this->assertSame('ของคนอื่น', $theirExpense->title);
        $this->assertSame('1000.00', $theirExpense->amount);
        $this->assertSame($status, $theirExpense->status);
        $this->assertNull($theirExpense->approved_by);

        $theirCategory = $theirCategory->fresh();
        $this->assertNotNull($theirCategory, 'category of another academy was deleted');
        $this->assertSame('หมวดของ ของคนอื่น', $theirCategory->name);
        $this->assertTrue($theirCategory->is_active);
    }

    public function test_store_rejects_another_academys_category(): void
    {
        [$mine, $owner] = $this->academyWithExpense('ของฉัน');
        [, , $theirCategory] = $this->academyWithExpense('ของคนอื่น');

        $this->actingAs($owner, 'api')->postJson("/api/academies/{$mine->id}/expenses", [
            'expense_category_id' => $theirCategory->id,
            'title' => 'รายจ่ายปลอม',
            'amount' => 100,
            'expense_date' => '2026-09-01',
        ])->assertStatus(422)->assertJsonValidationErrors('expense_category_id');

        $this->assertDatabaseMissing('expenses', ['title' => 'รายจ่ายปลอม']);
    }

    public function test_update_rejects_another_academys_category(): void
    {
        [$mine, $owner, $myCategory, $myExpense] = $this->academyWithExpense('ของฉัน');
        [, , $theirCategory] = $this->academyWithExpense('ของคนอื่น');

        $this->actingAs($owner, 'api')
            ->patchJson("/api/academies/{$mine->id}/expenses/{$myExpense->id}", [
                'expense_category_id' => $theirCategory->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('expense_category_id');

        $this->assertSame($myCategory->id, $myExpense->fresh()->expense_category_id);
    }

    public function test_owner_still_manages_own_academys_expense_and_category(): void
    {
        [$academy, $owner, $category, $expense] = $this->academyWithExpense('ของฉัน');
        $other = ExpenseCategory::create([
            'academy_id' => $academy->id,
            'name' => 'หมวดที่สอง',
            'is_active' => true,
        ]);

        $base = "/api/academies/{$academy->id}/expenses/{$expense->id}";

        $this->actingAs($owner, 'api')->getJson($base)
            ->assertOk()
            ->assertJsonPath('data.id', $expense->id);

        $this->actingAs($owner, 'api')->patchJson($base, ['title' => 'แก้แล้ว', 'expense_category_id' => $other->id])
            ->assertOk()
            ->assertJsonPath('data.title', 'แก้แล้ว')
            ->assertJsonPath('data.expense_category_id', $other->id);

        $this->actingAs($owner, 'api')->postJson("{$base}/approve")->assertOk();
        $this->actingAs($owner, 'api')->postJson("{$base}/mark-paid")->assertOk();
        $this->assertSame(Expense::STATUS_PAID, $expense->fresh()->status);

        $this->actingAs($owner, 'api')
            ->patchJson("/api/academies/{$academy->id}/expenses/categories/{$category->id}", ['name' => 'หมวดใหม่'])
            ->assertOk()
            ->assertJsonPath('data.name', 'หมวดใหม่');
    }

    public function test_owner_can_reject_and_delete_own_pending_expenses(): void
    {
        [$academy, $owner, $category, $toReject] = $this->academyWithExpense('จะปฏิเสธ');
        $toDelete = Expense::create([
            'academy_id' => $academy->id,
            'expense_category_id' => $category->id,
            'title' => 'จะลบ',
            'amount' => 50,
            'expense_date' => '2026-09-02',
            'status' => Expense::STATUS_PENDING,
            'requested_by' => $owner->id,
        ]);

        $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$academy->id}/expenses/{$toReject->id}/reject", ['reason' => 'ไม่มีใบเสร็จ'])
            ->assertOk();
        $this->assertSame(Expense::STATUS_REJECTED, $toReject->fresh()->status);

        $this->actingAs($owner, 'api')
            ->deleteJson("/api/academies/{$academy->id}/expenses/{$toDelete->id}")
            ->assertOk();
        $this->assertDatabaseMissing('expenses', ['id' => $toDelete->id]);
    }

    // =====================================================
    // id ใน body: academic_year_id / parent_id ต้องเป็นของโรงเรียนใน URL
    // =====================================================

    private function academicYearFor(Academy $academy, string $name = '2568'): AcademicYear
    {
        return AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => $name,
            'start_date' => '2026-05-01',
            'end_date' => '2027-03-31',
        ]);
    }

    public function test_store_rejects_another_academys_academic_year(): void
    {
        [$mine, $owner, $myCategory] = $this->academyWithExpense('ของฉัน');
        [$theirs] = $this->academyWithExpense('ของคนอื่น');
        $theirYear = $this->academicYearFor($theirs);

        $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$mine->id}/expenses", [
                'expense_category_id' => $myCategory->id,
                'academic_year_id' => $theirYear->id,
                'title' => 'รายจ่ายข้ามปีการศึกษา',
                'amount' => 100,
                'expense_date' => '2026-09-01',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('academic_year_id');

        $this->assertDatabaseMissing('expenses', ['title' => 'รายจ่ายข้ามปีการศึกษา']);
    }

    public function test_update_rejects_another_academys_academic_year(): void
    {
        [$mine, $owner, , $myExpense] = $this->academyWithExpense('ของฉัน');
        [$theirs] = $this->academyWithExpense('ของคนอื่น');
        $theirYear = $this->academicYearFor($theirs);

        $this->actingAs($owner, 'api')
            ->patchJson("/api/academies/{$mine->id}/expenses/{$myExpense->id}", [
                'academic_year_id' => $theirYear->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('academic_year_id');

        $this->assertNull($myExpense->fresh()->academic_year_id);
    }

    public function test_store_and_update_accept_own_academys_academic_year(): void
    {
        [$mine, $owner, $myCategory, $myExpense] = $this->academyWithExpense('ของฉัน');
        $firstYear = $this->academicYearFor($mine, '2568');
        $secondYear = $this->academicYearFor($mine, '2569');

        $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$mine->id}/expenses", [
                'expense_category_id' => $myCategory->id,
                'academic_year_id' => $firstYear->id,
                'title' => 'รายจ่ายปีนี้',
                'amount' => 100,
                'expense_date' => '2026-09-01',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.academic_year_id', $firstYear->id);

        $this->assertDatabaseHas('expenses', [
            'academy_id' => $mine->id,
            'title' => 'รายจ่ายปีนี้',
            'academic_year_id' => $firstYear->id,
        ]);

        $this->actingAs($owner, 'api')
            ->patchJson("/api/academies/{$mine->id}/expenses/{$myExpense->id}", [
                'academic_year_id' => $secondYear->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.academic_year_id', $secondYear->id);

        $this->assertSame($secondYear->id, $myExpense->fresh()->academic_year_id);
    }

    public function test_store_category_rejects_another_academys_parent(): void
    {
        [$mine, $owner] = $this->academyWithExpense('ของฉัน');
        [, , $theirCategory] = $this->academyWithExpense('ของคนอื่น');

        $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$mine->id}/expenses/categories", [
                'name' => 'หมวดลูกข้ามโรงเรียน',
                'parent_id' => $theirCategory->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('parent_id');

        $this->assertDatabaseMissing('expense_categories', ['name' => 'หมวดลูกข้ามโรงเรียน']);
        $this->assertSame(0, $theirCategory->children()->count());
    }

    public function test_update_category_rejects_another_academys_parent(): void
    {
        [$mine, $owner, $myCategory] = $this->academyWithExpense('ของฉัน');
        [, , $theirCategory] = $this->academyWithExpense('ของคนอื่น');

        $this->actingAs($owner, 'api')
            ->patchJson("/api/academies/{$mine->id}/expenses/categories/{$myCategory->id}", [
                'parent_id' => $theirCategory->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('parent_id');

        $this->assertNull($myCategory->fresh()->parent_id);
        $this->assertSame(0, $theirCategory->children()->count());
    }

    public function test_update_category_rejects_itself_as_parent(): void
    {
        [$mine, $owner, $myCategory] = $this->academyWithExpense('ของฉัน');

        $this->actingAs($owner, 'api')
            ->patchJson("/api/academies/{$mine->id}/expenses/categories/{$myCategory->id}", [
                'parent_id' => $myCategory->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('parent_id');

        $this->assertNull($myCategory->fresh()->parent_id);
    }

    public function test_category_accepts_own_academys_parent(): void
    {
        [$mine, $owner, $myCategory] = $this->academyWithExpense('ของฉัน');

        $this->actingAs($owner, 'api')
            ->postJson("/api/academies/{$mine->id}/expenses/categories", [
                'name' => 'หมวดลูกในบ้าน',
                'parent_id' => $myCategory->id,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.parent_id', $myCategory->id);

        $sibling = ExpenseCategory::create([
            'academy_id' => $mine->id,
            'name' => 'หมวดพี่น้อง',
            'is_active' => true,
        ]);

        $this->actingAs($owner, 'api')
            ->patchJson("/api/academies/{$mine->id}/expenses/categories/{$sibling->id}", [
                'parent_id' => $myCategory->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.parent_id', $myCategory->id);

        $this->assertSame($myCategory->id, $sibling->fresh()->parent_id);
        $this->assertSame(2, $myCategory->children()->count());
    }

    // =====================================================
    // index: sort_by / sort_order ต้องเป็นค่าที่อนุญาต (เดิมหลุดลง orderBy ตรง ๆ → 500)
    // =====================================================

    public function test_index_rejects_unknown_sort_column(): void
    {
        [$mine, $owner] = $this->academyWithExpense('ของฉัน');

        $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$mine->id}/expenses?sort_by=no_such_column")
            ->assertStatus(422)
            ->assertJsonValidationErrors('sort_by');

        $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$mine->id}/expenses?sort_by=".urlencode('amount) -- '))
            ->assertStatus(422)
            ->assertJsonValidationErrors('sort_by');
    }

    public function test_index_rejects_unknown_sort_order(): void
    {
        [$mine, $owner] = $this->academyWithExpense('ของฉัน');

        $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$mine->id}/expenses?sort_order=".urlencode('asc, amount desc'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('sort_order');
    }

    public function test_index_sorts_by_whitelisted_column(): void
    {
        [$mine, $owner, $category] = $this->academyWithExpense('ของฉัน');
        Expense::create([
            'academy_id' => $mine->id,
            'expense_category_id' => $category->id,
            'title' => 'ถูกกว่า',
            'amount' => 1,
            'expense_date' => '2026-09-02',
            'status' => Expense::STATUS_PENDING,
            'requested_by' => $owner->id,
        ]);

        $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$mine->id}/expenses?sort_by=amount&sort_order=asc")
            ->assertOk()
            ->assertJsonPath('data.0.title', 'ถูกกว่า')
            ->assertJsonPath('data.1.title', 'ของฉัน');

        $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$mine->id}/expenses")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
