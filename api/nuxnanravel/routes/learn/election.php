<?php

use App\Http\Controllers\Api\Learn\Academy\ElectionController;
use App\Http\Controllers\Api\Learn\Academy\ElectionPartyController;
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
    Route::post('/{election}/voter-roll/lock', [ElectionVoterRollController::class, 'lock'])->middleware('academy.permission:elections.manage');
    Route::get('/{election}/voter-roll', [ElectionVoterRollController::class, 'index'])->middleware('academy.permission:elections.manage');
    Route::get('/{election}/voter-roll/stats', [ElectionVoterRollController::class, 'stats'])->middleware('academy.permission:elections.view');
    Route::post('/{election}/parties', [ElectionPartyController::class, 'store'])->middleware('academy.permission:elections.view');
    Route::put('/{election}/parties/{party}', [ElectionPartyController::class, 'update'])->middleware('academy.permission:elections.view');
    Route::post('/{election}/parties/{party}/withdraw', [ElectionPartyController::class, 'withdraw'])->middleware('academy.permission:elections.view');
    Route::get('/{election}/parties', [ElectionPartyController::class, 'index'])->middleware('academy.permission:elections.manage');
    Route::post('/{election}/parties/{party}/approve', [ElectionPartyController::class, 'approve'])->middleware('academy.permission:elections.manage');
    Route::post('/{election}/parties/{party}/reject', [ElectionPartyController::class, 'reject'])->middleware('academy.permission:elections.manage');
});
