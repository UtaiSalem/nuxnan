<?php

namespace App\Http\Controllers\Api\Play;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Activity;
use App\Http\Resources\Play\ActivityResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    /**
     * Display a listing of all activities (admin/general use).
     */
    public function index()
    {
        $activities = Activity::with([
                'user', 
                'activityable.user',
            ])
            ->latest()
            ->paginate();

        // Load images based on model type
        $activities->getCollection()->each(function($activity) {
            if ($activity->activityable) {
                if ($activity->activityable_type === 'App\Models\Post') {
                    $activity->activityable->load('postImages');
                } elseif ($activity->activityable_type === 'App\Models\CoursePost') {
                    $activity->activityable->load('post_images');
                }
            }
        });

        // Load Share comments for Share activities
        $activities->getCollection()->each(function($activity) {
            if ($activity->activityable_type === 'App\Models\Share' && $activity->activityable) {
                $activity->activityable->load(['shareComments' => function($query) {
                    $query->with('user')->latest()->limit(3);
                }]);
            }
        });

        return response()->json([
            'success'    => true,
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
            ->with([
                'user', 
                'activityable.user',
            ])
            ->latest()
            ->paginate($perPage);

        // Load images based on model type
        $activities->getCollection()->each(function($activity) {
            if ($activity->activityable) {
                if ($activity->activityable_type === 'App\Models\Post') {
                    $activity->activityable->load('postImages');
                } elseif ($activity->activityable_type === 'App\Models\CoursePost') {
                    $activity->activityable->load('post_images');
                }
            }
        });

        // Load Share comments for Share activities
        $activities->getCollection()->each(function($activity) {
            if ($activity->activityable_type === 'App\Models\Share' && $activity->activityable) {
                $activity->activityable->load([
                    'shareComments' => function($query) {
                        $query->with('user')->latest()->limit(3);
                    },
                    'shareable.user' // Load original post and its author
                ]);
            }
        });

        return response()->json([
            'success'    => true,
            'activities' => ActivityResource::collection($activities),
        ], 200);
    }

    /**
     * Display activities for a specific user.
     */
    public function show(User $user)
    {
        $activities = $user->activities()
            ->with([
                'user', 
                'activityable.user',
            ])
            ->latest()
            ->paginate();

        // Load images based on model type
        $activities->getCollection()->each(function($activity) {
            if ($activity->activityable) {
                if ($activity->activityable_type === 'App\Models\Post') {
                    $activity->activityable->load('postImages');
                } elseif ($activity->activityable_type === 'App\Models\CoursePost') {
                    $activity->activityable->load('post_images');
                }
            }
        });

        // Load Share comments for Share activities
        $activities->getCollection()->each(function($activity) {
            if ($activity->activityable_type === 'App\Models\Share' && $activity->activityable) {
                $activity->activityable->load([
                    'shareComments' => function($query) {
                        $query->with('user')->latest()->limit(3);
                    },
                    'shareable.user' // Load original post and its author
                ]);
            }
        });

        return response()->json([
            'success'    => true,
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

