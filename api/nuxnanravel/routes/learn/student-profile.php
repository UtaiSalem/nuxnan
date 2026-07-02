<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Learn\Student\Profile\StudentProfileController;
use App\Http\Controllers\Api\Learn\Student\Master\StudentController;
use App\Http\Controllers\Api\Learn\Student\Master\AddressController;
use App\Http\Controllers\Api\Learn\Student\Master\ContactController;
use App\Http\Controllers\Api\Learn\Student\Master\GuardianController;
use App\Http\Controllers\Api\Learn\Student\Master\HealthController;
use App\Http\Controllers\Api\Learn\Student\Master\AcademicInfoController;
use App\Http\Controllers\Api\Learn\Student\Master\ChangeRequestController;

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

    // ============================================
    // Current User's Own Profile (Students/Me)
    // MUST come before {student} wildcard routes
    // ============================================
    Route::prefix('students/me')->name('api.academy.student-profile.me.')->group(function () {
        Route::get('/profile', [StudentProfileController::class, 'myProfile'])->name('show');
        Route::get('/summary', [StudentProfileController::class, 'mySummary'])->name('summary');
    });

    // ============================================
    // Student Profile by ID
    // ============================================
    Route::prefix('students/{student}')->name('api.academy.student-profile.')->group(function () {
        
        // Full student profile
        Route::get('/profile', [StudentProfileController::class, 'show'])->name('show');
        
        // Lightweight profile summary
        Route::get('/summary', [StudentProfileController::class, 'summary'])->name('summary');

        // Sectional edit endpoints (Phase 7)
        Route::patch('/personal', [StudentController::class, 'updatePersonal'])->name('personal.update');

        // Address routes
        Route::get('/addresses', [AddressController::class, 'index'])->name('addresses.index');
        Route::post('/addresses', [AddressController::class, 'store'])->name('addresses.store');
        Route::put('/addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
        Route::delete('/addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');
        Route::patch('/addresses/{address}/set-current', [AddressController::class, 'setCurrent'])->name('addresses.set-current');

        // Contact routes
        Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');
        Route::put('/contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
        Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
        Route::patch('/contacts/{contact}/set-primary', [ContactController::class, 'setPrimary'])->name('contacts.set-primary');

        // Guardian routes
        Route::get('/guardians', [GuardianController::class, 'show'])->name('guardians.show');
        Route::post('/guardians', [GuardianController::class, 'store'])->name('guardians.store');
        Route::put('/guardians', [GuardianController::class, 'update'])->name('guardians.update');

        // Health routes
        Route::get('/health', [HealthController::class, 'show'])->name('health.show');
        Route::post('/health', [HealthController::class, 'store'])->name('health.store');
        Route::put('/health/{health}', [HealthController::class, 'update'])->name('health.update');

        // Academic Info routes
        Route::get('/academic-info', [AcademicInfoController::class, 'index'])->name('academic-info.index');
        Route::post('/academic-info', [AcademicInfoController::class, 'store'])->name('academic-info.store');
        Route::put('/academic-info/{academicInfo?}', [AcademicInfoController::class, 'update'])->name('academic-info.update');
        Route::delete('/academic-info/{academicInfo}', [AcademicInfoController::class, 'destroy'])->name('academic-info.destroy');
        Route::patch('/academic-info/{academicInfo}/set-current', [AcademicInfoController::class, 'setCurrent'])->name('academic-info.set-current');

        // Change request routes
        Route::get('/change-requests', [ChangeRequestController::class, 'index'])->name('change-requests.index');
        Route::patch('/change-requests/{changeRequest}/approve', [ChangeRequestController::class, 'approve'])->name('change-requests.approve');
        Route::patch('/change-requests/{changeRequest}/reject', [ChangeRequestController::class, 'reject'])->name('change-requests.reject');
    });
});
