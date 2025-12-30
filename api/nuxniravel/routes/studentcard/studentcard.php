<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Learn\Student\Card\StudentCardController;

// Student Card System - Public Access (No Authentication Required)
// This system is open for teachers and students who are not website members
Route::prefix('student-card')->name('student-card.')->group(function () {
    
    // Main Student Card Routes (Public Access)
    Route::get('/', [StudentCardController::class, 'index'])->name('index');
    Route::get('/dashboard', [StudentCardController::class, 'dashboard'])->name('dashboard');
    Route::get('/search', [StudentCardController::class, 'search'])->name('search');
    Route::get('/{level}/{room}', [StudentCardController::class, 'getStudentByRoom'])->name('get-by-room');
    
    // Student Profile & Updates (Public - but with validation)
    Route::get('/profile/{student_card}', [StudentCardController::class, 'profile'])->name('profile');
    Route::put('/update/{student_card}', [StudentCardController::class, 'update'])->name('update');
    Route::delete('/{student_card}/photo', [StudentCardController::class, 'destroyPhoto'])->name('photo.destroy');
    
    // Admin Functions (Public - with admin password verification per action)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [StudentCardController::class, 'adminIndex'])->name('index');
        Route::get('/students', [StudentCardController::class, 'adminStudents'])->name('students');
        Route::get('/students/{level}/{room}', [StudentCardController::class, 'adminGetStudentByRoom'])->name('students.by-room');
        
        // Admin actions (with inline password verification)
        Route::post('/upload-photo/{student_card}', [StudentCardController::class, 'updateImage'])->name('upload-photo');
        Route::patch('/update-code/{student_card}', [StudentCardController::class, 'updateStudentID'])->name('update-student-id');
        Route::patch('/update-name-th/{student_card}', [StudentCardController::class, 'updateStudentNameTh'])->name('update-student-name-th');
        Route::patch('/update-name-en/{student_card}', [StudentCardController::class, 'updateStudentNameEn'])->name('update-student-name-en');
    });
});
