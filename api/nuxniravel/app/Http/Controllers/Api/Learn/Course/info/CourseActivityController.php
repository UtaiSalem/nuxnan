<?php

namespace App\Http\Controllers\Api\Learn\Course\info;

use App\Models\Course;
use App\Models\Activity;
use App\Models\CoursePost;
use App\Models\CourseGroup;
use App\Http\Controllers\Controller;
use App\Http\Resources\Play\ActivityResource;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Http\Resources\Learn\Academy\AcademyResource;
use App\Http\Resources\Learn\Course\info\CourseResource;
use App\Http\Resources\Learn\Course\groups\CourseGroupResource;

class CourseActivityController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:api'),
        ];
    }

    public function index(Course $course)
    {

        try {

            $isCourseAdmin = $course->user_id == auth()->id();
            $cma = $course->courseMembers()->where('user_id', auth()->id())->first();
            $coursesResource = new CourseResource($course);
            
            // Get course groups
            $courseGroups = CourseGroup::where('course_id', $course->id)
                ->withCount('course_group_members')
                ->get();
    
            $activities = Activity::whereHasMorph('activityable', [CoursePost::class], function ($query) use ($course) {
                    $query->where('course_id', $course->id);
            })->latest()->paginate();
    
            return response()->json([
                'success'               => true,
                'academy'               => $course->academy ? new AcademyResource($course->academy) : null,
                'course'                => $coursesResource,
                'isCourseAdmin'         => $isCourseAdmin,
                'courseMemberOfAuth'    => $cma,
                'courseGroups'          => CourseGroupResource::collection($courseGroups),
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

    public function test_get_data(Course $course)
    {
        return response()->json([
            'success' => true,
            'message' => 'Test route works fine',
            'data'    => CourseGroup::where('course_id', $course->id)->get(),
        ]);
    }
}
