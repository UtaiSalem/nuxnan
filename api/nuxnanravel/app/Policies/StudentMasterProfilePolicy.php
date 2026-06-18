<?php

namespace App\Policies;

use App\Models\AcademyMember;
use App\Models\Student;
use App\Models\User;

class StudentMasterProfilePolicy
{
    /**
     * Determine whether the user can list students in the given academy.
     */
    public function viewAny(User $user, $academy_id): bool
    {
        return $this->isStaff($user, $academy_id);
    }

    /**
     * Determine whether the user can view the student profile.
     */
    public function view(User $user, Student $student): bool
    {
        if ($user->id === $student->user_id) {
            return true;
        }

        return $this->isStaff($user, $student->academy_id);
    }

    /**
     * Determine whether the user can update the student profile.
     * Owner edits flow through change-request approval inside the trait.
     */
    public function update(User $user, Student $student): bool
    {
        if ($user->id === $student->user_id) {
            return true;
        }

        return AcademyMember::where('user_id', $user->id)
            ->where('academy_id', $student->academy_id)
            ->whereIn('role', ['admin', 'director'])
            ->exists();
    }

    /**
     * Determine whether the user can approve change requests for an academy.
     */
    public function approveRequests(User $user, $academy_id): bool
    {
        return AcademyMember::where('user_id', $user->id)
            ->where('academy_id', $academy_id)
            ->whereIn('role', ['admin', 'director'])
            ->exists();
    }

    /**
     * Staff = admin/teacher/director in the given academy.
     */
    protected function isStaff(User $user, $academy_id): bool
    {
        return AcademyMember::where('user_id', $user->id)
            ->where('academy_id', $academy_id)
            ->whereIn('role', ['admin', 'teacher', 'director'])
            ->exists();
    }
}
