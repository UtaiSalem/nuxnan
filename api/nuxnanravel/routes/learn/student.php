<?php

use App\Http\Controllers\Api\Learn\Academy\ClassroomController;
use App\Http\Controllers\Api\Learn\Academy\TranscriptController;
use App\Http\Controllers\Api\Learn\StudentAnalyticsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Student Management Routes
|--------------------------------------------------------------------------
|
| Routes for managing student-related functionality including:
| - Student Cards (CRUD operations, photo management)
| - Student Analytics
|
| Note: Home Visit System routes have been moved to /routes/homevisit/homevisit.php
|
*/

Route::middleware(['auth:api'])->group(function () {
    Route::get('/student/analytics', [StudentAnalyticsController::class, 'getDashboardAnalytics'])->name('student.analytics');

    // Student Transcript Routes (moved from gradebook.php)
    Route::prefix('/students/{student}')->group(function () {
        Route::get('/transcripts/semester', [TranscriptController::class, 'getSemesterTranscript'])->name('api.student.transcripts.semester');
        Route::get('/transcripts/annual', [TranscriptController::class, 'getAnnualTranscript'])->name('api.student.transcripts.annual');
        Route::get('/transcripts/{transcript}/pdf', [TranscriptController::class, 'downloadTranscriptPdf'])->name('api.student.transcripts.pdf');
    });

    // Current User Student Routes (me) (moved from gradebook.php)
    Route::prefix('/students/me')->group(function () {
        Route::get('/transcripts', [TranscriptController::class, 'getMyTranscripts'])->name('api.student.me.transcripts');
        Route::get('/transcripts/{transcript}/pdf', [TranscriptController::class, 'downloadMyTranscriptPdf'])->name('api.student.me.transcripts.pdf');
        Route::get('/card', [ClassroomController::class, 'getMyStudentCard'])->name('api.student.me.card');
    });
});

// =====================================
// STUDENT ROUTES GROUP
// =====================================
// Note: Student Card routes have been moved to /routes/studentcard/studentcard.php with independent authentication

// Note: Home Visit routes have been moved to /routes/homevisit/homevisit.php

// =====================================
// ADMIN ROUTES GROUP
// =====================================
// =====================================
// ADMIN ROUTES GROUP
// =====================================
// Note: Admin Student Card routes have been moved to /routes/studentcard/studentcard.php with independent authentication
