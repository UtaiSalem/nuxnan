<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Learn\Academy\AcademyController;
use App\Http\Controllers\Api\Learn\Academy\AcademyPostController;
use App\Http\Controllers\Api\Learn\Academy\AcademyCourseController;
use App\Http\Controllers\Api\Learn\Academy\AcademyMemberController;
use App\Http\Controllers\Api\Learn\Academy\AcademyActivityController;


Route::middleware(['auth:api', config('jetstream.auth_session'), 'verified',])->prefix('/academies')->group(function () {
    Route::get('/', [AcademyController::class, 'index'])->name('academies');
    Route::post('/', [AcademyController::class, 'store'])->name('academies.store');
    Route::get('/create', [AcademyController::class, 'create'])->name('academy.create');
    Route::get('/{academy:name}', [AcademyController::class, 'show'])->name('academy.show');

    Route::get('/{academy:name}/feeds', [AcademyActivityController::class, 'index'])->name('academy.feeds');

    Route::get('/{academy:name}/courses', [AcademyCourseController::class, 'index'])->name('academy.courses.index');
    Route::get('/{academy:name}/courses/create', [AcademyCourseController::class, 'create'])->name('academy.courses.create');
    Route::post('/{academy}/courses', [AcademyCourseController::class, 'store'])->name('academy.courses.store');

    Route::patch('/{academy}/update', [AcademyController::class, 'update'])->name('academy.update');

    // Route::get('/{academy}/members', [AcademyMemberController::class, 'index'])->name('academy.members');
    Route::get('/{academy}/members', [AcademyMemberController::class, 'index'])->name('academy.members');
    Route::post('/{academy}/members', [AcademyMemberController::class, 'storemember']);
    Route::post('/{academy}/unmembers', [AcademyMemberController::class, 'unmember']);

    Route::get('/{academy}/posts/{post}', [AcademyPostController::class, 'show'])->name('academy_post.show');

});

Route::middleware(['auth:api', config('jetstream.auth_session'), 'verified',])->prefix('/api/academies')->group(function () {
    Route::get('/', [AcademyController::class, 'getMoreAcademies'])->name('api.academies.all');
    Route::get('/users/{user}/my-academies', [AcademyController::class, 'getMyAcademies'])->name('api.academies.my-academies');
    Route::get('/users/{user}/membered-academies', [AcademyController::class, 'getAuthMemberedAcademies'])->name('api.academies.membered');
    Route::get('/all-academies', [AcademyController::class, 'getAllAcademies'])->name('api.academies.all-academies');

    Route::get('/{academy}/courses', [AcademyCourseController::class, 'getAcademyCourses'])->name('api.academy.courses.getAcademyCourses');

    Route::post('/{academy}/posts', [AcademyPostController::class, 'store'])->name('api.academy.posts.store');
    
    Route::get('/{academy}/activities', [AcademyActivityController::class, 'getActivities'])->name('api.academy.activities.getActivities');
});
