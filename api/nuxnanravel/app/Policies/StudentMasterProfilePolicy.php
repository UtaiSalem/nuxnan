<?php

namespace App\Policies;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\ClassroomMember;
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

        if ($this->isAcademyOwner($user, $student->academy_id)) {
            return true;
        }

        // ครูประจำชั้น/ครูประจำชั้นร่วม of the student's own classroom
        if (ClassroomMember::isHomeroomStaffOf($user->id, $student)) {
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
        if ($this->isAcademyOwner($user, $academy_id)) {
            return true;
        }

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
        if ($this->isAcademyOwner($user, $academy_id)) {
            return true;
        }

        return AcademyMember::where('user_id', $user->id)
            ->where('academy_id', $academy_id)
            ->whereIn('role', ['admin', 'teacher', 'director'])
            ->exists();
    }

    /**
     * The academy owner (and super admins) normally have no academy_members
     * row at all, so every role lookup above would miss them.
     */
    protected function isAcademyOwner(User $user, $academy_id): bool
    {
        return Academy::find($academy_id)?->isAdmin($user) ?? false;
    }
}
