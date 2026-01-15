<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Play\VideoController;

/*
|--------------------------------------------------------------------------
| Video Routes
|--------------------------------------------------------------------------
*/

// Public routes (with optional auth for privacy filtering)
Route::get('/users/{user}/videos', [VideoController::class, 'userVideos'])->name('user.videos');
Route::get('/videos/{video}', [VideoController::class, 'show'])->name('videos.show');
Route::post('/videos/{video}/view', [VideoController::class, 'recordView'])->name('videos.view');

// Protected routes
Route::middleware(['auth:api'])->group(function () {
    // My videos
    Route::get('/profile/videos', [VideoController::class, 'myVideos'])->name('profile.videos');
    Route::post('/profile/videos', [VideoController::class, 'store'])->name('profile.videos.store');
    Route::put('/profile/videos/{video}', [VideoController::class, 'update'])->name('profile.videos.update');
    Route::delete('/profile/videos/{video}', [VideoController::class, 'destroy'])->name('profile.videos.destroy');
});
