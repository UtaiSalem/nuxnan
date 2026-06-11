<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons;

use App\Enums\UsageEventType;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\ContentVisibilityService;
use App\Services\CoursePointAccountService;
use App\Services\LessonCompletionService;
use App\Services\UsageEventService;
use Illuminate\Http\Request;

class LessonProgressController extends Controller
{
    public function __construct(
        protected CoursePointAccountService $coursePointService,
        protected ContentVisibilityService $visibility,
        protected LessonCompletionService $lessonCompletionService
    ) {}

    /**
     * Get progress for a lesson
     */
    public function show(Request $request, Lesson $lesson)
    {
        $user = $request->user();

        // Guard for students
        if (! $lesson->course->isAdmin($user)) {
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
        if (! $lesson->course->isAdmin($user)) {
            $this->visibility->assertVisibleOrFail($lesson, $user, 403);
        }

        $progress = $lesson->getOrCreateProgress($user);

        if ($progress->status === LessonProgress::STATUS_NOT_STARTED) {
            $progress->markAsStarted();

            // Fire gamification event
            UsageEventService::fire($user, UsageEventType::LESSON_START->value, 'lesson', $lesson->id);
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
        if (! $lesson->course->isAdmin($user)) {
            $this->visibility->assertVisibleOrFail($lesson, $user, 403);
        }

        $result = $this->lessonCompletionService->completeLessonForUser($lesson, $user);

        return response()->json($result);
    }

    /**
     * Toggle complete status (mark complete or uncomplete)
     */
    public function toggleComplete(Request $request, Lesson $lesson)
    {
        $user = $request->user();

        // Guard for students
        if (! $lesson->course->isAdmin($user)) {
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
            // Complete using service
            $result = $this->lessonCompletionService->completeLessonForUser($lesson, $user);

            return response()->json($result);
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
        if (! $lesson->course->isAdmin($user)) {
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
