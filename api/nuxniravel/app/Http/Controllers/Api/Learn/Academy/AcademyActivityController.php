<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;


use App\Models\Academy;
use App\Models\Activity;
use App\Models\AcademyPost;
use Illuminate\Http\Request;
use App\Http\Resources\AcademyResource;
use App\Http\Resources\ActivityResource;

class AcademyActivityController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

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

    public function getActivities(Academy $academy)
    {
        // $activities = Activity::whereHasMorph('activityable', ['App\Models\AcademyPost'], function ($query) use ($academy) {
        //         $query->where('academy_id', $academy->id);
        // })->latest()->paginate();
        $activities = Activity::whereHasMorph('activityable', [AcademyPost::class], function ($query) use ($academy) {
                $query->where('academy_id', $academy->id);
        })->latest()->paginate();

        return response()->json([
            'success' => true,
            'activities' => ActivityResource::collection($activities),
        ]);
        // {
        //     $activities = Activity::whereHasMorph('activityable', 
        //         ['App\Models\AcademyPost'],
        //         function ($query) {
        //         $query->whereIn('privacy_settings', [2,3]);
        //     })->latest()->paginate();
    
        //     return response()->json([
        //         'success' => true,
        //         'activities' => ActivityResource::collection($activities),
        //     ]);
        // }
    }
}
