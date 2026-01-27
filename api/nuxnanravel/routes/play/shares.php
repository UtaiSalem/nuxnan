<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\ShareReactionController;
use App\Http\Controllers\ShareCommentController;

/**
 * Share Routes
 * Handle sharing functionality for posts, course posts, etc.
 */
Route::middleware(['auth:api'])->group(function () {
    
    // GET routes (no verified middleware needed for reading)
    // IMPORTANT: More specific routes must come BEFORE wildcard routes
    Route::get('/shares/{id}/comments', [ShareCommentController::class, 'index'])
        ->where('id', '[0-9]+')
        ->name('shares.comments.index');
    Route::get('/shares/{type}/{id}', [ShareController::class, 'shares'])->name('shares.list');
    
    // Share reactions (auth only, no verified needed)
    Route::post('/shares/{id}/like', [ShareReactionController::class, 'toggleLike'])->name('shares.like');
    Route::post('/shares/{id}/dislike', [ShareReactionController::class, 'toggleDislike'])->name('shares.dislike');
    
    // Share comments - create & reactions (auth only)
    Route::post('/shares/{id}/comments', [ShareCommentController::class, 'store'])->name('shares.comments.store');
    Route::post('/share-comments/{id}/like', [ShareCommentController::class, 'like'])->name('shares.comments.like');
    Route::post('/share-comments/{id}/dislike', [ShareCommentController::class, 'dislike'])->name('shares.comments.dislike');
    
});

Route::middleware(['auth:api', 'verified'])->group(function () {
    
    // Create share (needs verified for creating new content)
    Route::post('/shares', [ShareController::class, 'store'])->name('shares.store');
    
    // Delete share
    Route::delete('/shares/{id}', [ShareController::class, 'destroy'])->name('shares.destroy');
    
    // Delete comment
    Route::delete('/share-comments/{id}', [ShareCommentController::class, 'destroy'])->name('shares.comments.destroy');
    
});
