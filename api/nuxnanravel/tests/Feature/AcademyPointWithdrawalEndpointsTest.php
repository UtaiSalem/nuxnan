<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyPointAccount;
use App\Models\AcademyPointTransaction;
use App\Models\PlearndAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyPointWithdrawalEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private function makeSetup(int $balance = 100000): array
    {
        $owner = User::factory()->create(['email_verified_at' => now(), 'pp' => 0]);
        $Academy = Academy::factory()->create(['user_id' => $owner->id]);
        $account = AcademyPointAccount::create(['academy_id' => $Academy->id, 'balance' => $balance, 'reserved_balance' => 0]);

        return compact('owner', 'Academy', 'account');
    }

    private function admin(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        PlearndAdmin::create(['user_id' => $user->id]);

        return $user;
    }

    public function test_owner_can_create_withdrawal_request(): void
    {
        ['owner' => $o, 'Academy' => $c] = $this->makeSetup();
        $this->actingAs($o, 'api')->postJson("/api/academies/{$c->id}/withdrawals", ['amount' => 24000, 'purpose' => 'books'])->assertCreated()->assertJsonPath('data.status', 'pending')->assertJsonStructure(['data' => ['id', 'academy_id', 'amount', 'status']]);
    }

    public function test_non_owner_cannot_create(): void
    {
        ['Academy' => $c] = $this->makeSetup();
        $this->actingAs(User::factory()->create(), 'api')->postJson("/api/academies/{$c->id}/withdrawals", ['amount' => 24000])->assertForbidden();
    }

    public function test_amount_below_minimum_returns_422(): void
    {
        ['owner' => $o, 'Academy' => $c] = $this->makeSetup();
        $this->actingAs($o, 'api')->postJson("/api/academies/{$c->id}/withdrawals", ['amount' => 23999])->assertUnprocessable();
    }

    public function test_owner_can_cancel_own_pending_request(): void
    {
        ['owner' => $o, 'Academy' => $c] = $this->makeSetup();
        $id = $this->actingAs($o, 'api')->postJson("/api/academies/{$c->id}/withdrawals", ['amount' => 24000])->json('data.id');
        $this->actingAs($o, 'api')->postJson("/api/academy-withdrawals/{$id}/cancel")->assertOk()->assertJsonPath('data.status', 'cancelled');
    }

    public function test_admin_can_review_and_approve(): void
    {
        ['owner' => $o, 'Academy' => $c] = $this->makeSetup(200000);
        $id = $this->actingAs($o, 'api')->postJson("/api/academies/{$c->id}/withdrawals", ['amount' => 24000])->json('data.id');
        $reviewer = $this->admin();
        $approver = $this->admin();
        $this->actingAs($reviewer, 'api')->patchJson("/api/plearnd-admin/academy-withdrawals/{$id}/review")->assertOk();
        $this->actingAs($approver, 'api')->patchJson("/api/plearnd-admin/academy-withdrawals/{$id}/approve")->assertOk()->assertJsonPath('data.status', 'approved');
    }

    public function test_admin_can_reject_and_reason_stored(): void
    {
        ['owner' => $o, 'Academy' => $c] = $this->makeSetup();
        $id = $this->actingAs($o, 'api')->postJson("/api/academies/{$c->id}/withdrawals", ['amount' => 24000])->json('data.id');
        $this->actingAs($this->admin(), 'api')->patchJson("/api/plearnd-admin/academy-withdrawals/{$id}/reject", ['reason' => 'invalid'])->assertOk()->assertJsonPath('data.rejection_reason', 'invalid');
    }

    public function test_maker_checker_blocks_same_approver_on_high_amount(): void
    {
        config(['wallet.Academy_withdraw.maker_checker_threshold' => 5000]);
        ['owner' => $o, 'Academy' => $c] = $this->makeSetup(200000);
        $id = $this->actingAs($o, 'api')->postJson("/api/academies/{$c->id}/withdrawals", ['amount' => 30000])->json('data.id');
        $a = $this->admin();
        $this->actingAs($a, 'api')->patchJson("/api/plearnd-admin/academy-withdrawals/{$id}/review");
        $this->actingAs($a, 'api')->patchJson("/api/plearnd-admin/academy-withdrawals/{$id}/approve")->assertStatus(422);
    }

    public function test_admin_mark_paid_credits_pp(): void
    {
        config(['wallet.Academy_withdraw.maker_checker_threshold' => 5000]);
        ['owner' => $o, 'Academy' => $c] = $this->makeSetup(200000);
        $id = $this->actingAs($o, 'api')->postJson("/api/academies/{$c->id}/withdrawals", ['amount' => 30000])->json('data.id');
        $reviewer = $this->admin();
        $approver = $this->admin();
        $payer = $this->admin();
        $this->actingAs($reviewer, 'api')->patchJson("/api/plearnd-admin/academy-withdrawals/{$id}/review");
        $this->actingAs($approver, 'api')->patchJson("/api/plearnd-admin/academy-withdrawals/{$id}/approve");
        $this->actingAs($payer, 'api')->patch("/api/plearnd-admin/academy-withdrawals/{$id}/mark-paid", ['payment_reference' => 'REF-1'])->assertOk();
        $this->assertSame(30000, (int) $o->fresh()->pp);
        $this->assertSame(1, AcademyPointTransaction::where('type', 'withdrawal_paid')->count());
    }
}
