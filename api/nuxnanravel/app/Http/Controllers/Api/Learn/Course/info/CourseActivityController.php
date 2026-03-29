<?php

namespace App\Http\Controllers\Api\Learn\Course\info;

use App\Models\Course;
use App\Models\Activity;
use App\Models\RecentlyViewedCourse;
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
            
            // Record recently viewed
            if (auth()->check()) {
                RecentlyViewedCourse::updateOrInsert(
                    ['user_id' => auth()->id(), 'course_id' => $course->id],
                    ['updated_at' => now()]
                );
            }

            $isCourseAdmin = $course->isAdmin(auth()->user());
            $cma = $course->courseMembers()->where('user_id', auth()->id())->first();
            $coursesResource = new CourseResource($course);
            
            // Get course groups with eager loaded members and users
            $courseGroups = CourseGroup::where('course_id', $course->id)
                ->with(['members.user'])
                ->withCount('course_group_members')
                ->get();

            // Get ungrouped members (no group assigned, exclude admins role=4)
            $ungroupedMembers = $course->courseMembers()
                ->whereNull('group_id')
                ->where('role', '!=', 4)
                ->with('user')
                ->orderBy('order_number')
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
                'ungroupedMembers'      => $ungroupedMembers->map(function ($member) {
                    $user = $member->user;
                    $avatarUrl = $user
                        ? ($user->profile_photo_path
                            ? (filter_var($user->profile_photo_path, FILTER_VALIDATE_URL)
                                ? $user->profile_photo_path
                                : url(\Illuminate\Support\Facades\Storage::url($user->profile_photo_path)))
                            : 'https://ui-avatars.com/api/?name=' . urlencode($user->name ?? 'User') . '&color=7F9CF5&background=EBF4FF')
                        : 'https://ui-avatars.com/api/?name=User&color=7F9CF5&background=EBF4FF';
                    return [
                        'id'             => $member->id,
                        'course_id'      => $member->course_id,
                        'group_id'       => null,
                        'user_id'        => $member->user_id,
                        'member_name'    => $member->member_name,
                        'member_code'    => $member->member_code,
                        'order_number'   => $member->order_number,
                        'achieved_score' => $member->achieved_score ?? 0,
                        'bonus_points'   => $member->bonus_points ?? 0,
                        'role'           => $member->role,
                        'status'         => $member->status,
                        'enrollment_date'   => $member->enrollment_date,
                        'last_activity_at'  => $member->last_accessed_at,
                        'lessons_completed' => $member->lessons_completed ?? 0,
                        'attendance_rate'   => $member->attendance_rate ?? 0,
                        'user'           => $user ? [
                            'id'     => $user->id,
                            'name'   => $user->name,
                            'avatar' => $avatarUrl,
                            'email'  => $user->email,
                        ] : null,
                        'avatar'         => $avatarUrl,
                        'name'           => $member->member_name ?? $user?->name ?? 'Unknown User',
                        'group'          => null,
                    ];
                }),
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
