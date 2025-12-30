<?php

namespace App\Http\Controllers\Api\Learn\Course\assignments;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Http\Resources\Learn\Course\info\CourseResource;
use App\Http\Resources\Learn\Course\lessons\LessonResource;
use App\Http\Resources\Learn\Course\assignments\AssignmentResource;
use App\Http\Resources\Learn\Course\groups\CourseGroupResource;

class AssignmentController extends Controller
{
    public function index(Course $course)
    {
        return response()->json([
            'isCourseAdmin' => $course->user_id === auth()->id(),
            'course'        => new CourseResource($course),
            'lessons'       => LessonResource::collection($course->lessons),
            'assignments'       => AssignmentResource::collection($course->assignments),
            'groups'        => CourseGroupResource::collection($course->courseGroups),
            'courseMemberOfAuth'=> $course->courseMembers()->where('user_id', auth()->id())->first(),
        ]);
    }

    public function destroy(Assignment $assignment)
    {
        foreach ( $assignment->answers as $answer) {            
            foreach ($answer->images as $image) {
                Storage::disk('public')->delete('images/courses/assignments/answers/'. $image->filename);
            }
            $answer->images()->delete();
        }

        foreach ($assignment->images as $image) {
            Storage::disk('public')->delete('images/courses/assignments/'.$image->image_url);
        }

        $course = $assignment->assignmentable;
        $course->decrement('total_score', $assignment->points);

        $assignment->answers()->delete();
        $assignment->images()->delete();
        $assignment->delete();

        return response()->json(['success' => true], 200);
    }
}
