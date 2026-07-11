<?php

namespace App\Http\Controllers\Api\Play\Typing;

use App\Http\Controllers\Controller;
use App\Models\TypingDailyChallenge;
use App\Models\TypingSession;
use App\Models\TypingUserDailyChallenge;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TypingDailyChallengeController extends Controller
{
    public function today()
    {
        $challenge = TypingDailyChallenge::whereDate('challenge_date', today())->first();

        if (! $challenge) {
            return response()->json(['success' => false, 'message' => 'No challenge today.'], 404);
        }

        $userEntry = null;
        if (Auth::check()) {
            $userEntry = TypingUserDailyChallenge::where([
                'user_id' => Auth::id(),
                'challenge_id' => $challenge->id,
            ])->first();
        }

        return response()->json([
            'success' => true,
            'challenge' => $challenge,
            'completed' => $userEntry?->completed ?? false,
            'user_score' => $userEntry?->score,
        ]);
    }

    public function complete(Request $request, TypingDailyChallenge $challenge)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'session_id' => 'required|exists:typing_sessions,id',
        ]);

        return DB::transaction(function () use ($validated, $user, $challenge) {
            $session = TypingSession::lockForUpdate()->findOrFail($validated['session_id']);
            $sessionAlreadyUsed = TypingUserDailyChallenge::where('session_id', $session->id)->exists();
            $existing = TypingUserDailyChallenge::where([
                'user_id' => $user->id,
                'challenge_id' => $challenge->id,
            ])->lockForUpdate()->first();

            if ($existing?->completed) {
                return response()->json(['success' => false, 'message' => 'Already completed today.'], 422);
            }

            if ((int) $session->user_id !== (int) $user->id
                || $session->game_mode !== 'daily_challenge'
                || (int) $session->challenge_id !== (int) $challenge->id
                || ! $challenge->challenge_date->isToday()
                || $sessionAlreadyUsed) {
                throw ValidationException::withMessages([
                    'session_id' => ['The session is not eligible for this daily challenge.'],
                ]);
            }

            $completed = $session->wpm >= $challenge->target_wpm
                && $session->accuracy >= $challenge->target_acc;

            TypingUserDailyChallenge::updateOrCreate(
                ['user_id' => $user->id, 'challenge_id' => $challenge->id],
                [
                    'session_id' => $session->id,
                    'completed' => $completed,
                    'score' => $session->score,
                    'wpm' => $session->wpm,
                    'accuracy' => $session->accuracy,
                    'completed_at' => $completed ? now() : null,
                ]
            );

            if ($completed) {
                $pointsService = app(PointsService::class);
                $pointsService->addXp($user, $challenge->xp_reward);
                $ppTransaction = $pointsService->awardGoverned(
                    $user,
                    $challenge->pp_reward,
                    'typing_daily_challenge',
                    "daily_challenge:{$challenge->id}:{$user->id}",
                    $challenge->id,
                    "Daily Typing Challenge: {$challenge->challenge_date->toDateString()}",
                    ['session_id' => $session->id]
                );
            }

            $ppAwarded = isset($ppTransaction) ? (float) $ppTransaction->amount : 0;

            return response()->json([
                'success' => true,
                'completed' => $completed,
                'rewards' => $completed ? [
                    'xp' => $challenge->xp_reward,
                    'pp' => $ppAwarded,
                ] : null,
            ]);
        });
    }
}
