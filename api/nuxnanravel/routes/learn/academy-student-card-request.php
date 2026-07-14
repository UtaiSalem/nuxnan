<?php

use App\Http\Controllers\Api\Learn\Student\Card\StudentCardRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('academies/{academy}/student-card-requests')->group(function () {
    Route::middleware('academy.permission:students.cards.request,students.cards.produce')->group(function () {
        Route::get('my-classrooms', [StudentCardRequestController::class, 'myClassrooms']);
        Route::get('classrooms/{classroom}/students', [StudentCardRequestController::class, 'classroomStudents']);
        Route::post('/', [StudentCardRequestController::class, 'store']);
        Route::post('bulk', [StudentCardRequestController::class, 'bulkStore']);
        Route::patch('{studentCardRequest}/cancel', [StudentCardRequestController::class, 'cancel']);
    });

    Route::middleware('academy.permission:students.cards.produce')->group(function () {
        Route::get('/', [StudentCardRequestController::class, 'index']);
        Route::get('counts', [StudentCardRequestController::class, 'counts']);
        Route::post('bulk-transition', [StudentCardRequestController::class, 'bulkTransition']);
        Route::get('{studentCardRequest}', [StudentCardRequestController::class, 'show']);
        Route::patch('{studentCardRequest}/approve', [StudentCardRequestController::class, 'approve']);
        Route::patch('{studentCardRequest}/reject', [StudentCardRequestController::class, 'reject']);
        Route::patch('{studentCardRequest}/start', [StudentCardRequestController::class, 'start']);
        Route::patch('{studentCardRequest}/complete', [StudentCardRequestController::class, 'complete']);
    });
});
