<?php

use App\Http\Controllers\Api\Learn\Student\HomeVisit\AdminController;
use App\Http\Controllers\Api\Learn\Student\HomeVisit\ZoneController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Academy Home Visit Routes
|--------------------------------------------------------------------------
|
| Home Visit System Routes under Academy Management
| Prefix: /api/academies/{academy}/home-visits
|
| Authz model (menu #17 HV-S1/S2):
|   - อ่าน  → academy.permission:home_visits.view
|   - เขียน → academy.permission:home_visits.manage
| ทุกเส้นต้องผ่านด่านสิทธิ์ของโรงเรียน (JWT) — ไม่มีเส้น auth:api เปล่าในไฟล์นี้อีก
|
| หมายเหตุ: กลุ่ม session เก่า (auth/*, student/*, teacher/*) ที่เคยอยู่ที่นี่ถูกถอดออก
| 2026-10-11 — ไม่มี FE ใช้ และชี้ไป TeacherController แบบ session ที่ด่านถูก comment ทิ้ง
| (ช่องโหว่ G1/G2) · โฟลว์นักเรียน/ผู้ปกครอง/ครู ใช้ master-profile JWT ที่ student-profile.php
*/

Route::middleware(['auth:api'])->prefix('/academies/{academy}')->group(function () {

    Route::prefix('home-visits')->name('api.academy.home-visits.')->group(function () {

        // ================================================
        // Dashboard and Overview (อ่าน — ต้องมี home_visits.view)
        // ================================================
        Route::middleware('academy.permission:home_visits.view')->group(function () {
            Route::get('/', [AdminController::class, 'dashboard'])->name('index');
            Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
            Route::get('/statistics', [AdminController::class, 'statistics'])->name('statistics');
        });

        // ================================================
        // Zone Management
        // ================================================
        Route::middleware('academy.permission:home_visits.view')->prefix('zones')->name('zones.')->group(function () {
            Route::get('/', [ZoneController::class, 'index'])->name('index');
            Route::get('/list', [ZoneController::class, 'list'])->name('list');
            Route::get('/{id}', [ZoneController::class, 'show'])->name('show');
            Route::middleware('academy.permission:home_visits.manage')->group(function () {
                Route::post('/', [ZoneController::class, 'store'])->name('store');
                Route::put('/{id}', [ZoneController::class, 'update'])->name('update');
                Route::delete('/{id}', [ZoneController::class, 'destroy'])->name('destroy');
                Route::put('/{id}/toggle-status', [ZoneController::class, 'toggleStatus'])->name('toggle-status');
                Route::post('/reorder', [ZoneController::class, 'reorder'])->name('reorder');
            });
        });

        // ================================================
        // Admin Routes
        // ================================================
        Route::middleware('academy.permission:home_visits.view')->prefix('admin')->name('admin.')->group(function () {
            Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
            Route::get('/dashboard/mock', [AdminController::class, 'dashboardMock'])->name('dashboard.mock');
            Route::get('/students', [AdminController::class, 'students'])->name('students');
            Route::get('/students/{id}', [AdminController::class, 'showStudent'])->name('students.show');
            Route::middleware('academy.permission:home_visits.manage')->put('/students/{id}', [AdminController::class, 'updateStudent'])->name('students.update');
            Route::get('/visits', [AdminController::class, 'visits'])->name('visits');
            Route::middleware('academy.permission:home_visits.manage')->post('/visits', [AdminController::class, 'storeVisit'])->name('visits.store');
            Route::get('/visits/{id}', [AdminController::class, 'showVisit'])->name('visits.show');
            Route::middleware('academy.permission:home_visits.manage')->group(function () {
                Route::put('/visits/{id}', [AdminController::class, 'updateVisit'])->name('visits.update');
                Route::delete('/visits/{id}', [AdminController::class, 'destroyVisit'])->name('visits.destroy');
                Route::put('/visits/{id}/status', [AdminController::class, 'updateVisitStatus'])->name('visits.update.status');
            });
            Route::get('/export/visits', [AdminController::class, 'exportVisits'])->name('export.visits');
            Route::get('/settings', [AdminController::class, 'settings'])->name('settings');

            // Statistics and Reports
            Route::get('/statistics', [AdminController::class, 'statistics'])->name('statistics');
            Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
            Route::get('/reports/export', [AdminController::class, 'exportReports'])->name('reports.export');
        });
    });
});
