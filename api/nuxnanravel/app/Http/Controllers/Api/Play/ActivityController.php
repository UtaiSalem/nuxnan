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
        // friends เป็น morphMany จริง (friendships as sender) ⇒ withCount แทน friends()->count() ได้ตรง
        return fn ($q) => $q->withCardCounts();
    }

    /**
     * eager-load ความสัมพันธ์ของ activityable แบบ batch ต่อชนิด (เลี่ยง N+1 ในฟีด)
     *
     * comment 3 ล่าสุด/โพสต์ preload ตรงนี้เลย: Laravel 11+ รองรับ per-parent limit บน eager load
     * (window function บน MySQL) → getComments() ในโมเดลจะใช้ relation ที่โหลดแล้ว = 0 คิวรี/โพสต์
     * (เดิมเชื่อว่า batch limit รวมทั้งชุดจึงต้องวน .load รายโพสต์ — ไม่จริงบน L11+ แล้ว จึงตัด loop ทิ้ง)
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
                'postComments' => fn ($q) => $q->latest()->limit(3)->with([
                    'user' => $userCounts,
                    'postCommentImages',
                    'likedPostComment' => fn ($q2) => $q2->where('user_id', $authId),
                    'dislikedPostComment' => fn ($q2) => $q2->where('user_id', $authId),
                ]),
            ],
            'App\Models\CoursePost' => [
                'user' => $userCounts,
                'post_images',
                'poll.options', 'poll.user',
                'course:id,name,code,slug', 'academy:id,name',
                'likedPost' => fn ($q) => $q->where('user_id', $authId),
                'dislikedPost' => fn ($q) => $q->where('user_id', $authId),
                'post_comments' => fn ($q) => $q->latest()->limit(3)->with([
                    'user' => $userCounts,
                    'postCommentImages',
                    'comment_likes' => fn ($q2) => $q2->where('user_id', $authId),
                    'comment_dislikes' => fn ($q2) => $q2->where('user_id', $authId),
                ]),
            ],
            'App\Models\DonateRecipient' => ['reciever', 'donation'],
            'App\Models\Share' => [
                'user' => $userCounts,
                'shareable.user',
                'shareComments' => fn ($q) => $q->latest()->limit(3)->with('user'),
            ],
        ]);
    }

    /**
     * Display a listing of all activities (admin/general use).
     */
    public function index()
    {
        $activities = Activity::with(['activityable', 'user' => $this->feedUserCounts()])
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
            ->with(['activityable', 'user' => $this->feedUserCounts()])
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
            ->with(['activityable', 'user' => $this->feedUserCounts()])
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
