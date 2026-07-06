<?php

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
