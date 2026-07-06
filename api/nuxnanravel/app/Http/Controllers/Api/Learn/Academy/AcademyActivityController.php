<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Academy\AcademyResource;
use App\Http\Resources\Play\ActivityResource;
use App\Models\Academy;
use App\Models\AcademyPost;
use App\Models\Activity;
use Illuminate\Http\Request;

class AcademyActivityController extends Controller
{
    public function index(Academy $academy, Request $request)
    {
        $isAcademyAdmin = $academy->user_id == auth()->id();
        $userId = auth()->id();
        $filterType = $request->input('filter_type');
        $groupType = $request->input('group_type');

        $activities = Activity::with([
            'user',
            'activityable.user',
            'activityable.academy',
            'activityable.images',
            'activityable.postedAsGroup',
            'activityable.comments' => function ($cq) {
                $cq->with('user')->latest()->limit(3);
            },
        ])
            ->whereHasMorph('activityable', [AcademyPost::class], function ($query) use ($academy, $userId, $filterType, $groupType) {
                $query->where('academy_id', $academy->id);

                // กรองตามประเภทของโพสต์ (เช่น announcement, event)
                if ($filterType && $filterType !== 'all') {
                    $query->where('post_type', $filterType);
                }

                // กรองตามประเภทกลุ่มย่อย
                if ($groupType) {
                    $query->whereHas('postedAsGroup', function ($g) use ($groupType) {
                        $g->where('type', $groupType);
                    });
                }

                // ซ่อนโพสต์จากกลุ่มที่กด Mute ไว้
                if ($userId) {
                    $query->where(function ($q) use ($userId) {
                        $q->whereNull('posted_as_group_id')
                            ->orWhereNotIn('posted_as_group_id', function ($sub) use ($userId) {
                                $sub->select('academy_group_id')
                                    ->from('user_muted_groups')
                                    ->where('user_id', $userId);
                            });
                    });
                }
            })
            ->latest()
            ->paginate();

        return response()->json([
            'academy' => new AcademyResource($academy),
            'isAcademyAdmin' => $isAcademyAdmin,
            'activities' => ActivityResource::collection($activities),
        ]);
    }

    public function getActivities(Academy $academy, Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $userId = auth()->id();
        $filterType = $request->input('filter_type');
        $groupType = $request->input('group_type');

        $activities = Activity::with([
            'user',
            'activityable.user',
            'activityable.academy',
            'activityable.images',
            'activityable.postedAsGroup',
            'activityable.comments' => function ($cq) {
                $cq->with('user')->latest()->limit(3);
            },
        ])
            ->whereHasMorph('activityable', [AcademyPost::class], function ($query) use ($academy, $userId, $filterType, $groupType) {
                $query->where('academy_id', $academy->id);

                // กรองตามประเภทของโพสต์
                if ($filterType && $filterType !== 'all') {
                    $query->where('post_type', $filterType);
                }

                // กรองตามประเภทกลุ่มย่อย
                if ($groupType) {
                    $query->whereHas('postedAsGroup', function ($g) use ($groupType) {
                        $g->where('type', $groupType);
                    });
                }

                // ซ่อนโพสต์จากกลุ่มที่กด Mute ไว้
                if ($userId) {
                    $query->where(function ($q) use ($userId) {
                        $q->whereNull('posted_as_group_id')
                            ->orWhereNotIn('posted_as_group_id', function ($sub) use ($userId) {
                                $sub->select('academy_group_id')
                                    ->from('user_muted_groups')
                                    ->where('user_id', $userId);
                            });
                    });
                }
            })
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'activities' => [
                'data' => ActivityResource::collection($activities->items()),
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'total' => $activities->total(),
                'per_page' => $activities->perPage(),
                'next_page_url' => $activities->nextPageUrl(),
                'prev_page_url' => $activities->previousPageUrl(),
            ],
        ]);
    }
}
