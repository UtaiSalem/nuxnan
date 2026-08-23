<?php

use App\Http\Controllers\Api\Learn\Academy\AcademyMemberController;
use App\Http\Controllers\Api\Learn\Academy\ElectionController;
use App\Http\Controllers\Api\Learn\Academy\ElectionPartyController;
use App\Http\Controllers\Api\Learn\Academy\ElectionStationController;
use App\Http\Controllers\Api\Learn\Academy\ElectionVoterRollController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('academies/{academy}/elections')->group(function () {
    Route::get('/', [ElectionController::class, 'index'])->middleware('academy.permission:elections.view');
    Route::get('/{election}', [ElectionController::class, 'show'])->middleware('academy.permission:elections.view');
    Route::get('/{election}/audit-log', [ElectionController::class, 'auditLog'])->middleware('academy.permission:elections.view');
    Route::post('/', [ElectionController::class, 'store'])->middleware('academy.permission:elections.manage');
    Route::put('/{election}', [ElectionController::class, 'update'])->middleware('academy.permission:elections.manage');
    Route::delete('/{election}', [ElectionController::class, 'destroy'])->middleware('academy.permission:elections.manage');
    Route::post('/{election}/status', [ElectionController::class, 'transitionStatus'])->middleware('academy.permission:elections.manage');
    Route::post('/{election}/close-and-count', [ElectionController::class, 'closeAndCount'])->middleware('academy.permission:elections.manage');
    Route::post('/{election}/publish', [ElectionController::class, 'publish'])->middleware('academy.permission:elections.manage');
    Route::get('/{election}/results', [ElectionController::class, 'results'])->middleware('academy.permission:elections.view');
    Route::get('/{election}/turnout', [ElectionController::class, 'turnout'])->middleware('academy.permission:elections.view');
    Route::post('/{election}/cast', [ElectionStationController::class, 'cast'])->middleware('throttle:30,1');
    Route::post('/{election}/voter-roll/lock', [ElectionVoterRollController::class, 'lock'])->middleware('academy.permission:elections.manage');
    Route::get('/{election}/voter-roll', [ElectionVoterRollController::class, 'index'])->middleware('academy.permission:elections.manage');
    Route::get('/{election}/voter-roll/stats', [ElectionVoterRollController::class, 'stats'])->middleware('academy.permission:elections.view');
    Route::post('/{election}/parties', [ElectionPartyController::class, 'store'])->middleware('academy.permission:elections.view');
    Route::put('/{election}/parties/{party}', [ElectionPartyController::class, 'update'])->middleware('academy.permission:elections.view');
    Route::post('/{election}/parties/{party}/withdraw', [ElectionPartyController::class, 'withdraw'])->middleware('academy.permission:elections.view');
    Route::get('/{election}/parties', [ElectionPartyController::class, 'index'])->middleware('academy.permission:elections.manage');
    Route::post('/{election}/parties/{party}/approve', [ElectionPartyController::class, 'approve'])->middleware('academy.permission:elections.manage');
    Route::post('/{election}/parties/{party}/reject', [ElectionPartyController::class, 'reject'])->middleware('academy.permission:elections.manage');
    Route::post('/{election}/stations', [ElectionStationController::class, 'store'])->middleware('academy.permission:elections.manage');
    Route::get('/{election}/stations', [ElectionStationController::class, 'index'])->middleware('academy.permission:elections.manage');
    Route::put('/{election}/stations/{station}', [ElectionStationController::class, 'update'])->middleware('academy.permission:elections.manage');
    Route::delete('/{election}/stations/{station}', [ElectionStationController::class, 'destroy'])->middleware('academy.permission:elections.manage');
    Route::post('/{election}/stations/{station}/open', [ElectionStationController::class, 'open'])->middleware('academy.permission:elections.station');
    Route::post('/{election}/stations/{station}/close', [ElectionStationController::class, 'close'])->middleware('academy.permission:elections.station');
    Route::post('/{election}/stations/{station}/lookup', [ElectionStationController::class, 'lookup'])->middleware(['academy.permission:elections.station', 'throttle:60,1']);
    Route::get('/{election}/stations/{station}/search', [ElectionStationController::class, 'search'])->middleware('academy.permission:elections.station');
    Route::post('/{election}/stations/{station}/issue', [ElectionStationController::class, 'issue'])->middleware(['academy.permission:elections.station', 'throttle:30,1']);
    Route::post('/{election}/stations/{station}/void', [ElectionStationController::class, 'void'])->middleware('academy.permission:elections.station');
    Route::get('/{election}/stations/{station}/progress', [ElectionStationController::class, 'progress'])->middleware('academy.permission:elections.station');
});

Route::put('academies/{academy}/members/{member}/education-level', [AcademyMemberController::class, 'updateEducationLevel'])
    ->middleware(['auth:api', 'academy.permission:elections.manage']);
