<?php

use App\Http\Controllers\Api\Public\PublicAcademyController;
use App\Http\Controllers\Api\Public\PublicCourseController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:10,1')->prefix('public')->group(function () {
    Route::get('/courses', [PublicCourseController::class, 'index']);
    Route::get('/courses/{course}/support-summary', [PublicCourseController::class, 'supportSummary']);
    Route::get('/courses/{course}', [PublicCourseController::class, 'show']);
    Route::get('/schools', [PublicAcademyController::class, 'index']);
    Route::get('/schools/{academy}/support-summary', [PublicAcademyController::class, 'supportSummary']);
    Route::get('/schools/{academy}', [PublicAcademyController::class, 'show']);
});
