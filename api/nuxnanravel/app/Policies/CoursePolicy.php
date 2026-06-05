<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CoursePolicy
{
    /**
     * Determine whether the user can manage the course.
     */
    public function manage(User $user, Course $course): bool
    {
        // Owner, Instructor, or Creator
        if ($user->id === $course->user_id ||
            $user->id === $course->instructor_id ||
            $user->id === $course->creator_id) {
            return true;
        }
        // Course admin assigned via course_members role
        return $course->isAdmin($user);
    }

    /**
     * Determine whether the user can view the course.
     */
    public function view(User $user, Course $course): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create courses.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the course.
     */
    public function update(User $user, Course $course): bool
    {
        return $this->manage($user, $course);
    }

    /**
     * Determine whether the user can delete the course.
     */
    public function delete(User $user, Course $course): bool
    {
        return $this->manage($user, $course);
    }
}
