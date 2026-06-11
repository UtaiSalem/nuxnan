<?php

namespace App\Services;

use App\Enums\UsageEventType;
use App\Models\CourseMember;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;

class LessonCompletionService
{
    public function __construct(
        protected CoursePointAccountService $coursePointService,
        protected AttendanceEligibilityService $attendanceService
    ) {}

    /**
     * Mark a lesson as completed for a specific user and handle rewards/events.
     */
    public function completeLessonForUser(Lesson $lesson, User $user): array
    {
        $progress = $lesson->getOrCreateProgress($user);
        $wasAlreadyCompleted = $progress->isCompleted();

        // If not started yet, mark as started first
        if ($progress->status === LessonProgress::STATUS_NOT_STARTED) {
            $progress->update(['started_at' => now()]);
        }

        $progress->markAsCompleted();

        // Fire gamification event
        UsageEventService::fire($user, UsageEventType::LESSON_COMPLETE->value, 'lesson', $lesson->id);

        // Auto-unlock if there is a pending reading override and requirements are met
        $member = CourseMember::where('user_id', $user->id)
            ->where('course_id', $lesson->course_id)
            ->first();

        if ($member) {
            $this->attendanceService->processReadingLessonUnlockIfCompleted($member);
        }

        // Grant reward if first time completion
        $reward = ['rewarded' => false];
        if (! $wasAlreadyCompleted) {
            $reward = $this->coursePointService->grantLessonCompletionReward($lesson, $user);
        }

        return [
            'success' => true,
            'completed' => true,
            'was_already_completed' => $wasAlreadyCompleted,
            'message' => 'ยินดีด้วย! คุณเรียนบทเรียนนี้จบแล้ว',
            'progress' => [
                'status' => $progress->status,
                'completed_at' => $progress->completed_at,
            ],
            'reward' => $reward,
        ];
    }
}
