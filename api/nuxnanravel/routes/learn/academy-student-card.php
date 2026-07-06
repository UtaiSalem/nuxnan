<?php

use App\Http\Controllers\Api\Learn\Student\Card\StudentCardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Academy Student Card Routes
|--------------------------------------------------------------------------
|
| Student Card System Routes under Academy Management
| Prefix: /api/academies/{academy}/student-cards
|
| Note: Original routes at /api/student-card are still available for
| backward compatibility but deprecated.
|
*/

Route::middleware(['auth:api'])->prefix('/academies/{academy}')->group(function () {

    // ================================================
    // Student Card Routes (under Academy)
    // ================================================
    Route::prefix('student-cards')->name('api.academy.student-cards.')->group(function () {

        // Dashboard and Overview
        Route::get('/', [StudentCardController::class, 'index'])->name('index');
        Route::get('/dashboard', [StudentCardController::class, 'dashboard'])->name('dashboard');
        Route::get('/statistics', [StudentCardController::class, 'statistics'])->name('statistics');

        // Search and Filter
        Route::get('/search', [StudentCardController::class, 'search'])->name('search');
        Route::get('/levels', [StudentCardController::class, 'getLevels'])->name('levels');
        Route::get('/sections', [StudentCardController::class, 'getSections'])->name('sections');

        // Get card by student model binding
        Route::get('/by-student/{student}', [StudentCardController::class, 'byStudent'])->name('by-student');

        // Get students by classroom
        // Individual student profile
        Route::get('/profile/{student_card}', [StudentCardController::class, 'profile'])->name('profile');
        Route::middleware('academy.permission:students.manage')->group(function () {
            Route::put('/{student_card}', [StudentCardController::class, 'update'])->name('update');
            Route::delete('/{student_card}/photo', [StudentCardController::class, 'destroyPhoto'])->name('photo.destroy');
        });

        Route::get('/{level}/{room}', [StudentCardController::class, 'getStudentByRoom'])->name('by-room');

        // ================================================
        // Admin Functions (requires academy admin role)
        // ================================================
        Route::middleware('academy.permission:students.manage')->prefix('admin')->name('admin.')->group(function () {
            Route::get('/', [StudentCardController::class, 'adminIndex'])->name('index');
            Route::get('/students', [StudentCardController::class, 'adminStudents'])->name('students');
            Route::get('/students/{level}/{room}', [StudentCardController::class, 'adminGetStudentByRoom'])->name('students.by-room');

            // Audit and Sync operations
            Route::get('/audit', [StudentCardController::class, 'audit'])->name('audit');
            Route::get('/sync/preview', [StudentCardController::class, 'syncPreview'])->name('sync.preview');
            Route::post('/sync/commit', [StudentCardController::class, 'syncCommit'])->name('sync.commit');

            // Admin CRUD operations
            Route::post('/', [StudentCardController::class, 'store'])->name('store');
            Route::post('/import', [StudentCardController::class, 'import'])->name('import');
            Route::get('/export', [StudentCardController::class, 'export'])->name('export');

            // Photo management
            Route::post('/upload-photo/{student_card}', [StudentCardController::class, 'updateImage'])->name('upload-photo');

            // Student info updates
            Route::patch('/update-code/{student_card}', [StudentCardController::class, 'updateStudentID'])->name('update-student-id');
            Route::patch('/update-name-th/{student_card}', [StudentCardController::class, 'updateStudentNameTh'])->name('update-student-name-th');
            Route::patch('/update-name-en/{student_card}', [StudentCardController::class, 'updateStudentNameEn'])->name('update-student-name-en');

            // Bulk operations
            Route::post('/bulk-upload-photos', [StudentCardController::class, 'bulkUploadPhotos'])->name('bulk-upload-photos');
            Route::post('/bulk-update', [StudentCardController::class, 'bulkUpdate'])->name('bulk-update');
        });
    });
});
