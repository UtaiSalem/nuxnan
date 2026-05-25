<?php

namespace App\Http\Controllers\Api\Play\Typing;

use App\Http\Controllers\Controller;
use App\Models\TypingSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class TypingLeaderboardController extends Controller
{
    /**
     * Get leaderboard for typing game
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'game_mode' => 'required|string',
            'language' => 'nullable|in:en,th,ar',
            'period' => 'nullable|in:all,today,week,month',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = TypingSession::with('user:id,name,profile_photo_path')
            ->where('game_mode', $request->game_mode)
            ->where('completed', true);

        if ($request->has('language')) {
            $query->where('language', $request->language);
        }

        // Apply period filter
        if ($request->period === 'today') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($request->period === 'week') {
            $query->where('created_at', '>=', Carbon::now()->startOfWeek());
        } elseif ($request->period === 'month') {
            $query->where('created_at', '>=', Carbon::now()->startOfMonth());
        }

        $limit = $request->get('limit', 10);
        
        // Group by user to get only their best score
        $leaderboard = $query->selectRaw('user_id, MAX(score) as top_score, MAX(wpm) as top_wpm, MAX(accuracy) as top_accuracy')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('top_score')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $leaderboard
        ]);
    }
}
