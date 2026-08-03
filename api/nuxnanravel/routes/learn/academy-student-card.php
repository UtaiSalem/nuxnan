<?php

use App\Http\Controllers\Api\Learn\Student\Card\AcademyStudentCardManageController;
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
| ⚠️ {level} ต้องมีข้อจำกัดเป็นตัวเลขเสมอ ไม่งั้น /{level}/{room} จะกลืน
| /admin/students และ /admin/audit (พิสูจน์แล้วด้วย router match — สองเส้นนั้น
| เคยวิ่งเข้า getStudentByRoom(level=admin, room=students) แล้วคืนรายการว่าง)
|
| สิทธิ์แบ่งเป็นสามชั้น:
|  1. อ่าน                → students.view
|  2. แก้บัตร/รายชื่อในห้อง → students.view ที่ route + StudentCardAccessService
|                           ในเมธอด (ครูประจำชั้นแก้ได้เฉพาะห้องตัวเอง)
|  3. งานระดับโรงเรียน     → students.manage (สร้างบัตร นำเข้า ส่งออก sync ตรวจสอบ)
|
| ⚠️ ชั้นที่ 2 ปลอดภัยได้ด้วยการเรียก access service ในเมธอด "ทุกตัว" เท่านั้น
| ถ้าเพิ่ม route ใหม่ในกลุ่มนี้แล้วลืมเรียก = คนที่ดูได้จะแก้ได้ทันที
| มีเทสต์ AcademyStudentCardAccessTest คุมไว้ทุก route
*/

Route::middleware(['auth:api'])->prefix('/academies/{academy}')->group(function () {

    Route::prefix('student-cards')->name('api.academy.student-cards.')->group(function () {

        // ================================================
        // 1) Read-only — students.view
        // ================================================
        Route::middleware('academy.permission:students.view')->group(function () {
            Route::get('/', [StudentCardController::class, 'index'])->name('index');
            Route::get('/dashboard', [StudentCardController::class, 'dashboard'])->name('dashboard');
            Route::get('/statistics', [StudentCardController::class, 'statistics'])->name('statistics');

            Route::get('/search', [StudentCardController::class, 'search'])->name('search');
            Route::get('/levels', [StudentCardController::class, 'getLevels'])->name('levels');
            Route::get('/sections', [StudentCardController::class, 'getSections'])->name('sections');

            Route::get('/by-student/{student}', [StudentCardController::class, 'byStudent'])->name('by-student');

            Route::get('/profile/{student_card}', [StudentCardController::class, 'profile'])->name('profile');

            Route::get('/{level}/{room}', [StudentCardController::class, 'getStudentByRoom'])
                ->where(['level' => '[0-9]+', 'room' => '[^/]+'])
                ->name('by-room');
        });

        // ================================================
        // 2) แก้ไขบัตรและรายชื่อในห้อง
        //    ผ่าน route ด้วย students.view แล้วตรวจสิทธิ์จริงรายใบ/รายห้อง
        //    ในเมธอด เพราะ middleware มองไม่เห็นว่า card ใบนี้อยู่ห้องไหน
        // ================================================
        Route::middleware('academy.permission:students.view')->group(function () {
            Route::put('/{student_card}', [StudentCardController::class, 'update'])
                ->where('student_card', '[0-9]+')
                ->name('update');
            Route::delete('/{student_card}/photo', [StudentCardController::class, 'destroyPhoto'])
                ->where('student_card', '[0-9]+')
                ->name('photo.destroy');

            Route::post('/admin/upload-photo/{student_card}', [StudentCardController::class, 'updateImage'])
                ->name('admin.upload-photo');
            Route::patch('/admin/update-code/{student_card}', [StudentCardController::class, 'updateStudentID'])
                ->name('admin.update-student-id');
            Route::patch('/admin/update-name-th/{student_card}', [StudentCardController::class, 'updateStudentNameTh'])
                ->name('admin.update-student-name-th');
            Route::patch('/admin/update-name-en/{student_card}', [StudentCardController::class, 'updateStudentNameEn'])
                ->name('admin.update-student-name-en');

            // จัดการรายชื่อในห้อง — เทียบเท่าหน้าชั่วคราว แต่ตรวจสิทธิ์จริง
            Route::prefix('/{level}/{room}')
                ->where(['level' => '[0-9]+', 'room' => '[^/]+'])
                ->name('room.')
                ->group(function () {
                    Route::get('/context', [AcademyStudentCardManageController::class, 'context'])
                        ->name('context');
                    Route::get('/available-students', [AcademyStudentCardManageController::class, 'availableStudents'])
                        ->name('available-students');
                    Route::post('/students', [AcademyStudentCardManageController::class, 'addStudent'])
                        ->name('add-student');
                    Route::post('/students/{student}/transfer', [AcademyStudentCardManageController::class, 'transferStudent'])
                        ->name('transfer-student');
                    Route::delete('/students/{student}', [AcademyStudentCardManageController::class, 'removeStudent'])
                        ->name('remove-student');
                });
        });

        // ================================================
        // 3) งานระดับโรงเรียน — students.manage
        // ================================================
        Route::middleware('academy.permission:students.manage')->group(function () {
            Route::prefix('admin')->name('admin.')->group(function () {
                Route::get('/', [StudentCardController::class, 'adminIndex'])->name('index');
                Route::get('/students', [StudentCardController::class, 'adminStudents'])->name('students');
                Route::get('/students/{level}/{room}', [StudentCardController::class, 'adminGetStudentByRoom'])->name('students.by-room');

                // Audit and Sync operations
                Route::get('/audit', [StudentCardController::class, 'audit'])->name('audit');
                Route::get('/sync/preview', [StudentCardController::class, 'syncPreview'])->name('sync.preview');
                Route::post('/sync/commit', [StudentCardController::class, 'syncCommit'])->name('sync.commit');

                Route::post('/', [StudentCardController::class, 'store'])->name('store');
                Route::post('/import', [StudentCardController::class, 'import'])->name('import');
                Route::get('/export', [StudentCardController::class, 'export'])->name('export');

                Route::post('/bulk-upload-photos', [StudentCardController::class, 'bulkUploadPhotos'])->name('bulk-upload-photos');
                Route::post('/bulk-update', [StudentCardController::class, 'bulkUpdate'])->name('bulk-update');
            });
        });
    });
});
