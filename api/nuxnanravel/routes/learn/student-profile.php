<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Learn\Student\Profile\StudentProfileController;

/*
|--------------------------------------------------------------------------
| Student Profile Routes
|--------------------------------------------------------------------------
|
| Routes for viewing student profiles within an academy context.
| Accessible by: student, parents, teachers, and admins.
|
| Prefix: /api/academies/{academy}/students/{student}/profile
|
*/

Route::middleware(['auth:api'])->prefix('/academies/{academy}')->group(function () {

    Route::prefix('students/{student}')->name('api.academy.student-profile.')->group(function () {
        
        // Full student profile
        Route::get('/profile', [StudentProfileController::class, 'show'])->name('show');
        
        // Lightweight profile summary
        Route::get('/summary', [StudentProfileController::class, 'summary'])->name('summary');
    });
});
