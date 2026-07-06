<?php

namespace App\Http\Controllers\Api\Play;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BadgeController extends Controller
{
    public function getUserBadges(User $user)
    {
        // Get all badges and checking if user has them
        $badges = Badge::all();
        $userBadges = DB::table('user_badges')
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('badge_id');

        $result = $badges->map(function ($badge) use ($userBadges) {
            $userBadge = $userBadges->get($badge->id);

            return [
                'id' => $badge->id,
                'name' => $badge->name,
                'description' => $badge->description,
                'icon' => $badge->icon,
                'icon_color' => $badge->icon_color,
                'background_color' => $badge->background_color,
                'category' => $badge->category,
                'rarity' => $badge->rarity,
                'points' => $badge->points,
                'max_progress' => $badge->max_progress,
                'is_earned' => $userBadge ? (bool) $userBadge->is_earned : false,
                'progress' => $userBadge ? $userBadge->progress : 0,
                'earned_at' => $userBadge && $userBadge->earned_at ? $userBadge->earned_at : null,
            ];
        });

        return response()->json([
            'success' => true,
            'badges' => $result,
        ]);
    }
}
