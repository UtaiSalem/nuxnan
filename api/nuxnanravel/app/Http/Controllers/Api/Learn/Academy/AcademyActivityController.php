<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;


use App\Models\Academy;
use App\Models\Activity;
use App\Models\AcademyPost;
use Illuminate\Http\Request;
use App\Http\Resources\AcademyResource;
use App\Http\Resources\Play\ActivityResource;

class AcademyActivityController extends Controller
{
    public function index(Academy $academy)
    {
        $isAcademyAdmin = $academy->user_id == auth()->id();

        $activities = Activity::whereHasMorph('activityable', [AcademyPost::class], function ($query) use ($academy) {
                $query->where('academy_id', $academy->id);
        })->latest()->paginate();

        return response()->json([
            'academy'               => new AcademyResource($academy),
            'isAcademyAdmin'        => $isAcademyAdmin,
            'activities'            => ActivityResource::collection($activities),
        ]);
    }

    public function getActivities(Academy $academy, Request $request)
    {
        $perPage = $request->input('per_page', 15);
        
        // Eager load all necessary relationships for FeedPost component
        $activities = Activity::with([
            'user',
            'activityable.user',
            'activityable.academy',
            'activityable.images',
            'activityable.comments' => function ($cq) {
                $cq->with('user')->latest()->limit(3);
            },
        ])
        ->whereHasMorph('activityable', [AcademyPost::class], function ($query) use ($academy) {
            $query->where('academy_id', $academy->id);
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
