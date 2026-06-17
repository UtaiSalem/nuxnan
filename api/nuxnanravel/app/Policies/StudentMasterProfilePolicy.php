<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StudentMasterProfilePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, $academy_id): bool
    {
        return $user->academyMembers()
                    ->where('academy_id', $academy_id)
                    ->whereIn('role', ['admin', 'teacher', 'director'])
                    ->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Student $student): bool
    {
        // Student can view their own profile
        if ($user->id === $student->user_id) {
            return true;
        }

        // Staff can view if they are in the same academy
        return $user->academyMembers()
                    ->where('academy_id', $student->academy_id)
                    ->whereIn('role', ['admin', 'teacher', 'director'])
                    ->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Student $student): bool
    {
        // Student can update their own profile (goes through change request if restricted)
        if ($user->id === $student->user_id) {
            return true;
        }

        // Only admin/director can directly update any student
        return $user->academyMembers()
                    ->where('academy_id', $student->academy_id)
                    ->whereIn('role', ['admin', 'director'])
                    ->exists();
    }

    /**
     * Determine whether the user can approve change requests.
     */
    public function approveRequests(User $user, $academy_id): bool
    {
        return $user->academyMembers()
                    ->where('academy_id', $academy_id)
                    ->whereIn('role', ['admin', 'director'])
                    ->exists();
    }
}
