<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\WalletService;
use Illuminate\Console\Command;

class BackfillWalletOpeningBalance extends Command
{
    protected $signature = 'wallet:backfill-opening-balance
        {--users=* : user ids to backfill; default 117,412}';

    protected $description = 'Record opening-balance entries for selected user wallets.';

    public function handle(WalletService $walletService): int
    {
        $userIds = $this->option('users') ?: [117, 412];
        $rows = [];

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (! $user) {
                $rows[] = [$userId, 'user not found'];

                continue;
            }

            $rows[] = [$userId, $walletService->recordOpeningBalance($user) ? 'baselined' : 'already balanced'];
        }

        $this->table(['user_id', 'result'], $rows);

        return self::SUCCESS;
    }
}
