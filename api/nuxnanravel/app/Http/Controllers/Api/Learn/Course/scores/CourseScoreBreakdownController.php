<?php

namespace App\Http\Controllers\Api\Learn\Course\scores;

use App\Models\Course;
use Illuminate\Http\Request;
use App\Services\CourseScoreService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CourseScoreBreakdownController extends Controller
{
    protected CourseScoreService $scoreService;

    public function __construct(CourseScoreService $scoreService)
    {
        $this->scoreService = $scoreService;
    }

    /**
     * Get the score breakdown for all members in a course.
     * With auto-resync enabled per user request.
     */
    public function index(Request $request, Course $course): JsonResponse
    {
        // Require edit_grades permission
        if (!$course->hasPermission(auth()->user(), 'edit_grades')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Auto-resync (as requested in P4)
        // Note: For large courses, this might be slow, but it fulfills the immediate requirement.
        $this->scoreService->syncAllCourseMembers($course);

        // Fetch members with user data
        $members = $course->courseMembers()->with('user:id,name,username,profile_photo_path')->get();

        $data = [];
        foreach ($members as $member) {
            $breakdown = $this->scoreService->computeBreakdown($member);
            
            $data[] = [
                'member_id' => $member->id,
                'user' => $member->user,
                'breakdown' => $breakdown->toArray(),
                'last_synced_at' => $member->last_score_synced_at,
                'grade_progress' => $member->grade_progress,
                'draft_grade' => $member->draft_grade,
                'final_grade' => $member->final_grade,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'use_legacy_gradebook' => $course->use_legacy_gradebook,
            'course_total_score' => $course->total_score,
        ]);
    }

    /**
     * Explicitly resync all member scores for a course.
     */
    public function resync(Request $request, Course $course): JsonResponse
    {
        // Require edit_grades permission
        if (!$course->hasPermission(auth()->user(), 'edit_grades')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->scoreService->syncAllCourseMembers($course);

        return response()->json([
            'success' => true,
            'message' => 'Resynced scores for all members successfully.'
        ]);
    }
}
