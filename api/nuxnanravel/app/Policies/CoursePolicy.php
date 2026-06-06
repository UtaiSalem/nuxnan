<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\User;
use App\Services\CourseLifecycleService;
use App\Services\LifecycleResult;
use Illuminate\Auth\Access\Response;

class CoursePolicy
{
    public function __construct(protected CourseLifecycleService $lifecycle) {}

    /**
     * Determine whether the user can manage the course.
     */
    public function manage(User $user, Course $course): bool
    {
        if ($user->id === $course->user_id
            || $user->id === $course->instructor_id
            || $user->id === $course->creator_id) {
            return true;
        }

        return $course->isAdmin($user);
    }

    public function view(User $user, Course $course): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Course $course): bool
    {
        return $this->manage($user, $course);
    }

    public function delete(User $user, Course $course): bool
    {
        return $this->manage($user, $course);
    }

    // ---- Lifecycle-aware abilities --------------------------------------

    public function enroll(User $user, Course $course): Response
    {
        return $this->toResponse($this->lifecycle->canEnroll($course, $user));
    }

    public function enterCourse(User $user, Course $course): Response
    {
        return $this->toResponse($this->lifecycle->canEnterCourse($course, $user));
    }

    public function submitQuiz(User $user, Course $course): Response
    {
        return $this->submitAs($user, $course, 'quiz');
    }

    public function submitAssignment(User $user, Course $course): Response
    {
        return $this->submitAs($user, $course, 'assignment');
    }

    public function submitLessonQuestion(User $user, Course $course): Response
    {
        return $this->submitAs($user, $course, 'lesson_question');
    }

    public function submitRemediation(User $user, Course $course): Response
    {
        return $this->submitAs($user, $course, 'remediation');
    }

    public function downloadCertificate(User $user, Course $course): Response
    {
        $member = $this->resolveMember($user, $course);
        if (! $member) {
            return Response::deny('คุณไม่ใช่สมาชิกของรายวิชานี้', CourseLifecycleService::REASON_NOT_A_MEMBER);
        }

        return $this->toResponse($this->lifecycle->canDownloadCertificate($course, $member));
    }

    public function manageAfterEnd(User $user, Course $course): Response
    {
        return $this->toResponse($this->lifecycle->canManageAsInstructor($course, $user));
    }

    // ---- Helpers --------------------------------------------------------

    protected function submitAs(User $user, Course $course, string $workType): Response
    {
        $member = $this->resolveMember($user, $course);
        if (! $member) {
            return Response::deny('คุณไม่ใช่สมาชิกของรายวิชานี้', CourseLifecycleService::REASON_NOT_A_MEMBER);
        }

        return $this->toResponse($this->lifecycle->canSubmitWork($course, $member, $workType));
    }

    protected function resolveMember(User $user, Course $course): ?CourseMember
    {
        return CourseMember::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();
    }

    protected function toResponse(LifecycleResult $result): Response
    {
        return $result->allowed
            ? Response::allow()
            : Response::deny($result->message ?: $result->reasonCode, $result->reasonCode);
    }
}
