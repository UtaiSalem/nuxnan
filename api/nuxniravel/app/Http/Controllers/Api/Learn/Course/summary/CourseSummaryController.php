<?php

namespace App\Http\Controllers\Api\Learn\Course\summary;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseSummaryController extends Controller
{
    /**
     * Display a summary of the course with statistics and progress data
     */
    public function show(Course $course, Request $request)
    {
        $course->load([
            'courseGroups',
            'courseMembers',
            'courseLessons',
            'courseAssignments',
            'courseQuizzes'
        ]);

        $summary = [
            'basic_info' => [
                'id' => $course->id,
                'name' => $course->name,
                'code' => $course->code,
                'category' => $course->category,
                'level' => $course->level,
                'status' => $course->status,
                'enrolled_students' => $course->enrolled_students,
            ],
            'statistics' => [
                'total_groups' => $course->courseGroups->count(),
                'total_members' => $course->courseMembers->count(),
                'active_members' => $course->courseMembers->where('status', 1)->count(),
                'total_lessons' => $course->courseLessons->count(),
                'total_assignments' => $course->courseAssignments->count(),
                'total_quizzes' => $course->courseQuizzes->count(),
            ],
            'progress' => [
                'average_completion' => $this->calculateCourseCompletionRate($course),
                'average_grade' => $this->calculateCourseAverageGrade($course),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Helper method to calculate course completion rate
     */
    private function calculateCourseCompletionRate(Course $course): float
    {
        $members = $course->courseMembers()->where('status', 1)->get();
        
        if ($members->isEmpty()) return 0;

        $totalCompletion = 0;
        foreach ($members as $member) {
            $totalCompletion += $this->calculateMemberCompletionRate($course, $member);
        }

        return round($totalCompletion / $members->count(), 2);
    }

    /**
     * Helper method to calculate member completion rate
     */
    private function calculateMemberCompletionRate(Course $course, $member): float
    {
        $totalItems = $course->courseLessons()->count() +
                     $course->courseAssignments()->count() +
                     $course->courseQuizzes()->count();
        
        if ($totalItems === 0) return 0;

        $completedItems = 0;
        
        // Count completed lessons
        if ($member->lessons_completed) {
            $completedItems += count(json_decode($member->lessons_completed, true) ?? []);
        }
        
        // Count completed assignments
        if ($member->assignments_completed) {
            $completedItems += count(json_decode($member->assignments_completed, true) ?? []);
        }
        
        // Count completed quizzes
        if ($member->quizzes_completed) {
            $completedItems += count(json_decode($member->quizzes_completed, true) ?? []);
        }

        return round(($completedItems / $totalItems) * 100, 2);
    }

    /**
     * Helper method to calculate course average grade
     */
    private function calculateCourseAverageGrade(Course $course): float
    {
        $members = $course->courseMembers()->where('status', 1)->get();
        
        if ($members->isEmpty()) return 0;

        $totalGrade = 0;
        foreach ($members as $member) {
            if ($member->grade_progress) {
                $totalGrade += (float) $member->grade_progress;
            }
        }

        return round($totalGrade / $members->count(), 2);
    }
}
