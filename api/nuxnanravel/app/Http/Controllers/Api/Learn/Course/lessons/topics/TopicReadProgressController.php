<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons\topics;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\TopicReadProgress;
use App\Services\ContentVisibilityService;
use App\Services\LessonCompletionService;
use Illuminate\Http\Request;

class TopicReadProgressController extends Controller
{
    public function __construct(
        protected ContentVisibilityService $visibility,
        protected LessonCompletionService $lessonCompletionService
    ) {}

    /**
     * Start reading a topic
     */
    public function start(Request $request, Topic $topic)
    {
        $user = $request->user();

        // Guard: ถ้าไม่ใช่ course admin -> visibility check
        if (! $topic->course->isAdmin($user)) {
            $this->visibility->assertVisibleOrFail($topic->lesson, $user, 403);
        }

        $progress = TopicReadProgress::firstOrCreate(
            ['user_id' => $user->id, 'topic_id' => $topic->id],
            [
                'course_id' => $topic->course_id,
                'lesson_id' => $topic->lesson_id,
                'status' => TopicReadProgress::STATUS_NOT_STARTED,
            ]
        );

        if ($progress->status === TopicReadProgress::STATUS_NOT_STARTED) {
            $progress->markAsStarted($topic->getRequiredReadSeconds());
        }

        return response()->json([
            'success' => true,
            'progress' => [
                'status' => $progress->status,
                'started_at' => $progress->started_at,
                'required_seconds' => $progress->required_seconds_snapshot,
            ],
        ]);
    }

    /**
     * Complete reading a topic
     */
    public function complete(Request $request, Topic $topic)
    {
        $user = $request->user();

        if (! $topic->course->isAdmin($user)) {
            $this->visibility->assertVisibleOrFail($topic->lesson, $user, 403);
        }

        $progress = TopicReadProgress::where('user_id', $user->id)
            ->where('topic_id', $topic->id)
            ->first();

        if (! $progress) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเริ่มอ่านหัวข้อก่อน',
            ], 422);
        }

        if ($progress->isCompleted()) {
            return response()->json([
                'success' => true,
                'already_completed' => true,
                'progress' => [
                    'status' => $progress->status,
                    'completed_at' => $progress->completed_at,
                ],
            ]);
        }

        // Anti-cheat check (bypass for admins)
        if (! $topic->course->isAdmin($user) && ! $progress->canComplete()) {
            $progress->recordViolation();

            return response()->json([
                'success' => false,
                'code' => 'topic_read_too_fast',
                'message' => 'กรุณาอ่านเนื้อหาให้ครบก่อนกดเสร็จ',
                'required_seconds' => $progress->required_seconds_snapshot,
                'remaining_seconds' => $progress->remainingSeconds(),
            ], 422);
        }

        $progress->markAsCompleted();

        // Auto-complete lesson check
        $lesson = $topic->lesson;
        $lessonCompleted = false;
        $reward = null;

        if ($lesson->areAllTopicsCompletedBy($user)) {
            $result = $this->lessonCompletionService->completeLessonForUser($lesson, $user);
            $lessonCompleted = true;
            $reward = $result['reward'] ?? null;
        }

        return response()->json([
            'success' => true,
            'progress' => [
                'status' => $progress->status,
                'completed_at' => $progress->completed_at,
                'elapsed_seconds' => $progress->elapsed_seconds,
            ],
            'lesson_completed' => $lessonCompleted,
            'reward' => $reward,
        ]);
    }

    /**
     * Get summary of topic progress for a lesson
     */
    public function lessonTopicProgress(Request $request, Lesson $lesson)
    {
        $user = $request->user();

        if (! $lesson->course->isAdmin($user)) {
            $this->visibility->assertVisibleOrFail($lesson, $user, 403);
        }

        $publishedTopics = $lesson->topics()
            ->where('status', 'published')
            ->get();

        $progressRecords = TopicReadProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->get()
            ->keyBy('topic_id');

        $progressData = $publishedTopics->map(function ($topic) use ($progressRecords) {
            $record = $progressRecords->get($topic->id);

            return [
                'topic_id' => $topic->id,
                'status' => $record?->status ?? TopicReadProgress::STATUS_NOT_STARTED,
                'started_at' => $record?->started_at,
                'completed_at' => $record?->completed_at,
                'required_seconds' => $record?->required_seconds_snapshot ?? $topic->getRequiredReadSeconds(),
            ];
        });

        $totalTopics = $publishedTopics->count();
        $completedTopics = $progressData->where('status', TopicReadProgress::STATUS_COMPLETED)->count();

        return response()->json([
            'success' => true,
            'progress' => $progressData,
            'summary' => [
                'total_topics' => $totalTopics,
                'completed_topics' => $completedTopics,
                'progress_percentage' => $totalTopics > 0 ? (int) round(($completedTopics / $totalTopics) * 100) : 0,
            ],
        ]);
    }
}
