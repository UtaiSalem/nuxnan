<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\ProviderAuthController;
use App\Http\Controllers\Api\Learn\Course\groups\CourseGroupController;


Route::get('/', function () {
    return view('welcome');
});

// Named login route for API - returns JSON instead of redirect
Route::get('/login', function () {
    return response()->json([
        'success' => false,
        'message' => 'Unauthenticated. Please login first.',
    ], 401);
})->name('login');

// OAuth Routes (require session middleware)
Route::get('/auth/{provider}/redirect', [ProviderAuthController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('/auth/{provider}/callback', [ProviderAuthController::class, 'handleGoogleCallback'])->name('google.callback');


// Route::get('test/courses/{course}/groups', [CourseGroupController::class, 'index'])->name('test.courses.groups');
