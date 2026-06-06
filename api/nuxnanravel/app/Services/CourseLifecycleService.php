<?php

namespace App\Services;

use App\Enums\CourseLifecycleState;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseQuizResult;
use App\Models\User;

/**
 * CourseLifecycleService
 *
 * Single source of truth for "what can {user} do on this {course} right now?"
 *
 * All methods are pure: they read the course state and return a Result. They
 * never mutate the course or its members. Controllers and policies should
 * delegate to this service rather than open-coding lifecycle checks.
 *
 * Reason codes are machine-readable and stable so the frontend can map them
 * to localized strings.
 */
class CourseLifecycleService
{
    public const REASON_OK = 'OK';

    public const REASON_DRAFT = 'COURSE_DRAFT';

    public const REASON_ENROLLMENT_CLOSED = 'COURSE_ENROLLMENT_CLOSED';

    public const REASON_ENDED = 'COURSE_ENDED';

    public const REASON_FINALIZED = 'COURSE_FINALIZED';

    public const REASON_ARCHIVED = 'COURSE_ARCHIVED';

    public const REASON_NOT_A_MEMBER = 'NOT_A_MEMBER';

    public const REASON_NOT_AUTHENTICATED = 'NOT_AUTHENTICATED';

    public const REASON_WORK_LOCKED = 'WORK_TYPE_LOCKED_AFTER_END';

    public const REASON_APPEAL_WINDOW_CLOSED = 'APPEAL_WINDOW_CLOSED';

    public const REASON_NO_REMEDIATION_GRANT = 'NO_REMEDIATION_GRANT';

    public const REASON_GRADE_NOT_PUBLISHED = 'GRADE_NOT_PUBLISHED';

    public const REASON_CERTIFICATE_NOT_ISSUED = 'CERTIFICATE_NOT_ISSUED';

    /** Default appeal window if course_settings.appeal_window_days is not set. */
    public const DEFAULT_APPEAL_WINDOW_DAYS = 7;

    /**
     * Can a non-member enroll right now?
     */
    public function canEnroll(Course $course, ?User $user): LifecycleResult
    {
        if (! $user) {
            return LifecycleResult::deny(self::REASON_NOT_AUTHENTICATED, 'กรุณาเข้าสู่ระบบก่อนสมัครเรียน');
        }

        $state = $course->lifecycleState();

        return match ($state) {
            CourseLifecycleState::Active => LifecycleResult::allow(),
            CourseLifecycleState::Draft => LifecycleResult::deny(self::REASON_DRAFT, 'รายวิชายังเป็นฉบับร่าง ยังไม่เปิดรับสมัคร'),
            CourseLifecycleState::EnrollmentClosed => LifecycleResult::deny(self::REASON_ENROLLMENT_CLOSED, 'รายวิชานี้ปิดรับสมัครแล้ว'),
            CourseLifecycleState::Grading,
            CourseLifecycleState::Published => LifecycleResult::deny(self::REASON_ENDED, 'รายวิชาสิ้นสุดการเรียนการสอนแล้ว'),
            CourseLifecycleState::Finalized => LifecycleResult::deny(self::REASON_FINALIZED, 'รายวิชานี้ปิดการเรียนแล้ว'),
            CourseLifecycleState::Archived => LifecycleResult::deny(self::REASON_ARCHIVED, 'รายวิชานี้ถูกเก็บถาวร'),
        };
    }

    /**
     * Can the user enter the course shell (any page under /Learn/Courses/{id})?
     *
     * - Instructors / managers: always.
     * - Members: always (read-only in archived).
     * - Non-members: only when course is still publicly visible and enrollable.
     */
    public function canEnterCourse(Course $course, ?User $user): LifecycleResult
    {
        if (! $user) {
            return LifecycleResult::deny(self::REASON_NOT_AUTHENTICATED, 'กรุณาเข้าสู่ระบบ');
        }

        if ($this->isManager($course, $user)) {
            return LifecycleResult::allow();
        }

        if ($this->resolveMember($course, $user)) {
            return LifecycleResult::allow();
        }

        // Non-member: gate via enrollment policy.
        return $this->canEnroll($course, $user);
    }

    /**
     * Can a current member continue learning activities (view lessons)?
     * Lesson viewing is allowed in every post-end state for existing members,
     * including archived (read-only).
     */
    public function canContinueLearning(Course $course, CourseMember $member): LifecycleResult
    {
        if (! $this->memberBelongsToCourse($course, $member)) {
            return LifecycleResult::deny(self::REASON_NOT_A_MEMBER, 'คุณไม่ใช่สมาชิกของรายวิชานี้');
        }

        return LifecycleResult::allow();
    }

    /**
     * Can a member submit a piece of work right now?
     *
     * @param  string  $workType  one of: quiz, assignment, lesson_question, remediation
     */
    public function canSubmitWork(Course $course, CourseMember $member, string $workType): LifecycleResult
    {
        if (! $this->memberBelongsToCourse($course, $member)) {
            return LifecycleResult::deny(self::REASON_NOT_A_MEMBER, 'คุณไม่ใช่สมาชิกของรายวิชานี้');
        }

        $state = $course->lifecycleState();

        if ($workType === 'remediation') {
            if ($state === CourseLifecycleState::Archived) {
                return LifecycleResult::deny(self::REASON_ARCHIVED, 'รายวิชานี้ถูกเก็บถาวรแล้ว');
            }
            if (! $this->hasActiveRemediationGrant($course, $member)) {
                return LifecycleResult::deny(self::REASON_NO_REMEDIATION_GRANT, 'คุณไม่มีสิทธิ์สอบแก้ตัวสำหรับรายวิชานี้');
            }

            return LifecycleResult::allow();
        }

        // Regular submissions (quiz / assignment / lesson_question) are only
        // permitted while the course is still teaching (Active or EnrollmentClosed).
        return match ($state) {
            CourseLifecycleState::Active,
            CourseLifecycleState::EnrollmentClosed => LifecycleResult::allow(),
            CourseLifecycleState::Draft => LifecycleResult::deny(self::REASON_DRAFT, 'รายวิชายังเป็นฉบับร่าง'),
            CourseLifecycleState::Grading,
            CourseLifecycleState::Published,
            CourseLifecycleState::Finalized,
            CourseLifecycleState::Archived => LifecycleResult::deny(
                self::REASON_WORK_LOCKED,
                'รายวิชาสิ้นสุดแล้ว ไม่สามารถส่งงาน/ทำแบบทดสอบปกติได้',
                'หากต้องการสอบแก้ตัว กรุณาติดต่ออาจารย์ผู้สอน'
            ),
        };
    }

    /**
     * Can the user manage as instructor right now?
     * Override actions on archived courses are denied — unarchive first.
     */
    public function canManageAsInstructor(Course $course, ?User $user): LifecycleResult
    {
        if (! $user) {
            return LifecycleResult::deny(self::REASON_NOT_AUTHENTICATED, 'กรุณาเข้าสู่ระบบ');
        }
        if (! $this->isManager($course, $user)) {
            return LifecycleResult::deny(self::REASON_NOT_A_MEMBER, 'คุณไม่มีสิทธิ์จัดการรายวิชานี้');
        }
        if ($course->lifecycleState() === CourseLifecycleState::Archived) {
            return LifecycleResult::deny(self::REASON_ARCHIVED, 'รายวิชาถูกเก็บถาวร — ต้องนำออกจากคลังก่อนแก้ไข');
        }

        return LifecycleResult::allow();
    }

    /**
     * Whether the appeal window for a published/finalized course is still open.
     */
    public function appealWindowOpen(Course $course): bool
    {
        $state = $course->lifecycleState();
        if (! in_array($state, [CourseLifecycleState::Published, CourseLifecycleState::Finalized], true)) {
            return false;
        }

        $days = (int) ($course->courseSettings->appeal_window_days ?? self::DEFAULT_APPEAL_WINDOW_DAYS);
        $reference = $course->finalized_at ?? $course->published_at ?? $course->updated_at;
        if (! $reference) {
            return true;
        }

        return $reference->copy()->addDays($days)->isFuture();
    }

    public function canDownloadCertificate(Course $course, CourseMember $member): LifecycleResult
    {
        if (! $this->memberBelongsToCourse($course, $member)) {
            return LifecycleResult::deny(self::REASON_NOT_A_MEMBER, 'คุณไม่ใช่สมาชิกของรายวิชานี้');
        }

        // Certificate must be issued (member-level flag).
        if (! ($member->certificate_issued ?? false)) {
            return LifecycleResult::deny(self::REASON_CERTIFICATE_NOT_ISSUED, 'ยังไม่ได้ออกใบประกาศสำหรับคุณ');
        }

        return LifecycleResult::allow();
    }

    /**
     * Set of action keys allowed for a member when the course is post-end.
     * Used by the frontend to render the right CTAs / tabs.
     *
     * @return array<int, string>
     */
    public function allowedPostEndActions(Course $course, CourseMember $member): array
    {
        $state = $course->lifecycleState();
        $actions = ['view_lessons', 'view_own_grade'];

        if ($state === CourseLifecycleState::Published) {
            if (($member->grade_acceptance_status ?? null) === 'pending') {
                $actions[] = 'accept_grade';
            }
        }

        if ($this->appealWindowOpen($course)) {
            $actions[] = 'appeal';
        }

        if ($state !== CourseLifecycleState::Archived && $this->hasActiveRemediationGrant($course, $member)) {
            $actions[] = 'remediation';
        }

        if ($member->certificate_issued ?? false) {
            $actions[] = 'download_certificate';
        }

        return $actions;
    }

    // ---- Helpers ---------------------------------------------------------

    private function isManager(Course $course, User $user): bool
    {
        if ($user->id === $course->user_id
            || $user->id === $course->instructor_id
            || $user->id === $course->creator_id) {
            return true;
        }

        return method_exists($course, 'isAdmin') ? (bool) $course->isAdmin($user) : false;
    }

    private function resolveMember(Course $course, User $user): ?CourseMember
    {
        return CourseMember::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();
    }

    private function memberBelongsToCourse(Course $course, CourseMember $member): bool
    {
        return (int) $member->course_id === (int) $course->id;
    }

    /**
     * A member has an active remediation grant if they have at least one
     * CourseQuizResult with retake_unlocked_at set and retake_used_at null.
     */
    private function hasActiveRemediationGrant(Course $course, CourseMember $member): bool
    {
        return CourseQuizResult::query()
            ->where('user_id', $member->user_id)
            ->whereNotNull('retake_unlocked_at')
            ->whereNull('retake_used_at')
            ->whereHas('quiz', fn ($q) => $q->where('course_id', $course->id))
            ->exists();
    }
}
