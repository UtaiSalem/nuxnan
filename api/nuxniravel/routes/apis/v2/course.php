<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Learn\Course\info\CourseController;
use App\Http\Controllers\Api\Learn\Course\groups\CourseGroupController;
use App\Http\Controllers\Api\Learn\Course\members\CourseMemberController;
use App\Http\Controllers\Api\Learn\Course\summary\CourseSummaryController;

/*
|--------------------------------------------------------------------------
| Course API V2 Routes
|--------------------------------------------------------------------------
|
| These routes are specifically designed for Course Page Version 2 API
| with /api/v2/ prefix to ensure clear separation from v1 routes
| and follow RESTful API conventions.
|
*/

Route::middleware(['auth:api', config('jetstream.auth_session'), 'verified'])->prefix('api/v2')->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | Course V2 API Routes
    |--------------------------------------------------------------------------
    */
    
    // Course endpoints
    Route::get('/courses', [CourseController::class, 'index'])->name('api.v2.courses.index');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('api.v2.courses.show');
    Route::put('/courses/{course}', [CourseController::class, 'updateV2'])->name('api.v2.courses.update');
    
    // Course groups endpoints
    Route::get('/courses/{course}/groups', [CourseGroupController::class, 'index'])->name('api.v2.courses.groups.index');
    Route::post('/courses/{course}/groups', [CourseGroupController::class, 'store'])->name('api.v2.courses.groups.store');
    Route::put('/groups/{groupId}', [CourseGroupController::class, 'updateV2'])->name('api.v2.groups.update');
    Route::get('/groups/{groupId}/members', [CourseGroupController::class, 'members'])->name('api.v2.groups.members');
    
    // Course members endpoints
    Route::get('/courses/{course}/members', [CourseMemberController::class, 'index'])->name('api.v2.courses.members.index');
    Route::put('/members/{memberId}', [CourseMemberController::class, 'update'])->name('api.v2.members.update');
    
    // Course summary endpoint
    Route::get('/courses/{course}/summary', [CourseSummaryController::class, 'show'])->name('api.v2.courses.summary');
});