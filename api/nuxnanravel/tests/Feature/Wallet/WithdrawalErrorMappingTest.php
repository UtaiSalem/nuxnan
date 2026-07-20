<?php

namespace Tests\Feature\Wallet;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class WithdrawalErrorMappingTest extends TestCase
{
    use RefreshDatabase;

    private function rejectWith(\Throwable $exception): TestResponse
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');
        $token = JWTAuth::fromUser($admin);
        $owner = User::factory()->create(['wallet' => 5000]);
        $transaction = WalletTransaction::create([
            'user_id' => $owner->id,
            'transaction_type' => 'withdraw',
            'amount' => 100,
            'balance_before' => 5000,
            'balance_after' => 4895,
            'currency' => 'THB',
            'status' => 'pending',
            'metadata' => ['destination_type' => 'bank_transfer'],
            'version' => 1,
            'fee' => 5,
            'net_amount' => 95,
        ]);

        $service = \Mockery::mock(WalletService::class);
        $service->shouldReceive('rejectWithdrawal')->once()->andThrow($exception);
        $this->app->instance(WalletService::class, $service);

        return $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/admin/wallet/withdrawals/{$transaction->id}/reject", ['reason' => 'test']);
    }

    public function test_database_errors_are_not_masked_as_conflicts(): void
    {
        $exception = new QueryException('mysql', 'insert into wallet_transactions', [], new \PDOException('duplicate key'));

        $this->rejectWith($exception)->assertStatus(500);
    }

    public function test_runtime_errors_are_mapped_to_conflicts(): void
    {
        $this->rejectWith(new \RuntimeException('concurrent update'))
            ->assertStatus(409)
            ->assertJson(['message' => 'มีการแก้ไขรายการพร้อมกัน กรุณาลองใหม่']);
    }

    public function test_domain_errors_are_mapped_to_unprocessable_entity(): void
    {
        $this->rejectWith(new \DomainException('msg'))
            ->assertStatus(422)
            ->assertJson(['message' => 'msg']);
    }
}
