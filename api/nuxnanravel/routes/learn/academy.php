<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Learn\Academy\AcademyController;
use App\Http\Controllers\Api\Learn\Academy\AcademyPostController;
use App\Http\Controllers\Api\Learn\Academy\AcademyCourseController;
use App\Http\Controllers\Api\Learn\Academy\AcademyMemberController;
use App\Http\Controllers\Api\Learn\Academy\AcademyActivityController;
use App\Http\Controllers\Api\Learn\Academy\AcademyGroupController;


Route::middleware(['auth:api'])->prefix('/academies')->group(function () {
    Route::get('/', [AcademyController::class, 'index'])->name('academies');
    Route::post('/', [AcademyController::class, 'store'])->name('academies.store');
    Route::get('/create', [AcademyController::class, 'create'])->name('academy.create');
    
    // Specific routes MUST come before wildcard routes
    Route::get('/all-academies', [AcademyController::class, 'getAllAcademies'])->name('academies.all-academies');
    Route::get('/users/{user}/my-academies', [AcademyController::class, 'getMyAcademies'])->name('academies.my-academies');
    Route::get('/users/{user}/membered-academies', [AcademyController::class, 'getAuthMemberedAcademies'])->name('academies.membered');
    Route::get('/by-id/{academy}', [AcademyController::class, 'show'])->name('academy.showById');
    
    // Wildcard route MUST be last
    Route::get('/{academy:name}', [AcademyController::class, 'show'])->name('academy.show');

    // Academy Groups
    Route::get('/{academy}/groups', [AcademyGroupController::class, 'index'])->name('academy.groups.index');
    Route::get('/{academy}/groups/type/{type}', [AcademyGroupController::class, 'getByType'])->name('academy.groups.byType');
    Route::post('/{academy}/groups', [AcademyGroupController::class, 'store'])->name('academy.groups.store');
    Route::get('/groups/{academyGroup}', [AcademyGroupController::class, 'show'])->name('academy.groups.show');
    Route::patch('/groups/{academyGroup}', [AcademyGroupController::class, 'update'])->name('academy.groups.update');
    Route::delete('/groups/{academyGroup}', [AcademyGroupController::class, 'destroy'])->name('academy.groups.destroy');
    
    // Group Members Management
    Route::get('/groups/{academyGroup}/members', [AcademyGroupController::class, 'getMembers'])->name('academy.groups.members');
    Route::post('/groups/{academyGroup}/members', [AcademyGroupController::class, 'addMember'])->name('academy.groups.members.add');
    Route::delete('/groups/{academyGroup}/members', [AcademyGroupController::class, 'removeMember'])->name('academy.groups.members.remove');
    Route::patch('/groups/{academyGroup}/members/role', [AcademyGroupController::class, 'updateMemberRole'])->name('academy.groups.members.updateRole');

    Route::get('/{academy:name}/feeds', [AcademyActivityController::class, 'index'])->name('academy.feeds');

    Route::get('/{academy:name}/courses', [AcademyCourseController::class, 'index'])->name('academy.courses.index');
    Route::get('/{academy:name}/courses/create', [AcademyCourseController::class, 'create'])->name('academy.courses.create');
    Route::post('/{academy}/courses', [AcademyCourseController::class, 'store'])->name('academy.courses.store');

    Route::patch('/{academy}/update', [AcademyController::class, 'update'])->name('academy.update');

    // Route::get('/{academy}/members', [AcademyMemberController::class, 'index'])->name('academy.members');
    Route::get('/{academy}/members', [AcademyMemberController::class, 'index'])->name('academy.members');
    Route::post('/{academy}/members', [AcademyMemberController::class, 'storemember']);
    Route::post('/{academy}/unmembers', [AcademyMemberController::class, 'unmember']);
    
    // Academy Member Invitation Routes
    Route::post('/{academy}/invite', [AcademyMemberController::class, 'inviteMember'])->name('academy.invite');
    Route::post('/{academy}/accept-invite', [AcademyMemberController::class, 'acceptInvitation'])->name('academy.accept-invite');
    Route::post('/{academy}/decline-invite', [AcademyMemberController::class, 'declineInvitation'])->name('academy.decline-invite');
    Route::get('/{academy}/pending-requests', [AcademyMemberController::class, 'getPendingRequests'])->name('academy.pending-requests');

    Route::get('/{academy}/posts/{post}', [AcademyPostController::class, 'show'])->name('academy_post.show');

});

// API Routes - Additional routes for API (Note: routes/api.php already has /api prefix from RouteServiceProvider)
Route::middleware(['auth:api'])->prefix('/academies')->group(function () {
    // Academy Groups API - specific routes first
    Route::get('/groups/{academyGroup}', [AcademyGroupController::class, 'show'])->name('api.academy.groups.show');
    Route::delete('/groups/{academyGroup}', [AcademyGroupController::class, 'destroy'])->name('api.academy.groups.destroy');
    Route::get('/groups/{academyGroup}/members', [AcademyGroupController::class, 'getMembers'])->name('api.academy.groups.members');
    Route::post('/groups/{academyGroup}/members', [AcademyGroupController::class, 'addMember'])->name('api.academy.groups.members.add');
    Route::delete('/groups/{academyGroup}/members', [AcademyGroupController::class, 'removeMember'])->name('api.academy.groups.members.remove');
    Route::patch('/groups/{academyGroup}/members/role', [AcademyGroupController::class, 'updateMemberRole'])->name('api.academy.groups.members.updateRole');
    
    // User's invitations
    Route::get('/my-invitations', [AcademyMemberController::class, 'getMyInvitations'])->name('api.academy.my-invitations');
    
    // Additional wildcard routes
    Route::get('/{academy}/courses', [AcademyCourseController::class, 'getAcademyCourses'])->name('api.academy.courses.getAcademyCourses');
    Route::get('/{academy}/members', [AcademyMemberController::class, 'getAcademyMembers'])->name('api.academy.members.list');
    Route::post('/{academy}/posts', [AcademyPostController::class, 'store'])->name('api.academy.posts.store');
    Route::get('/{academy}/activities', [AcademyActivityController::class, 'getActivities'])->name('api.academy.activities.getActivities');
    Route::get('/{academy}/groups', [AcademyGroupController::class, 'index'])->name('api.academy.groups.index');
    Route::post('/{academy}/groups', [AcademyGroupController::class, 'store'])->name('api.academy.groups.store');
    
    // Academy Member Invitation API Routes
    Route::post('/{academy}/invite', [AcademyMemberController::class, 'inviteMember'])->name('api.academy.invite');
    Route::post('/{academy}/accept-invite', [AcademyMemberController::class, 'acceptInvitation'])->name('api.academy.accept-invite');
    Route::post('/{academy}/decline-invite', [AcademyMemberController::class, 'declineInvitation'])->name('api.academy.decline-invite');
    Route::get('/{academy}/pending-requests', [AcademyMemberController::class, 'getPendingRequests'])->name('api.academy.pending-requests');
    
    // Member management (accept/reject requests)
    Route::post('/{academy}/members/{member}/accept', [AcademyMemberController::class, 'acceptmember'])->name('api.academy.members.accept');
    Route::post('/{academy}/members/{member}/reject', [AcademyMemberController::class, 'rejectmember'])->name('api.academy.members.reject');
});

