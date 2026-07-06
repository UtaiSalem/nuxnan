<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\CourseQuiz;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ContentVisibilityService
{
    /**
     * Check if a student can view a lesson.
     */
    public function canStudentViewLesson(User $user, Lesson $lesson): bool
    {
        return $lesson->publication_status === Lesson::STATUS_PUBLISHED;
    }

    /**
     * Check if a student can view an assignment.
     * Visibility cascades from parent Lesson/Topic if applicable.
     */
    public function canStudentViewAssignment(User $user, Assignment $assignment): bool
    {
        // Must be published itself
        if (! $assignment->is_published) {
            return false;
        }

        // Check parent visibility if it's attached to a Lesson or Topic
        $lesson = $assignment->getLesson();
        if ($lesson) {
            return $this->canStudentViewLesson($user, $lesson);
        }

        // If course-level or other, only check its own status
        return true;
    }

    /**
     * Check if a student can view a quiz.
     */
    public function canStudentViewQuiz(User $user, CourseQuiz $quiz): bool
    {
        return (bool) $quiz->is_active;
    }

    /**
     * Check if a student can interact with a lesson (progress, answer questions).
     */
    public function canStudentInteractWithLesson(User $user, Lesson $lesson): bool
    {
        return $this->canStudentViewLesson($user, $lesson);
    }

    /**
     * Check if a student can interact with an assignment (submit answer).
     */
    public function canStudentInteractWithAssignment(User $user, Assignment $assignment): bool
    {
        return $this->canStudentViewAssignment($user, $assignment);
    }

    /**
     * Check if a student can interact with a quiz (start attempt, submit answers).
     */
    public function canStudentInteractWithQuiz(User $user, CourseQuiz $quiz): bool
    {
        return $this->canStudentViewQuiz($user, $quiz);
    }

    /**
     * Assert that the content is visible to the student or fail with 404/403.
     */
    public function assertVisibleOrFail(Model $model, User $user, int $code = 404): void
    {
        $visible = false;

        if ($model instanceof Lesson) {
            $visible = $this->canStudentViewLesson($user, $model);
        } elseif ($model instanceof Assignment) {
            $visible = $this->canStudentViewAssignment($user, $model);
        } elseif ($model instanceof CourseQuiz) {
            $visible = $this->canStudentViewQuiz($user, $model);
        } else {
            // Default to visible if unknown model, or should we be stricter?
            // For now, let's assume it's one of the three.
            return;
        }

        if (! $visible) {
            abort($code, 'เนื้อหานี้ยังไม่เปิดให้เข้าใช้งาน');
        }
    }
}
