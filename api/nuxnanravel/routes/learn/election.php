<?php

use App\Http\Controllers\Api\Learn\Academy\ElectionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('academies/{academy}/elections')->group(function () {
    Route::get('/', [ElectionController::class, 'index'])->middleware('academy.permission:elections.view');
    Route::get('/{election}', [ElectionController::class, 'show'])->middleware('academy.permission:elections.view');
    Route::get('/{election}/audit-log', [ElectionController::class, 'auditLog'])->middleware('academy.permission:elections.view');
    Route::post('/', [ElectionController::class, 'store'])->middleware('academy.permission:elections.manage');
    Route::put('/{election}', [ElectionController::class, 'update'])->middleware('academy.permission:elections.manage');
    Route::delete('/{election}', [ElectionController::class, 'destroy'])->middleware('academy.permission:elections.manage');
    Route::post('/{election}/status', [ElectionController::class, 'transitionStatus'])->middleware('academy.permission:elections.manage');
});
