<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Learn\Student\HomeVisit\HomeVisitAuthController;
use App\Http\Controllers\Api\Learn\Student\HomeVisit\StudentController;
use App\Http\Controllers\Api\Learn\Student\HomeVisit\TeacherController;
use App\Http\Controllers\Api\Learn\Student\HomeVisit\AdminController;
use App\Http\Controllers\Api\Learn\Student\HomeVisit\StudentAcademicInfoController;
use App\Http\Controllers\Api\Learn\Student\HomeVisit\StudentAddressController;
use App\Http\Controllers\Api\Learn\Student\HomeVisit\StudentContactController;
use App\Http\Controllers\Api\Learn\Student\HomeVisit\StudentHealthController;
use App\Http\Controllers\Api\Learn\Student\HomeVisit\StudentGuardianController;
use App\Http\Controllers\Api\Learn\Student\HomeVisit\ZoneController;

/*
|--------------------------------------------------------------------------
| Academy Home Visit Routes
|--------------------------------------------------------------------------
|
| Home Visit System Routes under Academy Management
| Prefix: /api/academies/{academy}/home-visits
|
| Note: Original routes at /api/home-visit are still available for
| backward compatibility but deprecated.
|
*/

Route::middleware(['auth:api'])->prefix('/academies/{academy}')->group(function () {
    
    // ================================================
    // Home Visit Routes (under Academy)
    // ================================================
    Route::prefix('home-visits')->name('api.academy.home-visits.')->group(function () {
        
        // Dashboard and Overview
        Route::get('/', [AdminController::class, 'dashboard'])->name('index');
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/statistics', [AdminController::class, 'statistics'])->name('statistics');
        
        // ================================================
        // Authentication Routes (Session-based for the subsystem)
        // ================================================
        Route::prefix('auth')->name('auth.')->group(function () {
            Route::post('/student-login', [HomeVisitAuthController::class, 'studentLogin'])->name('student.login');
            Route::post('/teacher-login', [HomeVisitAuthController::class, 'teacherLogin'])->name('teacher.login');
            Route::post('/admin-login', [HomeVisitAuthController::class, 'adminLogin'])->name('admin.login');
            Route::post('/logout', [HomeVisitAuthController::class, 'logout'])->name('logout');
            Route::get('/check-auth', [HomeVisitAuthController::class, 'checkAuth'])->name('check');
        });

        // ================================================
        // Zone Management
        // ================================================
        Route::prefix('zones')->name('zones.')->group(function () {
            Route::get('/', [ZoneController::class, 'index'])->name('index');
            Route::get('/list', [ZoneController::class, 'list'])->name('list');
            Route::post('/', [ZoneController::class, 'store'])->name('store');
            Route::get('/{id}', [ZoneController::class, 'show'])->name('show');
            Route::put('/{id}', [ZoneController::class, 'update'])->name('update');
            Route::delete('/{id}', [ZoneController::class, 'destroy'])->name('destroy');
            Route::put('/{id}/toggle-status', [ZoneController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/reorder', [ZoneController::class, 'reorder'])->name('reorder');
        });

        // ================================================
        // Student Routes (self-service portal)
        // ================================================
        Route::prefix('student')->name('student.')->group(function () {
            Route::get('/profile', [StudentController::class, 'profile'])->name('profile');
            Route::post('/home-visit', [StudentController::class, 'storeHomeVisit'])->name('home-visit.store');
            Route::post('/update-info', [StudentController::class, 'updateInfo'])->name('update.info');
            Route::post('/upload-photos', [StudentController::class, 'uploadPhotos'])->name('upload.photos');
            
            // Student Academic Info Routes
            Route::prefix('{student}/academic-info')->name('academic-info.')->group(function () {
                Route::get('/', [StudentAcademicInfoController::class, 'index'])->name('index');
                Route::post('/', [StudentAcademicInfoController::class, 'store'])->name('store');
                Route::get('/{academicInfo}', [StudentAcademicInfoController::class, 'show'])->name('show');
                Route::put('/{academicInfo}', [StudentAcademicInfoController::class, 'update'])->name('update');
                Route::delete('/{academicInfo}', [StudentAcademicInfoController::class, 'destroy'])->name('destroy');
                Route::put('/{academicInfo}/set-current', [StudentAcademicInfoController::class, 'setCurrent'])->name('set-current');
            });

            // Student Address Routes
            Route::prefix('{student}/addresses')->name('addresses.')->group(function () {
                Route::get('/', [StudentAddressController::class, 'index'])->name('index');
                Route::post('/', [StudentAddressController::class, 'store'])->name('store');
                Route::put('/{address}', [StudentAddressController::class, 'update'])->name('update');
                Route::delete('/{address}', [StudentAddressController::class, 'destroy'])->name('destroy');
                Route::put('/{address}/set-current', [StudentAddressController::class, 'setCurrent'])->name('set-current');
            });

            // Student Contact Routes
            Route::prefix('{student}/contacts')->name('contacts.')->group(function () {
                Route::get('/', [StudentContactController::class, 'index'])->name('index');
                Route::post('/', [StudentContactController::class, 'store'])->name('store');
                Route::put('/{contact}', [StudentContactController::class, 'update'])->name('update');
                Route::delete('/{contact}', [StudentContactController::class, 'destroy'])->name('destroy');
                Route::put('/{contact}/set-primary', [StudentContactController::class, 'setPrimary'])->name('set-primary');
            });

            // Student Health Routes
            Route::prefix('{student}/health')->name('health.')->group(function () {
                Route::get('/', [StudentHealthController::class, 'show'])->name('show');
                Route::post('/', [StudentHealthController::class, 'store'])->name('store');
                Route::put('/{health}', [StudentHealthController::class, 'update'])->name('update');
            });

            // Student Guardian Routes
            Route::prefix('{student}/guardian')->name('guardian.')->group(function () {
                Route::get('/', [StudentGuardianController::class, 'show'])->name('show');
                Route::post('/', [StudentGuardianController::class, 'store'])->name('store');
                Route::put('/', [StudentGuardianController::class, 'update'])->name('update');
            });
        });

        // ================================================
        // Teacher Routes
        // ================================================
        Route::prefix('teacher')->name('teacher.')->group(function () {
            Route::get('/dashboard', [TeacherController::class, 'dashboard'])->name('dashboard');
            Route::get('/search-students', [TeacherController::class, 'searchStudents'])->name('search.students');
            Route::get('/manage-student/{student}', [TeacherController::class, 'manageStudent'])->name('manage.student');
            Route::post('/create-home-visit/{student}', [TeacherController::class, 'createHomeVisitWithImages'])->name('create.home.visit');
            Route::put('/update-student/{student}', [TeacherController::class, 'updateStudent'])->name('update.student');
            Route::put('/update-home-visit/{homeVisit}', [TeacherController::class, 'updateHomeVisit'])->name('update.home.visit');
            Route::post('/update-home-visit-with-images/{homeVisit}', [TeacherController::class, 'updateHomeVisitWithImages'])->name('update.home.visit.with.images');
            Route::delete('/delete-home-visit/{homeVisit}', [TeacherController::class, 'deleteHomeVisit'])->name('delete.home.visit');
            
            // Teacher Access to Student Sub-routes
            Route::prefix('{student}/academic-info')->name('academic-info.')->group(function () {
                Route::get('/', [StudentAcademicInfoController::class, 'index'])->name('index');
                Route::post('/', [StudentAcademicInfoController::class, 'store'])->name('store');
                Route::put('/{academicInfo}', [StudentAcademicInfoController::class, 'update'])->name('update');
                Route::delete('/{academicInfo}', [StudentAcademicInfoController::class, 'destroy'])->name('destroy');
            });
            
            Route::prefix('{student}/addresses')->name('addresses.')->group(function () {
                Route::get('/', [StudentAddressController::class, 'index'])->name('index');
                Route::post('/', [StudentAddressController::class, 'store'])->name('store');
                Route::put('/{address}', [StudentAddressController::class, 'update'])->name('update');
                Route::delete('/{address}', [StudentAddressController::class, 'destroy'])->name('destroy');
            });
            
            Route::prefix('{student}/contacts')->name('contacts.')->group(function () {
                Route::get('/', [StudentContactController::class, 'index'])->name('index');
                Route::post('/', [StudentContactController::class, 'store'])->name('store');
                Route::put('/{contact}', [StudentContactController::class, 'update'])->name('update');
                Route::delete('/{contact}', [StudentContactController::class, 'destroy'])->name('destroy');
            });
            
            Route::prefix('{student}/health')->name('health.')->group(function () {
                Route::get('/', [StudentHealthController::class, 'show'])->name('show');
                Route::post('/', [StudentHealthController::class, 'store'])->name('store');
                Route::put('/{health}', [StudentHealthController::class, 'update'])->name('update');
            });
            
            Route::prefix('{student}/guardian')->name('guardian.')->group(function () {
                Route::get('/', [StudentGuardianController::class, 'show'])->name('show');
                Route::post('/', [StudentGuardianController::class, 'store'])->name('store');
                Route::put('/', [StudentGuardianController::class, 'update'])->name('update');
            });
        });

        // ================================================
        // Admin Routes
        // ================================================
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
            Route::get('/dashboard/mock', [AdminController::class, 'dashboardMock'])->name('dashboard.mock');
            Route::get('/students', [AdminController::class, 'students'])->name('students');
            Route::get('/students/{id}', [AdminController::class, 'showStudent'])->name('students.show');
            Route::put('/students/{id}', [AdminController::class, 'updateStudent'])->name('students.update');
            Route::get('/visits', [AdminController::class, 'visits'])->name('visits');
            Route::get('/visits/{id}', [AdminController::class, 'showVisit'])->name('visits.show');
            Route::put('/visits/{id}/status', [AdminController::class, 'updateVisitStatus'])->name('visits.update.status');
            Route::get('/export/visits', [AdminController::class, 'exportVisits'])->name('export.visits');
            Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
            
            // Statistics and Reports
            Route::get('/statistics', [AdminController::class, 'statistics'])->name('statistics');
            Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
            Route::get('/reports/export', [AdminController::class, 'exportReports'])->name('reports.export');
        });
    });
});
