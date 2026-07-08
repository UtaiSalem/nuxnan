<?php

use App\Http\Controllers\Api\Learn\Student\HomeVisit\HomeVisitAuthController;
use App\Http\Controllers\Api\Learn\Student\HomeVisit\TeacherController;
use App\Http\Controllers\Api\Learn\Student\HomeVisit\ZoneController;
use App\Http\Controllers\Api\Learn\Student\Master\AcademicInfoController;
use App\Http\Controllers\Api\Learn\Student\Master\AddressController;
use App\Http\Controllers\Api\Learn\Student\Master\ContactController;
use App\Http\Controllers\Api\Learn\Student\Master\GuardianController;
use App\Http\Controllers\Api\Learn\Student\Master\HealthController;
use App\Http\Controllers\Api\Learn\Student\Master\StudentController;
use Illuminate\Support\Facades\Route;

// Main Home Visit System Routes
Route::prefix('home-visit')->name('homevisit.')->group(function () {

    // Authentication Routes
    Route::get('/', [HomeVisitAuthController::class, 'index'])->name('login');
    Route::post('/student-login', [HomeVisitAuthController::class, 'studentLogin'])->name('student.login');
    Route::post('/teacher-login', [HomeVisitAuthController::class, 'teacherLogin'])->name('teacher.login');
    Route::post('/admin-login', [HomeVisitAuthController::class, 'adminLogin'])->name('admin.login');
    Route::post('/logout', [HomeVisitAuthController::class, 'logout'])->name('general.logout');
    Route::get('/check-auth', [HomeVisitAuthController::class, 'checkAuth'])->name('check.auth');

    // Student Routes (protected by session authentication)
    Route::prefix('student')->name('student.')->group(function () {
        Route::post('/home-visit', [StudentController::class, 'storeHomeVisit'])->name('home-visit.store');
        Route::get('/profile', [StudentController::class, 'profile'])->name('profile');
        Route::post('/update-info', [StudentController::class, 'updateInfo'])->name('update.info');
        Route::post('/upload-photos', [StudentController::class, 'uploadPhotos'])->name('upload.photos');

        // Student Academic Info Routes
        Route::prefix('{student}/academic-info')->name('academic-info.')->group(function () {
            Route::get('/', [AcademicInfoController::class, 'index'])->name('index');  // ดูทั้งหมด
            Route::post('/', [AcademicInfoController::class, 'store'])->name('store');
            Route::get('/{academicInfo}', [AcademicInfoController::class, 'show'])->name('show');
            Route::put('/{academicInfo}', [AcademicInfoController::class, 'update'])->name('update');
            Route::delete('/{academicInfo}', [AcademicInfoController::class, 'destroy'])->name('destroy');
            Route::put('/{academicInfo}/set-current', [AcademicInfoController::class, 'setCurrent'])->name('set-current');
        });

        // Student Address Routes
        Route::prefix('{student}/addresses')->name('addresses.')->group(function () {
            Route::get('/', [AddressController::class, 'index'])->name('index');
            Route::post('/', [AddressController::class, 'store'])->name('store');
            Route::put('/{address}', [AddressController::class, 'update'])->name('update');
            Route::delete('/{address}', [AddressController::class, 'destroy'])->name('destroy');
            Route::put('/{address}/set-current', [AddressController::class, 'setCurrent'])->name('set-current');
        });

        // Student Contact Routes
        Route::prefix('{student}/contacts')->name('contacts.')->group(function () {
            Route::get('/', [ContactController::class, 'index'])->name('index');
            Route::post('/', [ContactController::class, 'store'])->name('store');
            Route::put('/{contact}', [ContactController::class, 'update'])->name('update');
            Route::delete('/{contact}', [ContactController::class, 'destroy'])->name('destroy');
            Route::put('/{contact}/set-primary', [ContactController::class, 'setPrimary'])->name('set-primary');
        });

        // Student Health Routes
        Route::prefix('{student}/health')->name('health.')->group(function () {
            Route::get('/', [HealthController::class, 'show'])->name('show');
            Route::post('/', [HealthController::class, 'store'])->name('store');
            Route::put('/{health}', [HealthController::class, 'update'])->name('update');
        });

        // Student Guardian Routes
        Route::prefix('{student}/guardian')->name('guardian.')->group(function () {
            Route::get('/', [GuardianController::class, 'show'])->name('show');
            Route::post('/', [GuardianController::class, 'store'])->name('store');
            Route::put('/', [GuardianController::class, 'update'])->name('update');
        });

        // Search and Statistics Routes
        Route::get('/academic-info/search', [AcademicInfoController::class, 'searchByAcademicInfo'])->name('academic-info.search');
        Route::get('/academic-info/statistics', [AcademicInfoController::class, 'statistics'])->name('academic-info.statistics');
        Route::put('/academic-info/bulk-update', [AcademicInfoController::class, 'bulkUpdate'])->name('academic-info.bulk-update');
    });

    // Teacher Routes (protected by session authentication)
    Route::prefix('teacher')->name('teacher.')->group(function () {
        Route::get('/dashboard', [TeacherController::class, 'dashboard'])->name('dashboard');
        Route::get('/search-students', [TeacherController::class, 'searchStudents'])->name('search.students');
        Route::get('/manage-student/{student}', [TeacherController::class, 'manageStudent'])->name('manage.student');
        Route::post('/create-home-visit/{student}', [TeacherController::class, 'createHomeVisitWithImages'])->name('create.home.visit');
        Route::put('/update-student/{student}', [TeacherController::class, 'updateStudent'])->name('update.student');
        Route::put('/update-home-visit/{homeVisit}', [TeacherController::class, 'updateHomeVisit'])->name('update.home.visit');
        Route::post('/update-home-visit-with-images/{homeVisit}', [TeacherController::class, 'updateHomeVisitWithImages'])->name('update.home.visit.with.images');
        Route::delete('/delete-home-visit/{homeVisit}', [TeacherController::class, 'deleteHomeVisit'])->name('delete.home.visit');

        // Student Academic Info Routes for Teacher
        Route::prefix('{student}/academic-info')->name('academic-info.')->group(function () {
            Route::get('/', [AcademicInfoController::class, 'index'])->name('index');  // ดูทั้งหมด
            Route::post('/', [AcademicInfoController::class, 'store'])->name('store');
            Route::get('/{academicInfo}', [AcademicInfoController::class, 'show'])->name('show');
            Route::put('/{academicInfo}', [AcademicInfoController::class, 'update'])->name('update');
            Route::delete('/{academicInfo}', [AcademicInfoController::class, 'destroy'])->name('destroy');
            Route::put('/{academicInfo}/set-current', [AcademicInfoController::class, 'setCurrent'])->name('set-current');
        });

        // Student Address Routes for Teacher
        Route::prefix('{student}/addresses')->name('addresses.')->group(function () {
            Route::get('/', [AddressController::class, 'index'])->name('index');
            Route::post('/', [AddressController::class, 'store'])->name('store');
            Route::put('/{address}', [AddressController::class, 'update'])->name('update');
            Route::delete('/{address}', [AddressController::class, 'destroy'])->name('destroy');
            Route::put('/{address}/set-current', [AddressController::class, 'setCurrent'])->name('set-current');
        });

        // Student Contact Routes for Teacher
        Route::prefix('{student}/contacts')->name('contacts.')->group(function () {
            Route::get('/', [ContactController::class, 'index'])->name('index');
            Route::post('/', [ContactController::class, 'store'])->name('store');
            Route::put('/{contact}', [ContactController::class, 'update'])->name('update');
            Route::delete('/{contact}', [ContactController::class, 'destroy'])->name('destroy');
            Route::put('/{contact}/set-primary', [ContactController::class, 'setPrimary'])->name('set-primary');
        });

        // Student Health Routes for Teacher
        Route::prefix('{student}/health')->name('health.')->group(function () {
            Route::get('/', [HealthController::class, 'show'])->name('show');
            Route::post('/', [HealthController::class, 'store'])->name('store');
            Route::put('/{health}', [HealthController::class, 'update'])->name('update');
        });

        // Student Guardian Routes for Teacher
        Route::prefix('{student}/guardian')->name('guardian.')->group(function () {
            Route::get('/', [GuardianController::class, 'show'])->name('show');
            Route::post('/', [GuardianController::class, 'store'])->name('store');
            Route::put('/', [GuardianController::class, 'update'])->name('update');
        });

        // Teacher Access to Search and Statistics
        Route::get('/academic-info/search', [AcademicInfoController::class, 'searchByAcademicInfo'])->name('academic-info.search');
        Route::get('/academic-info/statistics', [AcademicInfoController::class, 'statistics'])->name('academic-info.statistics');
        Route::put('/academic-info/bulk-update', [AcademicInfoController::class, 'bulkUpdate'])->name('academic-info.bulk-update');
    });

    // The legacy admin API was removed. Academy administration is available at
    // /api/academies/{academy}/home-visits and is protected by JWT auth.

    // Public/Shared Routes for Zones (accessible by teachers)
    Route::get('/zones', [ZoneController::class, 'index'])->name('zones.index');
});
