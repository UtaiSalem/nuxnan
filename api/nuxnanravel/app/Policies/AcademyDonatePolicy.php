<?php

namespace App\Policies;

use App\Models\Academy;
use App\Models\AcademyDonate;
use App\Models\User;

class AcademyDonatePolicy
{
    public function donate(?User $user, Academy $academy): bool
    {
        return $academy->donationEnabled() && ($user === null || $user->id !== $academy->user_id);
    }

    public function view(User $user, AcademyDonate $donation): bool
    {
        return $user->id === $donation->donor_id || $user->id === $donation->academy->user_id || $this->admin($user);
    }

    public function moderate(User $user, ?AcademyDonate $donation = null): bool
    {
        return $this->admin($user);
    }

    private function admin(User $user): bool
    {
        return (bool) ($user->is_plearnd_admin || $user->isSuperAdmin());
    }
}
