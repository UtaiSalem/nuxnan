<?php

namespace App\Http\Controllers\Api\Play\Typing;

use App\Http\Controllers\Controller;
use App\Models\TypingSession;
use App\Models\TypingTournament;
use App\Models\TypingTournamentEntry;
use App\Services\PointsService;
use App\Services\TypingScoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TypingTournamentController extends Controller
{
    /**
     * Get list of tournaments (active, upcoming, finished)
     */
    public function index(): JsonResponse
    {
        $active = TypingTournament::active()
            ->withCount('entries')
            ->orderBy('ends_at')
            ->get();

        $upcoming = TypingTournament::upcoming()
            ->withCount('entries')
            ->orderBy('starts_at')
            ->limit(3)
            ->get();

        $finished = TypingTournament::finished()
            ->withCount('entries')
            ->orderByDesc('ends_at')
            ->limit(5)
            ->get();

        // My entries (if logged in)
        $myEntries = [];
        if (Auth::check()) {
            $myEntries = TypingTournamentEntry::where('user_id', Auth::id())
                ->pluck('tournament_id')
                ->toArray();
        }

        return response()->json([
            'success' => true,
            'active' => $active,
            'upcoming' => $upcoming,
            'finished' => $finished,
            'my_entries' => $myEntries,
        ]);
    }

    /**
     * Get tournament details and leaderboard
     */
    public function show(TypingTournament $tournament): JsonResponse
    {
        $leaderboard = $tournament->topEntries(50)->get()
            ->map(fn ($e, $i) => [
                'rank' => $i + 1,
                'user_id' => $e->user_id,
                'name' => $e->user->name,
                'avatar' => $e->user->profile_photo_url ?? $e->user->avatar,
                'best_wpm' => $e->best_wpm,
                'best_acc' => $e->best_accuracy,
                'score' => $e->best_score,
                'attempts' => $e->attempts,
            ]);

        $myEntry = null;
        $myRank = null;
        if (Auth::check()) {
            $myEntry = TypingTournamentEntry::where([
                'tournament_id' => $tournament->id,
                'user_id' => Auth::id(),
            ])->first();

            if ($myEntry) {
                $myRank = TypingTournamentEntry::where('tournament_id', $tournament->id)
                    ->where('best_score', '>', $myEntry->best_score)
                    ->count() + 1;
            }
        }

        return response()->json([
            'success' => true,
            'tournament' => $tournament,
            'leaderboard' => $leaderboard,
            'my_entry' => $myEntry,
            'my_rank' => $myRank,
        ]);
    }

    /**
     * Submit an attempt result for the tournament
     */
    public function attempt(Request $request, TypingTournament $tournament): JsonResponse
    {
        if (! $tournament->isActive()) {
            return response()->json(['success' => false, 'message' => 'Tournament is not active.'], 422);
        }

        $data = $request->validate([
            'session_token' => 'required|string|unique:typing_sessions,session_token',
            'correct_chars' => 'required|integer|min:0',
            'total_chars' => 'required|integer|min:0',
            'correct_words' => 'required|integer|min:0',
            'total_words' => 'required|integer|min:0',
            'mistakes' => 'required|integer|min:0',
            'max_combo' => 'required|integer|min:0',
            'time_elapsed' => 'required|integer|min:1',
        ]);

        $user = Auth::user();
        $lang = $tournament->language === 'any' ? 'en' : $tournament->language;
        $diff = $tournament->difficulty === 'any' ? 'normal' : $tournament->difficulty;
        $scoreService = app(TypingScoreService::class);

        $wpm = $scoreService->calculateWpm($data['correct_chars'], $data['time_elapsed']);
        $accuracy = $scoreService->calculateAccuracy($data['correct_chars'], $data['total_chars']);

        $scoreParams = array_merge($data, [
            'wpm' => $wpm,
            'accuracy' => $accuracy,
            'difficulty' => $diff,
        ]);
        $scores = $scoreService->calculateScore($scoreParams);
        $xp = $scoreService->calculateXp($scores['score'], $wpm, $accuracy, $diff);

        return DB::transaction(function () use ($data, $user, $tournament, $lang, $diff, $wpm, $accuracy, $scores, $xp) {
            // Save session
            $session = TypingSession::create([
                'session_token' => $data['session_token'],
                'user_id' => $user->id,
                'game_mode' => $tournament->game_mode,
                'language' => $lang,
                'difficulty' => $diff,
                'wpm' => $wpm,
                'accuracy' => $accuracy,
                'score' => $scores['score'],
                'speed_bonus' => $scores['speed_bonus'],
                'combo_bonus' => $scores['combo_bonus'],
                'accuracy_bonus' => $scores['accuracy_bonus'],
                'xp_earned' => $xp,
                'correct_chars' => $data['correct_chars'],
                'total_chars' => $data['total_chars'],
                'correct_words' => $data['correct_words'],
                'total_words' => $data['total_words'],
                'mistakes' => $data['mistakes'],
                'max_combo' => $data['max_combo'],
                'time_elapsed' => $data['time_elapsed'],
                'time_limit' => $tournament->time_limit,
                'completed' => true,
                'completed_at' => now(),
            ]);

            // Award XP for every play
            app(PointsService::class)->addXp($user, $xp);

            // Update entry - only keep best score
            $entry = TypingTournamentEntry::firstOrNew([
                'tournament_id' => $tournament->id,
                'user_id' => $user->id,
            ]);

            $isNewBest = $scores['score'] > ($entry->best_score ?? 0);

            $entry->attempts = ($entry->attempts ?? 0) + 1;
            $entry->last_played_at = now();

            if ($isNewBest || ! $entry->exists) {
                $entry->best_session_id = $session->id;
                $entry->best_wpm = $wpm;
                $entry->best_accuracy = $accuracy;
                $entry->best_score = $scores['score'];
            }

            $wasNew = ! $entry->exists;
            $entry->save();

            if ($wasNew) {
                $tournament->increment('total_entries');
            }

            return response()->json([
                'success' => true,
                'wpm' => $wpm,
                'accuracy' => $accuracy,
                'score' => $scores['score'],
                'xp_earned' => $xp,
                'is_new_best' => $isNewBest,
                'attempts' => $entry->attempts,
                'best_score' => $entry->best_score,
            ]);
        });
    }

    /**
     * Claim prizes after tournament is finished
     */
    public function claim(TypingTournament $tournament): JsonResponse
    {
        if (! $tournament->isFinished()) {
            return response()->json(['success' => false, 'message' => 'Tournament has not finished yet.'], 422);
        }

        $user = Auth::user();

        return DB::transaction(function () use ($tournament, $user) {
            $entry = TypingTournamentEntry::where([
                'tournament_id' => $tournament->id,
                'user_id' => $user->id,
            ])->lockForUpdate()->firstOrFail();

            if ($entry->prize_claimed) {
                return response()->json(['success' => false, 'message' => 'Prize already claimed.'], 422);
            }

            if ($entry->rank === null) {
                return response()->json(['success' => false, 'message' => 'Tournament rankings are not finalized.'], 422);
            }

            $prizes = $tournament->getPrizesFor($entry->rank);
            $pointsService = app(PointsService::class);
            $pointsService->addXp($user, $prizes['xp']);
            $ppTransaction = $pointsService->awardGoverned(
                $user,
                $prizes['pp'],
                'typing_tournament_prize',
                "tournament_prize:{$tournament->id}:{$user->id}",
                $tournament->id,
                "Tournament Prize: {$tournament->name} (Rank #{$entry->rank})",
                ['rank' => $entry->rank]
            );
            $ppAwarded = $ppTransaction ? (int) $ppTransaction->amount : 0;

            $entry->update([
                'prize_claimed' => true,
                'prize_xp' => $prizes['xp'],
                'prize_pp' => $ppAwarded,
            ]);

            return response()->json([
                'success' => true,
                'rank' => $entry->rank,
                'prize_xp' => $prizes['xp'],
                'prize_pp' => $ppAwarded,
            ]);
        });
    }
}
