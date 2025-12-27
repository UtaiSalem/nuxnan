<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Learn\Academy\AcademyController;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:api', config('jetstream.auth_session'), 'verified',])->prefix('academies')->group(function () {
    Route::get('/', [AcademyController::class, 'index'])->name('academies');
    Route::get('/create', [AcademyController::class, 'create'])->name('academy.create');
});