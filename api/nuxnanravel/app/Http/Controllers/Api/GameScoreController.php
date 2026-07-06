<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameScore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GameScoreController extends Controller
{
    /**
     * Get the leaderboard for a specific game.
     */
    public function index(Request $request)
    {
        $gameType = $request->query('game_type', 'crossmath');

        // Get top 20 unique users with their highest score
        $leaderboard = GameScore::with('user:id,name,avatar,profile_photo_url')
            ->where('game_type', $gameType)
            ->select('user_id', 'player_name', \DB::raw('MAX(score) as score'), \DB::raw('MAX(level) as level'))
            ->groupBy('user_id', 'player_name')
            ->orderByDesc('score')
            ->orderByDesc('level')
            ->limit(20)
            ->get();

        $userRank = null;
        $userScore = null;

        if (Auth::check()) {
            $userScore = GameScore::where('game_type', $gameType)
                ->where('user_id', Auth::id())
                ->first();

            if ($userScore) {
                // Count how many users have a better score
                $userRank = GameScore::where('game_type', $gameType)
                    ->select('user_id', \DB::raw('MAX(score) as max_score'))
                    ->groupBy('user_id')
                    ->having('max_score', '>', $userScore->score)
                    ->get()
                    ->count() + 1;
            }
        }

        return response()->json([
            'leaderboard' => $leaderboard,
            'user_rank' => $userRank,
            'user_best_score' => $userScore,
        ]);
    }

    /**
     * Store a new game score.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'game_type' => 'required|string',
            'session_id' => 'required|string',
            'score' => 'required|integer',
            'level' => 'required|integer',
            'time_spent' => 'required|integer',
            'player_name' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $updateData = [
            'score' => $validated['score'],
            'level' => $validated['level'],
            'time_spent' => $validated['time_spent'],
            'metadata' => $validated['metadata'],
        ];

        if (Auth::check()) {
            $updateData['user_id'] = Auth::id();
            $updateData['player_name'] = Auth::user()->name;
        } else {
            $updateData['player_name'] = $validated['player_name'] ?? 'Guest';
        }

        $score = GameScore::updateOrCreate(
            ['session_id' => $validated['session_id'], 'game_type' => $validated['game_type']],
            $updateData
        );

        return response()->json([
            'success' => true,
            'message' => 'Score saved successfully.',
            'data' => $score,
        ]);
    }
}
