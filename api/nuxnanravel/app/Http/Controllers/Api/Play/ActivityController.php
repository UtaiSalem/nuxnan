<?php

namespace App\Http\Controllers\Api\Play;

use App\Http\Controllers\Controller;
use App\Http\Resources\Play\ActivityResource;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    /**
     * closure สำหรับ eager-load ตัวนับของผู้ใช้ + roles ครั้งเดียว
     * UserResource อ่าน posts_count/followers_count/following_count จาก withCount ถ้ามี (ไม่งั้น query รายคน)
     * และ isSuperAdmin (hasRole) ใช้ roles ที่โหลดไว้ ⇒ เลี่ยง N+1 รายผู้ใช้ในฟีด
     */
    protected function feedUserCounts(): \Closure
    {
        return fn ($q) => $q->withCount(['posts', 'followers', 'following'])->with(['roles', 'plearndAdmin']);
    }

    /**
     * eager-load ความสัมพันธ์ของ activityable แบบ batch ต่อชนิด (เลี่ยง N+1 ในฟีด)
     * shareComments จำกัด 3 ต่อโพสต์จึงต้องโหลดรายรายการ (eager load แบบ batch จะ limit รวมทั้งชุด)
     */
    protected function loadActivityableForFeed($activities): void
    {
        $authId = Auth::id();
        $userCounts = $this->feedUserCounts();

        $activities->getCollection()->loadMorph('activityable', [
            'App\Models\Post' => [
                'user' => $userCounts,
                'postImages',
                'poll.options', 'poll.user',
                'likedPost' => fn ($q) => $q->where('user_id', $authId),
                'dislikedPost' => fn ($q) => $q->where('user_id', $authId),
            ],
            'App\Models\CoursePost' => [
                'user' => $userCounts,
                'post_images',
                'poll.options', 'poll.user',
                'course:id,name,code', 'academy:id,name',
                'likedPost' => fn ($q) => $q->where('user_id', $authId),
                'dislikedPost' => fn ($q) => $q->where('user_id', $authId),
            ],
            'App\Models\DonateRecipient' => ['reciever', 'donation'],
            'App\Models\Share' => ['user' => $userCounts, 'shareable.user'],
        ]);

        $activities->getCollection()->each(function ($activity) {
            if ($activity->activityable_type === 'App\Models\Share' && $activity->activityable) {
                $activity->activityable->load(['shareComments' => function ($query) {
                    $query->with('user')->latest()->limit(3);
                }]);
            }
        });
    }

    /**
     * Display a listing of all activities (admin/general use).
     */
    public function index()
    {
        $activities = Activity::with(['user' => $this->feedUserCounts()])
            ->latest()
            ->paginate();

        $this->loadActivityableForFeed($activities);

        return response()->json([
            'success' => true,
            'activities' => ActivityResource::collection($activities),
        ], 200);
    }

    /**
     * Get activities for newsfeed page.
     * Filters by activity types: CoursePost, Donate, DonateRecipient
     */
    public function newsfeed(Request $request)
    {
        $perPage = $request->input('per_page', 15);

        $activities = Activity::whereIn('activityable_type', [
            'App\Models\Post',
            'App\Models\CoursePost',
            'App\Models\Donate',
            'App\Models\DonateRecipient',
            'App\Models\Share',
        ])
            ->with(['user' => $this->feedUserCounts()])
            ->latest()
            ->paginate($perPage);

        $this->loadActivityableForFeed($activities);

        return response()->json([
            'success' => true,
            'activities' => ActivityResource::collection($activities),
        ], 200);
    }

    /**
     * Display activities for a specific user.
     */
    public function show(User $user)
    {
        $activities = $user->activities()
            ->with(['user' => $this->feedUserCounts()])
            ->latest()
            ->paginate();

        $this->loadActivityableForFeed($activities);

        return response()->json([
            'success' => true,
            'activities' => ActivityResource::collection($activities),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Activity $activity)
    {
        if ($activity->activityable) {
            $activity->activityable()->delete();
        }
        $activity->delete();

        if (auth()->check()) {
            // auth()->user()->decrement('pp', 1); // Uncomment if 'pp' exists and is needed
        }

        return response()->json([
            'success' => true,
        ], 200);
    }
}
