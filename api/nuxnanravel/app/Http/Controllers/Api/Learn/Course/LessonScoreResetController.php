<?php

namespace App\Http\Controllers\Api\Learn\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\Lesson;
use App\Services\LessonScoreResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LessonScoreResetController extends Controller
{
    protected LessonScoreResetService $resetService;

    public function __construct(LessonScoreResetService $resetService)
    {
        $this->resetService = $resetService;
    }

    /**
     * Reset scores for a single member in a specific lesson.
     */
    public function resetMemberLesson(Request $request, Course $course, CourseMember $member, Lesson $lesson)
    {
        // 1. Guard: Check permission
        if (!$course->hasPermission(auth()->user(), 'edit_grades')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // 2. Guard: Legacy gradebook
        if ($course->use_legacy_gradebook) {
            return response()->json(['message' => 'Cannot reset scores in legacy gradebook mode.'], 422);
        }

        // 3. Guard: Member must belong to this course
        if ($member->course_id !== $course->id) {
            return response()->json(['message' => 'Member does not belong to this course'], 422);
        }

        // 4. Guard: Lesson must belong to this course
        if ($lesson->course_id !== $course->id) {
            return response()->json(['message' => 'Lesson does not belong to this course'], 422);
        }

        $type = $request->input('type'); // quiz, assignment_grade, assignment_full
        $reason = $request->input('reason');
        $adminId = auth()->id();

        try {
            switch ($type) {
                case 'quiz':
                    $breakdown = $this->resetService->resetQuiz($member, $lesson, $adminId, $reason);
                    break;
                case 'assignment_grade':
                    $breakdown = $this->resetService->resetAssignmentGrade($member, $lesson, $adminId, $reason);
                    break;
                case 'assignment_full':
                    $breakdown = $this->resetService->resetAssignmentFull($member, $lesson, $adminId, $reason);
                    break;
                default:
                    return response()->json(['message' => 'Invalid reset type'], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Scores reset successfully',
                'breakdown' => $breakdown->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Reset scores for all members in a specific lesson (Bulk).
     */
    public function resetAllMembersLesson(Request $request, Course $course, Lesson $lesson)
    {
        if (!$course->hasPermission(auth()->user(), 'edit_grades')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($course->use_legacy_gradebook) {
            return response()->json(['message' => 'Cannot reset scores in legacy gradebook mode.'], 422);
        }

        if ($lesson->course_id !== $course->id) {
            return response()->json(['message' => 'Lesson does not belong to this course'], 422);
        }

        $type = $request->input('type');
        $reason = $request->input('reason');
        $adminId = auth()->id();

        if (!in_array($type, ['quiz', 'assignment_grade', 'assignment_full'], true)) {
            return response()->json(['message' => 'Invalid reset type'], 422);
        }

        // Avoid PHP timeout on large classrooms; bulk reset is still synchronous
        // but processed in chunks. Move to a queued job if classroom size grows.
        set_time_limit(0);

        try {
            $totalAffected = 0;

            $course->courseMembers()
                ->whereIn('role', [1, 2])
                ->chunkById(20, function ($members) use ($lesson, $adminId, $reason, $type, &$totalAffected) {
                    foreach ($members as $member) {
                        switch ($type) {
                            case 'quiz':
                                $this->resetService->resetQuiz($member, $lesson, $adminId, $reason);
                                break;
                            case 'assignment_grade':
                                $this->resetService->resetAssignmentGrade($member, $lesson, $adminId, $reason);
                                break;
                            case 'assignment_full':
                                $this->resetService->resetAssignmentFull($member, $lesson, $adminId, $reason);
                                break;
                        }
                        $totalAffected++;
                    }
                });

            return response()->json([
                'success' => true,
                'message' => 'All member scores for this lesson reset successfully',
                'affected_members' => $totalAffected,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
