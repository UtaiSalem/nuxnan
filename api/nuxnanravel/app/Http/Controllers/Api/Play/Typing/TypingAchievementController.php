<?php

namespace App\Http\Controllers\Api\Play\Typing;

use App\Http\Controllers\Controller;
use App\Models\TypingAchievement;
use App\Models\TypingUserAchievement;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TypingAchievementController extends Controller
{
    /**
     * Get all active achievements.
     */
    public function index(): JsonResponse
    {
        $achievements = TypingAchievement::where('is_active', true)
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $achievements,
        ]);
    }

    /**
     * Get user's earned achievements.
     */
    public function mine(): JsonResponse
    {
        $user = Auth::user();

        $myAchievements = TypingUserAchievement::where('user_id', $user->id)
            ->with('achievement')
            ->get()
            ->map(function ($ua) {
                return [
                    'id' => $ua->achievement_id,
                    'name' => $ua->achievement->name,
                    'description' => $ua->achievement->description,
                    'icon' => $ua->achievement->icon,
                    'xp_reward' => $ua->achievement->xp_reward,
                    'earned_at' => $ua->earned_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $myAchievements,
        ]);
    }
}
