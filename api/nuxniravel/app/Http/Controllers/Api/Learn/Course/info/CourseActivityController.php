<?php

namespace App\Http\Controllers\Api\Learn\Course\info;


use App\Models\Course;
use App\Models\Activity;
use App\Models\CoursePost;
use App\Http\Resources\Learn\info\CourseResource;
use App\Http\Resources\Learn\Academy\AcademyResource;
use App\Http\Resources\Play\ActivityResource;

class CourseActivityController extends \App\Http\Controllers\Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Course $course)
    {
        try {

            $isCourseAdmin = $course->user_id == auth()->id();
            $cma = $course->courseMembers()->where('user_id', auth()->id())->first();
            $coursesResource = new CourseResource($course);
    
            $activities = Activity::whereHasMorph('activityable', [CoursePost::class], function ($query) use ($course) {
                    $query->where('course_id', $course->id);
            })->latest()->paginate();
    
            return response()->json([
                'success'               => true,
                'academy'               => $course->academy ? new AcademyResource($course->academy) : null,
                'course'                => $coursesResource,
                'isCourseAdmin'         => $isCourseAdmin,
                'courseMemberOfAuth'    => $cma,
                'activities'            => ActivityResource::collection($activities),
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load course activity'. $e->getMessage());
        }
    }

    public function getActivities(Course $course)
    {
        $activities = Activity::whereHasMorph('activityable', [CoursePost::class], function ($query) use ($course) {
                $query->where('course_id', $course->id);
        })->latest()->paginate();

        return response()->json([
            'success' => true,
            'activities' => ActivityResource::collection($activities),
        ]);
    }
}
