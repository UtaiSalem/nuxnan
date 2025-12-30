<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Play\Game\GameController;

Route::get('/game/guessing-number', [GameController::class, 'guessing_number_game'])->name('game.quessing_number');
Route::get('/game/xo', [GameController::class, 'xo_game'])->name('game.xo');
Route::get('/game/snake', [GameController::class, 'snake_game'])->name('game.snake');
Route::get('/game/mental-match', [GameController::class, 'mental_match_game'])->name('game.mental_match');
