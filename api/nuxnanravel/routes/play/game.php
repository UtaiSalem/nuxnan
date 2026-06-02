<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Play\Game\GameController;
use App\Http\Controllers\Api\GameScoreController;
use App\Http\Controllers\Api\Play\Typing\TypingWordController;
use App\Http\Controllers\Api\Play\Typing\TypingSessionController;
use App\Http\Controllers\Api\Play\Typing\TypingLeaderboardController;

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
Route::get('/typing/daily', [\App\Http\Controllers\Api\Play\Typing\TypingDailyChallengeController::class, 'today'])->name('typing.daily.today');
Route::get('/typing/tournaments', [\App\Http\Controllers\Api\Play\Typing\TypingTournamentController::class, 'index']);
Route::get('/typing/tournaments/{tournament}', [\App\Http\Controllers\Api\Play\Typing\TypingTournamentController::class, 'show']);

// Authenticated routes
Route::middleware('auth:api')->group(function () {
    Route::post('/typing/tournaments/{tournament}/attempt', [\App\Http\Controllers\Api\Play\Typing\TypingTournamentController::class, 'attempt']);
    Route::post('/typing/tournaments/{tournament}/claim', [\App\Http\Controllers\Api\Play\Typing\TypingTournamentController::class, 'claim']);
    
    Route::post('/typing/sessions', [TypingSessionController::class, 'store'])->name('typing.sessions.store');
    Route::post('/typing/daily/{challenge}/complete', [\App\Http\Controllers\Api\Play\Typing\TypingDailyChallengeController::class, 'complete'])->name('typing.daily.complete');
    Route::get('/typing/sessions/history', [TypingSessionController::class, 'history'])->name('typing.sessions.history');
    Route::get('/typing/sessions/stats', [TypingSessionController::class, 'stats'])->name('typing.sessions.stats');
    Route::get('/typing/sessions/best', [TypingSessionController::class, 'best'])->name('typing.sessions.best');
    Route::get('/typing/sessions/wpm-history', [TypingSessionController::class, 'wpmHistory']);
    Route::get('/typing/classroom/{academyId}/report', [\App\Http\Controllers\Api\Play\Typing\TypingClassroomController::class, 'report'])->name('typing.classroom.report');
    
    // Achievement routes
    Route::get('/typing/achievements', [\App\Http\Controllers\Api\Play\Typing\TypingAchievementController::class, 'index'])->name('typing.achievements.index');
    Route::get('/typing/achievements/mine', [\App\Http\Controllers\Api\Play\Typing\TypingAchievementController::class, 'mine'])->name('typing.achievements.mine');

    // Race Room routes
    Route::post('/typing/race/rooms', [\App\Http\Controllers\Api\Play\Typing\TypingRaceController::class, 'createRoom']);
    Route::post('/typing/race/rooms/{code}/join', [\App\Http\Controllers\Api\Play\Typing\TypingRaceController::class, 'joinRoom']);
    Route::post('/typing/race/rooms/{code}/start', [\App\Http\Controllers\Api\Play\Typing\TypingRaceController::class, 'startRace']);
    Route::post('/typing/race/rooms/{code}/submit', [\App\Http\Controllers\Api\Play\Typing\TypingRaceController::class, 'submitResult']);
    Route::delete('/typing/race/rooms/{code}/leave', [\App\Http\Controllers\Api\Play\Typing\TypingRaceController::class, 'leaveRoom']);
    Route::get('/typing/race/rooms/{code}', [\App\Http\Controllers\Api\Play\Typing\TypingRaceController::class, 'roomStatus']);

    // Admin routes
    Route::prefix('admin')->group(function () {
        Route::apiResource('typing/words', \App\Http\Controllers\Api\Play\Typing\Admin\AdminTypingWordController::class);
        Route::apiResource('typing/sentences', \App\Http\Controllers\Api\Play\Typing\Admin\AdminTypingSentenceController::class);
        Route::post('typing/words/import', [\App\Http\Controllers\Api\Play\Typing\Admin\AdminTypingWordController::class, 'import']);
    });
});
