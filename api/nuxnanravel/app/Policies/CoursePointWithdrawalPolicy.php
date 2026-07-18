<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\CoursePointWithdrawalRequest;
use App\Models\User;

class CoursePointWithdrawalPolicy
{
    public function viewAny(User $user, Course $course): bool
    {
        return $user->id === $course->user_id || $this->moderate($user);
    }

    public function view(User $user, CoursePointWithdrawalRequest $withdrawal): bool
    {
        return $user->id === $withdrawal->requested_by || $user->id === $withdrawal->course->user_id || $this->moderate($user);
    }

    public function moderate(User $user): bool
    {
        return $user->isPlearndAdmin() || $user->isSuperAdmin();
    }
}
