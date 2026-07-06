<?php

use App\Http\Controllers\Api\GameScoreController;
use App\Http\Controllers\Api\Play\Game\GameController;
use App\Http\Controllers\Api\Play\Typing\Admin\AdminTypingSentenceController;
use App\Http\Controllers\Api\Play\Typing\Admin\AdminTypingWordController;
use App\Http\Controllers\Api\Play\Typing\TypingAchievementController;
use App\Http\Controllers\Api\Play\Typing\TypingClassroomController;
use App\Http\Controllers\Api\Play\Typing\TypingDailyChallengeController;
use App\Http\Controllers\Api\Play\Typing\TypingLeaderboardController;
use App\Http\Controllers\Api\Play\Typing\TypingRaceController;
use App\Http\Controllers\Api\Play\Typing\TypingSessionController;
use App\Http\Controllers\Api\Play\Typing\TypingTournamentController;
use App\Http\Controllers\Api\Play\Typing\TypingWordController;
use Illuminate\Support\Facades\Route;

Route::get('/game/guessing-number', [GameController::class, 'guessing_number_game'])->name('game.quessing_number');
Route::get('/game/xo', [GameController::class, 'xo_game'])->name('game.xo');
Route::get('/game/snake', [GameController::class, 'snake_game'])->name('game.snake');
Route::get('/game/mental-match', [GameController::class, 'mental_match_game'])->name('game.mental_match');

// Game Scores and Leaderboards
Route::get('/game/scores', [GameScoreController::class, 'index'])->name('game.scores.index');
Route::post('/game/scores', [GameScoreController::class, 'store'])->name('game.scores.store');

// ── Typing Game Routes ───────────────────────────────────────────

// Public routes
Route::get('/typing/words', [TypingWordController::class, 'index'])->name('typing.words.index');
Route::get('/typing/sentences', [TypingWordController::class, 'sentences'])->name('typing.sentences.index');
Route::get('/typing/leaderboard', [TypingLeaderboardController::class, 'index'])->name('typing.leaderboard');
Route::get('/typing/daily', [TypingDailyChallengeController::class, 'today'])->name('typing.daily.today');
Route::get('/typing/tournaments', [TypingTournamentController::class, 'index']);
Route::get('/typing/tournaments/{tournament}', [TypingTournamentController::class, 'show']);

// Authenticated routes
Route::middleware('auth:api')->group(function () {
    Route::post('/typing/tournaments/{tournament}/attempt', [TypingTournamentController::class, 'attempt']);
    Route::post('/typing/tournaments/{tournament}/claim', [TypingTournamentController::class, 'claim']);

    Route::post('/typing/sessions', [TypingSessionController::class, 'store'])->name('typing.sessions.store');
    Route::post('/typing/daily/{challenge}/complete', [TypingDailyChallengeController::class, 'complete'])->name('typing.daily.complete');
    Route::get('/typing/sessions/history', [TypingSessionController::class, 'history'])->name('typing.sessions.history');
    Route::get('/typing/sessions/stats', [TypingSessionController::class, 'stats'])->name('typing.sessions.stats');
    Route::get('/typing/sessions/best', [TypingSessionController::class, 'best'])->name('typing.sessions.best');
    Route::get('/typing/sessions/wpm-history', [TypingSessionController::class, 'wpmHistory']);
    Route::get('/typing/classroom/{academyId}/report', [TypingClassroomController::class, 'report'])->name('typing.classroom.report');

    // Achievement routes
    Route::get('/typing/achievements', [TypingAchievementController::class, 'index'])->name('typing.achievements.index');
    Route::get('/typing/achievements/mine', [TypingAchievementController::class, 'mine'])->name('typing.achievements.mine');

    // Race Room routes
    Route::post('/typing/race/rooms', [TypingRaceController::class, 'createRoom']);
    Route::post('/typing/race/rooms/{code}/join', [TypingRaceController::class, 'joinRoom']);
    Route::post('/typing/race/rooms/{code}/start', [TypingRaceController::class, 'startRace']);
    Route::post('/typing/race/rooms/{code}/submit', [TypingRaceController::class, 'submitResult']);
    Route::delete('/typing/race/rooms/{code}/leave', [TypingRaceController::class, 'leaveRoom']);
    Route::get('/typing/race/rooms/{code}', [TypingRaceController::class, 'roomStatus']);

    // Admin routes
    Route::prefix('admin')->group(function () {
        Route::apiResource('typing/words', AdminTypingWordController::class);
        Route::apiResource('typing/sentences', AdminTypingSentenceController::class);
        Route::post('typing/words/import', [AdminTypingWordController::class, 'import']);
    });
});
