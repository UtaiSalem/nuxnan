<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\CourseMember;
use App\Services\AttendanceEligibilityService;
use App\Services\CoursePointAccountService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Services\ContentVisibilityService;


class LessonProgressController extends Controller
{
    /**
     * @var CoursePointAccountService
     */
    protected $coursePointService;

    protected ContentVisibilityService $visibility;

    public function __construct(CoursePointAccountService $coursePointService, ContentVisibilityService $visibility)
    {
        $this->coursePointService = $coursePointService;
        $this->visibility = $visibility;
    }

    /**
     * Get progress for a lesson
     */
    public function show(Request $request, Lesson $lesson)
    {
        $user = $request->user();

        // Guard for students
        if (!$lesson->course->isAdmin($user)) {
            $this->visibility->assertVisibleOrFail($lesson, $user, 404);
        }

        $progress = $lesson->userProgress($user);

        return response()->json([
            'success' => true,
            'progress' => $progress ? [
                'status' => $progress->status,
                'started_at' => $progress->started_at,
                'completed_at' => $progress->completed_at,
                'time_spent_seconds' => $progress->time_spent_seconds,
                'is_completed' => $progress->isCompleted(),
                'is_in_progress' => $progress->isInProgress(),
            ] : null,
        ]);
    }

    /**
     * Start learning a lesson
     */
    public function start(Request $request, Lesson $lesson)
    {
        $user = $request->user();

        // Guard for students
        if (!$lesson->course->isAdmin($user)) {
            $this->visibility->assertVisibleOrFail($lesson, $user, 403);
        }

        $progress = $lesson->getOrCreateProgress($user);

        if ($progress->status === LessonProgress::STATUS_NOT_STARTED) {
            $progress->markAsStarted();

            // Fire gamification event
            \App\Services\UsageEventService::fire($user, \App\Enums\UsageEventType::LESSON_START->value, 'lesson', $lesson->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'เริ่มเรียนบทเรียนแล้ว',
            'progress' => [
                'status' => $progress->status,
                'started_at' => $progress->started_at,
            ],
        ]);
    }

    /**
     * Mark lesson as complete
     */
    public function complete(Request $request, Lesson $lesson)
    {
        $user = $request->user();

        // Guard for students
        if (!$lesson->course->isAdmin($user)) {
            $this->visibility->assertVisibleOrFail($lesson, $user, 403);
        }

        $progress = $lesson->getOrCreateProgress($user);

        $wasAlreadyCompleted = $progress->isCompleted();

        // If not started yet, mark as started first
        if ($progress->status === LessonProgress::STATUS_NOT_STARTED) {
            $progress->update(['started_at' => now()]);
        }

        $progress->markAsCompleted();

        // Fire gamification event
        \App\Services\UsageEventService::fire($user, \App\Enums\UsageEventType::LESSON_COMPLETE->value, 'lesson', $lesson->id);

        // Auto-unlock if there is a pending reading override and requirements are met
        $member = CourseMember::where('user_id', $user->id)
            ->where('course_id', $lesson->course_id)
            ->first();

        if ($member) {
            $service = app(AttendanceEligibilityService::class);
            $service->processReadingLessonUnlockIfCompleted($member);
        }

        // ✅ Grant reward if first time completion
        $reward = ['rewarded' => false];
        if (!$wasAlreadyCompleted) {
            $reward = $this->coursePointService->grantLessonCompletionReward($lesson, $user);
        }

        return response()->json([
            'success' => true,
            'message' => 'ยินดีด้วย! คุณเรียนบทเรียนนี้จบแล้ว',
            'progress' => [
                'status' => $progress->status,
                'completed_at' => $progress->completed_at,
            ],
            'reward' => $reward,
        ]);
    }

    /**
     * Toggle complete status (mark complete or uncomplete)
     */
    public function toggleComplete(Request $request, Lesson $lesson)
    {
        $user = $request->user();

        // Guard for students
        if (!$lesson->course->isAdmin($user)) {
            $this->visibility->assertVisibleOrFail($lesson, $user, 403);
        }

        $progress = $lesson->getOrCreateProgress($user);

        if ($progress->isCompleted()) {
            // Uncomplete - go back to in_progress
            $progress->update([
                'status' => LessonProgress::STATUS_IN_PROGRESS,
                'completed_at' => null,
            ]);

            return response()->json([
                'success' => true,
                'completed' => false,
                'message' => 'ยกเลิกสถานะเรียนจบแล้ว',
                'progress' => [
                    'status' => $progress->status,
                ],
            ]);
        } else {
            // Complete
            $wasAlreadyCompleted = $progress->isCompleted();

            if ($progress->status === LessonProgress::STATUS_NOT_STARTED) {
                $progress->update(['started_at' => now()]);
            }
            $progress->markAsCompleted();

            // Fire gamification event
            \App\Services\UsageEventService::fire($user, \App\Enums\UsageEventType::LESSON_COMPLETE->value, 'lesson', $lesson->id);

            // Auto-unlock if there is a pending reading override and requirements are met
            $member = CourseMember::where('user_id', $user->id)
                ->where('course_id', $lesson->course_id)
                ->first();

            if ($member) {
                $service = app(AttendanceEligibilityService::class);
                $service->processReadingLessonUnlockIfCompleted($member);
            }

            // ✅ Grant reward
            $reward = $this->coursePointService->grantLessonCompletionReward($lesson, $user);

            return response()->json([
                'success' => true,
                'completed' => true,
                'message' => 'ยินดีด้วย! คุณเรียนบทเรียนนี้จบแล้ว',
                'progress' => [
                    'status' => $progress->status,
                    'completed_at' => $progress->completed_at,
                ],
                'reward' => $reward,
            ]);
        }
    }

    /**
     * Update time spent on lesson
     */
    public function updateTimeSpent(Request $request, Lesson $lesson)
    {
        $request->validate([
            'seconds' => 'required|integer|min:1|max:3600', // Max 1 hour per update
        ]);

        $user = $request->user();

        // Guard for students
        if (!$lesson->course->isAdmin($user)) {
            $this->visibility->assertVisibleOrFail($lesson, $user, 403);
        }

        $progress = $lesson->getOrCreateProgress($user);

        // Auto-start if not started
        if ($progress->status === LessonProgress::STATUS_NOT_STARTED) {
            $progress->markAsStarted();
        }

        $progress->addTimeSpent($request->seconds);

        return response()->json([
            'success' => true,
            'time_spent_seconds' => $progress->time_spent_seconds,
        ]);
    }

    /**
     * Get course progress summary (all lessons)
     */
    public function courseProgress(Request $request, $courseId)
    {
        $user = $request->user();

        $lessons = Lesson::where('course_id', $courseId)
            ->where('status', 1) // Only published lessons
            ->with(['progress' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->orderBy('order')
            ->get();

        $totalLessons = $lessons->count();
        $completedLessons = $lessons->filter(function ($lesson) {
            return $lesson->progress->first()?->isCompleted();
        })->count();

        $progressPercentage = $totalLessons > 0 
            ? round(($completedLessons / $totalLessons) * 100) 
            : 0;

        return response()->json([
            'success' => true,
            'total_lessons' => $totalLessons,
            'completed_lessons' => $completedLessons,
            'progress_percentage' => $progressPercentage,
            'lessons' => $lessons->map(function ($lesson) {
                $progress = $lesson->progress->first();
                return [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'order' => $lesson->order,
                    'status' => $progress?->status ?? 'not_started',
                    'is_completed' => $progress?->isCompleted() ?? false,
                ];
            }),
        ]);
    }
}
