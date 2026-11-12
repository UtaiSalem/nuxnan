<?php

namespace Tests\Feature\Academy;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Budget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * งบประมาณของโรงเรียน (ledger ภายใน ไม่ใช่แต้ม/กระเป๋าเงินของผู้ใช้)
 *
 * 1. ทุก route ที่มี `{budget}` ต้องหางบได้เฉพาะของ `{academy}` ใน URL เท่านั้น
 *    ผู้ที่มี `settings.manage` ของโรงเรียน A ต้องเอา id งบของโรงเรียน B มาใส่ใต้ URL ของ A ไม่ได้
 * 2. `transfer` ต้องได้งบตัวจริงจาก `{budget}` (เดิมพารามิเตอร์ชื่อ `$fromBudget` ไม่ตรงกับ route
 *    จึงได้ `Budget` เปล่าที่ยังไม่บันทึก) และ `to_budget_id` ต้องอยู่ในโรงเรียนเดียวกัน
 */
class BudgetAcademyScopingTest extends TestCase
{
    use RefreshDatabase;

    private Academy $academyA;

    private Academy $academyB;

    private User $managerA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academyA = Academy::factory()->create(['user_id' => User::factory()->create()->id]);
        $this->academyB = Academy::factory()->create(['user_id' => User::factory()->create()->id]);
        $this->managerA = $this->settingsManagerOf($this->academyA);
    }

    /** สมาชิกที่อนุมัติแล้วของโรงเรียน ที่มีสิทธิ์ settings.manage (ผ่านด่านของกลุ่ม budgets) */
    private function settingsManagerOf(Academy $academy): User
    {
        $user = User::factory()->create();
        $role = AcademyRole::create([
            'academy_id' => $academy->id,
            'name' => 'role-'.uniqid(),
            'display_name_th' => 'ผู้จัดการตั้งค่า',
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

    private function budgetIn(Academy $academy, float $allocated = 1000, string $name = 'งบทดสอบ'): Budget
    {
        $year = AcademicYear::firstOrCreate(
            ['academy_id' => $academy->id, 'name' => '2569'],
            ['start_date' => '2026-05-16', 'end_date' => '2027-03-31'],
        );

        return Budget::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'name' => $name,
            'allocated_amount' => $allocated,
            'spent_amount' => 0,
            'remaining_amount' => $allocated,
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ]);
    }

    private function assertBalance(Budget $budget, float $allocated, float $remaining): void
    {
        $fresh = $budget->fresh();

        $this->assertNotNull($fresh, "budget #{$budget->id} หายไป");
        $this->assertSame($allocated, (float) $fresh->allocated_amount, "allocated_amount ของ budget #{$budget->id}");
        $this->assertSame($remaining, (float) $fresh->remaining_amount, "remaining_amount ของ budget #{$budget->id}");
    }

    /**
     * ทุก route ที่มี `{budget}` ในกลุ่ม `{academy}/budgets`
     * `'__own__'` ใน payload = id งบของโรงเรียน A (ใส่ตอนรันเทสต์)
     */
    public static function budgetRoutes(): array
    {
        return [
            'show' => ['getJson', '', []],
            'update' => ['patchJson', '', ['name' => 'ถูกแก้ข้ามโรงเรียน', 'allocated_amount' => 1]],
            'destroy' => ['deleteJson', '', []],
            'activate' => ['postJson', '/activate', []],
            'close' => ['postJson', '/close', []],
            'addFunds' => ['postJson', '/add-funds', ['amount' => 500]],
            'transfer' => ['postJson', '/transfer', ['to_budget_id' => '__own__', 'amount' => 400]],
            'expenses' => ['getJson', '/expenses', []],
        ];
    }

    #[DataProvider('budgetRoutes')]
    public function test_budget_of_another_academy_is_not_found_under_this_academy(string $method, string $suffix, array $payload): void
    {
        $ownBudget = $this->budgetIn($this->academyA, 100, 'งบของ A');
        $foreignBudget = $this->budgetIn($this->academyB, 1000, 'งบของ B');

        $payload = array_map(fn ($v) => $v === '__own__' ? $ownBudget->id : $v, $payload);

        $response = $this->actingAs($this->managerA, 'api')
            ->{$method}("/api/academies/{$this->academyA->id}/budgets/{$foreignBudget->id}{$suffix}", $payload);

        $response->assertNotFound();

        // งบของ B ต้องไม่ถูกแตะ และงบของ A ต้องไม่ได้เงินเพิ่ม
        $this->assertBalance($foreignBudget, 1000, 1000);
        $this->assertSame('งบของ B', $foreignBudget->fresh()->name);
        $this->assertBalance($ownBudget, 100, 100);
    }

    public function test_manager_can_still_reach_own_academy_budget(): void
    {
        $budget = $this->budgetIn($this->academyA, 1000);

        $this->actingAs($this->managerA, 'api')
            ->postJson("/api/academies/{$this->academyA->id}/budgets/{$budget->id}/add-funds", ['amount' => 250])
            ->assertOk()
            ->assertJsonPath('data.id', $budget->id);

        $this->assertBalance($budget, 1250, 1250);
    }

    public function test_transfer_moves_funds_between_budgets_of_the_same_academy(): void
    {
        $from = $this->budgetIn($this->academyA, 1000, 'ต้นทาง');
        $to = $this->budgetIn($this->academyA, 200, 'ปลายทาง');
        $budgetCount = Budget::count();

        $response = $this->actingAs($this->managerA, 'api')
            ->postJson("/api/academies/{$this->academyA->id}/budgets/{$from->id}/transfer", [
                'to_budget_id' => $to->id,
                'amount' => 300,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.from.id', $from->id)
            ->assertJsonPath('data.to.id', $to->id);

        $this->assertBalance($from, 700, 700);
        $this->assertBalance($to, 500, 500);
        // ต้องไม่มีแถวงบผี (เดิม $fromBudget เป็น Budget เปล่า ถ้า save() จะกลายเป็นแถวใหม่)
        $this->assertSame($budgetCount, Budget::count());
    }

    public function test_transfer_rejects_destination_budget_from_another_academy(): void
    {
        $from = $this->budgetIn($this->academyA, 1000);
        $foreign = $this->budgetIn($this->academyB, 0);

        $this->actingAs($this->managerA, 'api')
            ->postJson("/api/academies/{$this->academyA->id}/budgets/{$from->id}/transfer", [
                'to_budget_id' => $foreign->id,
                'amount' => 300,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('to_budget_id');

        $this->assertBalance($from, 1000, 1000);
        $this->assertBalance($foreign, 0, 0);
    }

    public function test_transfer_rejects_the_source_budget_as_destination(): void
    {
        $from = $this->budgetIn($this->academyA, 1000);

        $this->actingAs($this->managerA, 'api')
            ->postJson("/api/academies/{$this->academyA->id}/budgets/{$from->id}/transfer", [
                'to_budget_id' => $from->id,
                'amount' => 300,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('to_budget_id');

        $this->assertBalance($from, 1000, 1000);
    }

    public function test_transfer_rejects_amount_above_remaining_balance(): void
    {
        $from = $this->budgetIn($this->academyA, 100);
        $to = $this->budgetIn($this->academyA, 0);

        $this->actingAs($this->managerA, 'api')
            ->postJson("/api/academies/{$this->academyA->id}/budgets/{$from->id}/transfer", [
                'to_budget_id' => $to->id,
                'amount' => 100.01,
            ])
            ->assertStatus(400);

        $this->assertBalance($from, 100, 100);
        $this->assertBalance($to, 0, 0);
    }
}
