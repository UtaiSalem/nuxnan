<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PhotoController;
use App\Http\Controllers\Api\AlbumController;

/*
|--------------------------------------------------------------------------
| Photos & Albums API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your photos and albums system.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

Route::middleware('auth:api')->group(function () {
    // Profile Photos Routes
    Route::prefix('profile')->group(function () {
        // Get current user's photos
        Route::get('/photos', [PhotoController::class, 'index']);
        
        // Upload photos
        Route::post('/photos', [PhotoController::class, 'store']);
        
        // Update photo
        Route::put('/photos/{id}', [PhotoController::class, 'update']);
        
        // Delete photo
        Route::delete('/photos/{id}', [PhotoController::class, 'destroy']);
        
        // Get current user's albums
        Route::get('/albums', [AlbumController::class, 'index']);
        
        // Create album
        Route::post('/albums', [AlbumController::class, 'store']);
        
        // Update album
        Route::put('/albums/{id}', [AlbumController::class, 'update']);
        
        // Delete album
        Route::delete('/albums/{id}', [AlbumController::class, 'destroy']);
    });

    // Photos Routes
    Route::prefix('photos')->group(function () {
        // Get photos by album
        Route::get('/album/{albumId}', [PhotoController::class, 'getByAlbum']);
        
        // Like photo
        Route::post('/{id}/like', [PhotoController::class, 'like']);
        
        // Unlike photo
        Route::post('/{id}/unlike', [PhotoController::class, 'unlike']);
        
        // Show single photo
        Route::get('/{id}', [PhotoController::class, 'show']);
    });

    // Albums Routes
    Route::prefix('albums')->group(function () {
        // Show single album
        Route::get('/{id}', [AlbumController::class, 'show']);
    });

    // User Photos Routes (View other users' photos)
    Route::prefix('users/{identifier}')->group(function () {
        // Get user's photos
        Route::get('/photos', [PhotoController::class, 'index']);
        
        // Get user's albums
        Route::get('/albums', [AlbumController::class, 'index']);
    });
});
