<?php

namespace App\Policies;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\ClassroomStudent;
use App\Models\RolloverBatch;
use App\Models\Student;
use App\Models\User;

class EnrollmentPolicy
{
    /**
     * Determine whether the user can manage the per-student enrollment lifecycle
     * (graduate/drop/repeat/promote/transfer) for a given student in an academy.
     * Allowed: Academy admins, directors, owners, OR the student's active homeroom teacher.
     */
    public function lifecycle(User $user, Academy $academy, Student $student): bool
    {
        if ($student->academy_id !== $academy->id) {
            return false;
        }

        // 1. Academy admin, director or owner can perform all operations
        if ($this->isAcademyAdmin($user, $academy)) {
            return true;
        }

        // 2. Homeroom teacher of the student's active classroom can also perform operations
        return ClassroomStudent::where('student_id', $student->id)
            ->where('status', ClassroomStudent::STATUS_ACTIVE)
            ->whereHas('classroom', function ($q) use ($user) {
                $q->where('homeroom_teacher_id', $user->id);
            })
            ->exists();
    }

    /**
     * Determine whether the user can preview a rollover.
     * Allowed: Academy admin, director, owner, OR teacher.
     */
    public function previewRollover(User $user, Academy $academy): bool
    {
        return $this->isAcademyStaff($user, $academy);
    }

    /**
     * Determine whether the user can plan a rollover.
     * Allowed: Academy admin, director, owner, OR teacher.
     */
    public function planRollover(User $user, Academy $academy): bool
    {
        return $this->isAcademyStaff($user, $academy);
    }

    /**
     * Determine whether the user can commit a rollover.
     * Allowed: Academy admin, director, owner only.
     */
    public function commitRollover(User $user, Academy $academy): bool
    {
        return $this->isAcademyAdmin($user, $academy);
    }

    /**
     * Determine whether the user can undo a rollover.
     * Allowed: Academy admin, director, owner only.
     */
    public function undoRollover(User $user, Academy $academy, RolloverBatch $batch): bool
    {
        if ($batch->academy_id !== $academy->id) {
            return false;
        }

        return $this->isAcademyAdmin($user, $academy);
    }

    /**
     * Determine whether the user can view rollover batches.
     * Allowed: Academy admin, director, owner, OR teacher.
     */
    public function viewBatches(User $user, Academy $academy): bool
    {
        return $this->isAcademyStaff($user, $academy);
    }

    // === Authorization Helpers ===

    protected function isAcademyAdmin(User $user, Academy $academy): bool
    {
        if ($academy->user_id === $user->id) {
            return true;
        }

        return AcademyMember::where('user_id', $user->id)
            ->where('academy_id', $academy->id)
            ->whereIn('role', ['admin', 'director'])
            ->exists();
    }

    protected function isAcademyStaff(User $user, Academy $academy): bool
    {
        if ($this->isAcademyAdmin($user, $academy)) {
            return true;
        }

        return AcademyMember::where('user_id', $user->id)
            ->where('academy_id', $academy->id)
            ->whereIn('role', ['admin', 'teacher', 'director'])
            ->exists();
    }
}
