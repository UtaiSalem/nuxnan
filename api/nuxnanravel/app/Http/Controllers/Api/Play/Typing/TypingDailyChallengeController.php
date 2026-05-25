<?php
namespace App\Http\Controllers\Api\Play\Typing;

use App\Http\Controllers\Controller;
use App\Models\TypingDailyChallenge;
use App\Models\TypingUserDailyChallenge;
use App\Models\TypingSession;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TypingDailyChallengeController extends Controller
{
    public function today()
    {
        $challenge = TypingDailyChallenge::whereDate('challenge_date', today())->first();

        if (!$challenge) {
            return response()->json(['success' => false, 'message' => 'No challenge today.'], 404);
        }

        $userEntry = null;
        if (Auth::check()) {
            $userEntry = TypingUserDailyChallenge::where([
                'user_id'      => Auth::id(),
                'challenge_id' => $challenge->id,
            ])->first();
        }

        return response()->json([
            'success'    => true,
            'challenge'  => $challenge,
            'completed'  => $userEntry?->completed ?? false,
            'user_score' => $userEntry?->score ?? null,
        ]);
    }

    public function complete(Request $request, TypingDailyChallenge $challenge)
    {
        $user = Auth::user();

        // ตรวจว่าทำแล้วหรือยัง
        $existing = TypingUserDailyChallenge::where([
            'user_id'      => $user->id,
            'challenge_id' => $challenge->id,
        ])->first();

        if ($existing?->completed) {
            return response()->json(['success' => false, 'message' => 'Already completed today.'], 422);
        }

        $validated = $request->validate([
            'session_id' => 'required|exists:typing_sessions,id',
            'score'      => 'required|integer|min:0',
            'wpm'        => 'required|integer|min:0',
            'accuracy'   => 'required|numeric|min:0|max:100',
        ]);

        $completed = $validated['wpm'] >= $challenge->target_wpm
                  && $validated['accuracy'] >= $challenge->target_acc;

        TypingUserDailyChallenge::updateOrCreate(
            ['user_id' => $user->id, 'challenge_id' => $challenge->id],
            [
                'session_id'   => $validated['session_id'],
                'completed'    => $completed,
                'score'        => $validated['score'],
                'wpm'          => $validated['wpm'],
                'accuracy'     => $validated['accuracy'],
                'completed_at' => $completed ? now() : null,
            ]
        );

        // มอบรางวัลเมื่อผ่าน
        if ($completed) {
            app(PointsService::class)->addXp($user, $challenge->xp_reward);
            app(PointsService::class)->earn(
                $user,
                $challenge->pp_reward,
                'daily_challenge',
                $challenge->id,
                "Daily Typing Challenge: {$challenge->challenge_date}"
            );
        }

        return response()->json([
            'success'   => true,
            'completed' => $completed,
            'rewards'   => $completed ? [
                'xp' => $challenge->xp_reward,
                'pp' => $challenge->pp_reward,
            ] : null,
        ]);
    }
}
