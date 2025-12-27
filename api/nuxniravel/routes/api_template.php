<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Auth Routes
Route::post('/register', [App\Http\Controllers\Api\Auth\RegisteredUserController::class, 'store']);
Route::post('/login', [App\Http\Controllers\Api\Auth\AuthenticatedSessionController::class, 'store']);
Route::post('/logout', [App\Http\Controllers\Api\Auth\AuthenticatedSessionController::class, 'destroy'])->middleware('auth:sanctum');

// Public Routes
Route::get('/', [App\Http\Controllers\Api\Shared\WelcomeController::class, 'index']);

// Protected Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // ... Copy routes from web.php here and update namespaces ...
    // Example:
    // Route::get('/newsfeed', [App\Http\Controllers\Api\Play\NewsfeedController::class, 'index']);
});
