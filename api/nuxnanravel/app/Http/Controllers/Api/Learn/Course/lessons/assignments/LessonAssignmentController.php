<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons\assignments;

use App\Models\Lesson;
use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Learn\Course\assignments\AssignmentResource;

class LessonAssignmentController extends \App\Http\Controllers\Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Lesson $lesson, Request $request)
    {
        $assignment = $lesson->assignments()->create([
            'title' => $request->title,
            'description' => $request->description,
            'points' => $request->points,
            'start_date' => $request->start_date,
            'due_date' => $request->due_date,
            'status' => 1 // Auto publish
        ]);

        // Update Course Total Score
        if ($lesson->course) {
            app(\App\Services\CourseScoreService::class)->syncCourseTotalScore($lesson->course);
        }

        if($request->hasFile('images')) {
            $images = $request->file('images');
            $fileNames = [];
            foreach ($images as $image) {
                $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
                $image_url = Storage::disk('public')->putFileAs('images/lessons/assignments', $image, $fileName);
                $fileNames[] = $fileName;

                $assignment->images()->create([
                    'image_url' => $image_url
                ]);
            }
        }
        return response()->json([
            'assignment' => new AssignmentResource($assignment),
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Lesson $lesson, Assignment $assignment, Request $request)
    {
        // Ownership check: assignment must belong to this lesson (directly or via topic)
        $assignmentLesson = $assignment->getLesson();
        if ($assignment->assignmentable_type === \App\Models\Lesson::class) {
            abort_if($assignment->assignmentable_id !== $lesson->id, 404);
        } elseif ($assignmentLesson) {
            abort_if($assignmentLesson->id !== $lesson->id, 404);
        } else {
            abort(404, 'Assignment does not belong to this lesson');
        }

        $oldPoints = $assignment->points;
        $assignment->update([
            'title' => $request->title,
            'description' => $request->description,
            'points' => $request->points,
            'start_date' => $request->start_date,
            'due_date' => $request->due_date,
        ]);

        // Update Course Total Score if points changed
        if ($lesson->course) {
            app(\App\Services\CourseScoreService::class)->syncCourseTotalScore($lesson->course);
        }

        if($request->hasFile('images')) {
            $images = $request->file('images');
            $fileNames = [];
            foreach ($images as $image) {
                $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
                $image_url = Storage::disk('public')->putFileAs('images/lessons/assignments', $image, $fileName);
                $fileNames[] = $fileName;

                $assignment->images()->create([
                    'image_url' => $image_url
                ]);
            }
        }
        return response()->json([
            'assignment' => new AssignmentResource($assignment),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lesson $lesson, Assignment $assignment)
    {
        $affectedCourseMembers = [];

        // Delete all answers and their images
        foreach ($assignment->answers as $answer) {            
            // Collect course members to recompute later
            if ($answer->points > 0) {
                $courseMember = \App\Models\CourseMember::where('course_id', $lesson->course_id)
                    ->where('user_id', $answer->user_id)
                    ->first();
                
                if ($courseMember) {
                    $affectedCourseMembers[] = $courseMember;
                }
            }

            foreach ($answer->images as $image) {
                Storage::disk('public')->delete('images/courses/assignments/answers/'. $image->filename);
            }
            $answer->images()->delete();
        }
        $assignment->answers()->delete();

        foreach ($affectedCourseMembers as $cm) {
            app(\App\Services\CourseScoreService::class)->recompute($cm);
        }

        // Delete assignment images
        foreach ($assignment->images as $image) {
            Storage::disk('public')->delete('images/lessons/assignments/'.$image->image_url);
        }
        $assignment->images()->delete();

        // Sync Course total score
        if ($lesson->course) {
            app(\App\Services\CourseScoreService::class)->syncCourseTotalScore($lesson->course);
        }

        $assignment->delete();

        return response()->json(['success' => true], 200);
    }
}
