<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WalletTransaction;

class WithdrawalPolicy
{
    public function view(User $user, WalletTransaction $transaction): bool
    {
        return $user->id === $transaction->user_id || $user->isSuperAdmin() || $user->hasAnyRole(['ADMIN', 'MODERATOR']) || $user->isPlearndAdmin();
    }

    public function approve(User $user, WalletTransaction $transaction): bool
    {
        return ($user->isSuperAdmin() || $user->hasRole('ADMIN')) && $transaction->transaction_type === 'withdraw';
    }

    public function reject(User $user, WalletTransaction $transaction): bool
    {
        return $this->approve($user, $transaction);
    }
}
