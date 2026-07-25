<?php

namespace App\Policies;

use App\Models\Academy;
use App\Models\AcademyPointWithdrawalRequest;
use App\Models\User;

class AcademyPointWithdrawalPolicy
{
    public function viewAny(User $user, Academy $Academy): bool
    {
        return $user->id === $Academy->user_id || $this->moderate($user);
    }

    public function view(User $user, AcademyPointWithdrawalRequest $withdrawal): bool
    {
        return $user->id === $withdrawal->requested_by || $user->id === $withdrawal->Academy->user_id || $this->moderate($user);
    }

    public function moderate(User $user): bool
    {
        return $user->isPlearndAdmin() || $user->isSuperAdmin();
    }
}
