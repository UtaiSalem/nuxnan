<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\CourseDonate;
use App\Models\User;

class CourseDonatePolicy
{
    public function donate(?User $user, Course $course): bool
    {
        return $course->donationEnabled() && ($course->status != 2 || ($user && ($user->id === $course->user_id || $course->members()->whereKey($user->id)->exists())));
    }

    public function view(User $user, CourseDonate $donation): bool
    {
        return $user->id === $donation->donor_id || $user->id === $donation->course->user_id || $user->isSuperAdmin() || $user->is_plearnd_admin;
    }

    public function moderate(User $user, CourseDonate $donation): bool
    {
        return (bool) ($user->is_plearnd_admin || $user->isSuperAdmin());
    }
}
