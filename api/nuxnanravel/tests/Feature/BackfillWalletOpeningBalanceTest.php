<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BackfillWalletOpeningBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_creates_opening_balance_row_for_unbalanced_user(): void
    {
        $user = User::factory()->create(['wallet' => 5.00, 'locked_balance' => 0]);
        Artisan::call('wallet:backfill-opening-balance', ['--users' => [$user->id]]);

        $this->assertDatabaseHas('wallet_transactions', ['user_id' => $user->id, 'transaction_type' => 'opening_balance', 'amount' => '5.00']);
    }

    public function test_backfill_is_idempotent(): void
    {
        $user = User::factory()->create(['wallet' => 5.00, 'locked_balance' => 0]);
        Artisan::call('wallet:backfill-opening-balance', ['--users' => [$user->id]]);
        Artisan::call('wallet:backfill-opening-balance', ['--users' => [$user->id]]);

        $this->assertSame(1, $user->walletTransactions()->where('transaction_type', 'opening_balance')->count());
    }
}
