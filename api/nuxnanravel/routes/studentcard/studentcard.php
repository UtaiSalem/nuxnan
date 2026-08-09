<?php

use App\Http\Controllers\Api\Learn\Student\Card\PublicStudentCardRequestController;
use App\Http\Controllers\Api\Learn\Student\Card\StudentCardController;
use App\Http\Controllers\Api\Learn\Student\Card\StudentCardManageController;
use Illuminate\Support\Facades\Route;

// Student Card System - Public Access (No Authentication Required)
// This system is open for teachers and students who are not website members
Route::prefix('student-card')->name('student-card.')->group(function () {

    // Main Student Card Routes (Public Access)
    Route::get('/', [StudentCardController::class, 'index'])->name('index');
    Route::get('/dashboard', [StudentCardController::class, 'dashboard'])->name('dashboard');
    Route::get('/search', [StudentCardController::class, 'search'])->middleware('auth:api')->name('search');
    Route::get('/{level}/{room}', [StudentCardController::class, 'getStudentByRoom'])->name('get-by-room');

    // Temporary classroom management (no auth — gated by PUBLIC_STUDENT_CARD_MANAGEMENT config)
    Route::prefix('{level}/{room}')->name('manage.')->group(function () {
        Route::middleware('throttle:60,1')->group(function () {
            Route::get('/manage-context', [StudentCardManageController::class, 'context'])->name('context');
            Route::get('/available-students', [StudentCardManageController::class, 'availableStudents'])->name('available-students');
        });

        Route::middleware('throttle:15,1')->group(function () {
            Route::post('/students', [StudentCardManageController::class, 'addStudent'])->name('add-student');
            Route::post('/students/{student}/transfer', [StudentCardManageController::class, 'transferStudent'])->name('transfer-student');
            Route::delete('/students/{student}', [StudentCardManageController::class, 'removeStudent'])->name('remove-student');
        });

        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/requests', [PublicStudentCardRequestController::class, 'submitRequest'])->name('submit-request');
            Route::post('/requests/{studentCardRequest}/cancel', [PublicStudentCardRequestController::class, 'cancelRequest'])->name('cancel-request');
            Route::post('/requests/{studentCardRequest}/{action}', [PublicStudentCardRequestController::class, 'reviewRequest'])->name('review-request');
        });

        Route::middleware('throttle:5,1')->group(function () {
            Route::post('/requests/bulk', [PublicStudentCardRequestController::class, 'submitBulkRequests'])->name('submit-bulk-requests');
        });
    });

    // Student profile and the main card update endpoint remain authenticated.
    Route::middleware('auth:api')->group(function () {
        Route::get('/profile/{student_card}', [StudentCardController::class, 'profile'])->name('profile');
        Route::put('/update/{student_card}', [StudentCardController::class, 'update'])->name('update');
        Route::delete('/{student_card}/photo', [StudentCardController::class, 'destroyPhoto'])->name('photo.destroy');
    });

    // Temporary public classroom-management endpoints, scoped to the current room.
    Route::put('/public-update/{level}/{room}/{student_card}', [StudentCardController::class, 'publicUpdate'])
        ->middleware('throttle:10,1')
        ->name('public-update');

    Route::post('/public-photo/{level}/{room}/{student_card}', [StudentCardController::class, 'publicUpdateImage'])
        ->middleware('throttle:20,1')
        ->name('public-photo.store');

    Route::delete('/public-photo/{level}/{room}/{student_card}', [StudentCardController::class, 'publicDestroyPhoto'])
        ->middleware('throttle:20,1')
        ->name('public-photo.destroy');

    // Admin Functions (Public - with admin password verification per action)
    Route::prefix('admin')->name('admin.')->group(function () {
        // Public read-only listing (intentionally unauthenticated — matches public page access)
        Route::middleware('throttle:60,1')->group(function () {
            Route::get('/students/{level}/{room}', [StudentCardController::class, 'adminGetStudentByRoom'])->name('students.by-room');
        });

        Route::middleware('auth:api')->group(function () {
            Route::get('/', [StudentCardController::class, 'adminIndex'])->name('index');
            Route::get('/students', [StudentCardController::class, 'adminStudents'])->name('students');

            // Admin actions (with inline password verification)
            Route::post('/upload-photo/{student_card}', [StudentCardController::class, 'updateImage'])->name('upload-photo');
            Route::patch('/update-code/{student_card}', [StudentCardController::class, 'updateStudentID'])->name('update-student-id');
            Route::patch('/update-name-th/{student_card}', [StudentCardController::class, 'updateStudentNameTh'])->name('update-student-name-th');
            Route::patch('/update-name-en/{student_card}', [StudentCardController::class, 'updateStudentNameEn'])->name('update-student-name-en');
        });
    });
});
