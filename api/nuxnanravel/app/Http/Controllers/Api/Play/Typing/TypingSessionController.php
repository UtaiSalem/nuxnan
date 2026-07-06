<?php

namespace App\Http\Controllers\Api\Play\Typing;

use App\Http\Controllers\Controller;
use App\Models\TypingSession;
use App\Services\PointsService;
use App\Services\TypingScoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TypingSessionController extends Controller
{
    protected $scoreService;

    protected $pointsService;

    public function __construct(TypingScoreService $scoreService, PointsService $pointsService)
    {
        $this->scoreService = $scoreService;
        $this->pointsService = $pointsService;
    }

    /**
     * Store a completed typing session
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'session_token' => 'required|string|unique:typing_sessions',
            'game_mode' => 'required|in:word_typing,time_attack,sentence_typing,monster_battle,falling_words,classroom_race,daily_challenge,key_training,letter_runner',
            'language' => 'required|in:th,en,ar',
            'difficulty' => 'required|in:beginner,easy,normal,hard,expert',
            'correct_chars' => 'required|integer|min:0',
            'total_chars' => 'required|integer|min:0',
            'correct_words' => 'required|integer|min:0',
            'total_words' => 'required|integer|min:0',
            'mistakes' => 'required|integer|min:0',
            'max_combo' => 'required|integer|min:0',
            'time_elapsed' => 'required|integer|min:1',
            'time_limit' => 'nullable|integer',
            'classroom_id' => 'nullable|exists:academies,id',
            'challenge_id' => 'nullable|exists:typing_daily_challenges,id',
        ]);

        $user = Auth::user();

        $wpm = $this->scoreService->calculateWpm($data['correct_chars'], $data['time_elapsed']);
        $accuracy = $this->scoreService->calculateAccuracy($data['correct_chars'], $data['total_chars']);
        $scores = $this->scoreService->calculateScore([...$data, 'wpm' => $wpm, 'accuracy' => $accuracy]);
        $xp = $this->scoreService->calculateXp($scores['score'], $wpm, $accuracy, $data['difficulty']);

        $session = TypingSession::create([
            ...$data,
            'user_id' => $user?->id,
            'wpm' => $wpm,
            'raw_wpm' => $wpm,
            'accuracy' => $accuracy,
            'score' => $scores['score'],
            'speed_bonus' => $scores['speed_bonus'],
            'combo_bonus' => $scores['combo_bonus'],
            'accuracy_bonus' => $scores['accuracy_bonus'],
            'xp_earned' => $xp,
            'completed' => true,
            'completed_at' => now(),
        ]);

        $newAchievements = [];

        if ($user) {
            $this->pointsService->addXp($user, $xp);

            if ($scores['score'] > 0) {
                $ppAmount = floor($scores['score'] / 100);
                if ($ppAmount > 0) {
                    $this->pointsService->earn($user, $ppAmount, 'typing_game', $session->id, "Typing Game: {$data['game_mode']}");
                }
            }

            $newAchievements = $this->scoreService->checkAchievements($user, $session);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $session->id,
                'wpm' => $wpm,
                'accuracy' => $accuracy,
                'score' => $scores['score'],
                'speed_bonus' => $scores['speed_bonus'],
                'combo_bonus' => $scores['combo_bonus'],
                'accuracy_bonus' => $scores['accuracy_bonus'],
                'xp_earned' => $xp,
                'new_achievements' => $newAchievements,
            ],
        ]);
    }

    /**
     * Get session history for the user
     */
    public function history(Request $request): JsonResponse
    {
        $user = Auth::user();
        $history = TypingSession::where('user_id', $user->id)
            ->latest()
            ->paginate($request->get('limit', 10));

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Get aggregated stats for the user
     */
    public function stats(): JsonResponse
    {
        $user = Auth::user();
        $stats = TypingSession::where('user_id', $user->id)
            ->selectRaw('
                COUNT(*) as total_sessions,
                AVG(wpm) as avg_wpm,
                MAX(wpm) as max_wpm,
                AVG(accuracy) as avg_accuracy,
                SUM(score) as total_score,
                SUM(xp_earned) as total_xp
            ')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get WPM history for the user (for charts)
     */
    public function wpmHistory(Request $request): JsonResponse
    {
        $userId = $request->get('user_id', Auth::id());

        if (! $userId) {
            return response()->json(['success' => false, 'message' => 'User ID required'], 400);
        }

        $days = min((int) $request->get('days', 30), 90);

        $history = TypingSession::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays($days))
            ->where('completed', true)
            ->selectRaw('DATE(created_at) as date, AVG(wpm) as wpm, MAX(wpm) as max_wpm, COUNT(*) as sessions')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json(['success' => true, 'data' => $history]);
    }

    /**
     * Get best scores per mode and overall stats
     */
    public function best(): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $bests = TypingSession::where('user_id', $user->id)
            ->where('completed', true)
            ->selectRaw('game_mode, MAX(wpm) as wpm, MAX(score) as score, MAX(accuracy) as accuracy')
            ->groupBy('game_mode')
            ->get()
            ->keyBy('game_mode');

        return response()->json([
            'success' => true,
            'data' => [
                'best_by_mode' => $bests,
                'total_sessions' => TypingSession::where('user_id', $user->id)->count(),
                'max_wpm' => $bests->max('wpm') ?? 0,
                'avg_wpm' => TypingSession::where('user_id', $user->id)->avg('wpm') ?? 0,
                'avg_accuracy' => TypingSession::where('user_id', $user->id)->avg('accuracy') ?? 0,
                'total_xp' => TypingSession::where('user_id', $user->id)->sum('xp_earned') ?? 0,
            ],
        ]);
    }
}
