<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Learn\Academy\AcademyController;
use App\Http\Controllers\Api\Learn\Academy\AcademyPostController;
use App\Http\Controllers\Api\Learn\Academy\AcademyPostCommentController;
use App\Http\Controllers\Api\Learn\Academy\AcademyCourseController;
use App\Http\Controllers\Api\Learn\Academy\AcademyMemberController;
use App\Http\Controllers\Api\Learn\Academy\AcademyActivityController;
use App\Http\Controllers\Api\Learn\Academy\AcademyGroupController;
use App\Http\Controllers\Api\Learn\Academy\AcademyRoleController;
use App\Http\Controllers\Api\Learn\Academy\AcademyGroupPermissionController;
use App\Http\Controllers\Api\Learn\Academy\DepartmentController;
use App\Http\Controllers\Api\Learn\Academy\ClassroomController;
use App\Http\Controllers\Api\Learn\Academy\CurriculumController;
use App\Http\Controllers\Api\Learn\Academy\ClassScheduleController;
use App\Http\Controllers\Api\Learn\Academy\FeeStructureController;
use App\Http\Controllers\Api\Learn\Academy\TuitionFeeController;
use App\Http\Controllers\Api\Learn\Academy\PaymentController;
use App\Http\Controllers\Api\Learn\Academy\ExpenseController;
use App\Http\Controllers\Api\Learn\Academy\BudgetController;
use App\Http\Controllers\Api\Learn\Academy\StaffController;
use App\Http\Controllers\Api\Learn\Academy\StaffAttendanceController;
use App\Http\Controllers\Api\Learn\Academy\LeaveRequestController;
use App\Http\Controllers\Api\Learn\Academy\PayrollController;
use App\Http\Controllers\Api\Learn\Academy\AnnouncementController;
use App\Http\Controllers\Api\Learn\Academy\SchoolEventController;
use App\Http\Controllers\Api\Learn\Academy\EmergencyAlertController;
use App\Http\Controllers\Api\Learn\Academy\MessageThreadController;
use App\Http\Controllers\Api\Learn\Academy\MeetingController;
use App\Http\Controllers\Api\Learn\Academy\ReportController;
use App\Http\Controllers\Api\Learn\Academy\DashboardWidgetController;
use App\Http\Controllers\Api\Learn\Academy\AnalyticsController;
use App\Http\Controllers\Api\Learn\Academy\GuardianController;
use App\Http\Controllers\Api\Learn\Academy\ParentDashboardController;
use App\Http\Controllers\Api\Learn\Academy\MemberActivityLogController;
use App\Http\Controllers\Api\Learn\Academy\InviteLinkController;
use App\Http\Controllers\Api\Learn\Academy\MemberTagController;
use App\Http\Controllers\Api\Learn\Academy\ClassroomGroupController;
use App\Http\Controllers\Api\Learn\Academy\ClassroomInvitationController;

// Public routes for invite links (no auth required for validation)
Route::get('/invite/{code}', [InviteLinkController::class, 'validateCode'])->name('invite.validate');

// Auth required routes for invite links
Route::middleware(['auth:api'])->group(function () {
    Route::post('/invite/{code}/join', [InviteLinkController::class, 'joinWithCode'])->name('invite.join');
});

Route::middleware(['auth:api'])->prefix('/academies')->group(function () {
    Route::get('/', [AcademyController::class, 'index'])->name('academies');
    Route::post('/', [AcademyController::class, 'store'])->name('academies.store');
    Route::get('/create', [AcademyController::class, 'create'])->name('academy.create');
    
    // Specific routes MUST come before wildcard routes
    Route::get('/all-academies', [AcademyController::class, 'getAllAcademies'])->name('academies.all-academies');
    Route::get('/users/{user}/my-academies', [AcademyController::class, 'getMyAcademies'])->name('academies.my-academies');
    Route::get('/users/{user}/membered-academies', [AcademyController::class, 'getAuthMemberedAcademies'])->name('academies.membered');
    Route::get('/by-id/{academy}', [AcademyController::class, 'show'])->name('academy.showById');
    Route::get('/my-student-card', [ClassroomController::class, 'getMyStudentCard'])->name('api.academy.myStudentCard');
    
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
    // Route::get('/{academy}/members', [AcademyMemberController::class, 'index'])->name('academy.members');
    // Route::get('/{academy}/members', [AcademyMemberController::class, 'index'])->name('academy.members');
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
    Route::patch('/{academy}/posts/{post}', [AcademyPostController::class, 'update'])->name('api.academy.posts.update');
    Route::delete('/{academy}/posts/{post}', [AcademyPostController::class, 'destroy'])->name('api.academy.posts.destroy');
    Route::get('/{academy}/activities', [AcademyActivityController::class, 'getActivities'])->name('api.academy.activities.getActivities');
    Route::get('/{academy}/groups', [AcademyGroupController::class, 'index'])->name('api.academy.groups.index');
    Route::post('/{academy}/groups', [AcademyGroupController::class, 'store'])->name('api.academy.groups.store');
    
    // Academy Post Comments Routes
    Route::get('/{academy}/posts/{academy_post}/comments', [AcademyPostCommentController::class, 'index'])->name('api.academy.posts.comments.index');
    Route::post('/{academy}/posts/{academy_post}/comments', [AcademyPostCommentController::class, 'store'])->name('api.academy.posts.comments.store');
    Route::patch('/{academy}/posts/{academy_post}/comments/{comment}', [AcademyPostCommentController::class, 'update'])->name('api.academy.posts.comments.update');
    Route::delete('/{academy}/posts/{academy_post}/comments/{comment}', [AcademyPostCommentController::class, 'destroy'])->name('api.academy.posts.comments.destroy');
    
    // Academy Post Reactions Routes
    Route::post('/{academy}/posts/{post}/like', [\App\Http\Controllers\Api\Learn\Academy\AcademyPostReactionController::class, 'toggleLike'])->name('api.academy.posts.like');
    Route::post('/{academy}/posts/{post}/dislike', [\App\Http\Controllers\Api\Learn\Academy\AcademyPostReactionController::class, 'toggleDislike'])->name('api.academy.posts.dislike');
    
    // Academy Member Invitation API Routes
    Route::post('/{academy}/invite', [AcademyMemberController::class, 'inviteMember'])->name('api.academy.invite');
    Route::post('/{academy}/accept-invite', [AcademyMemberController::class, 'acceptInvitation'])->name('api.academy.accept-invite');
    Route::post('/{academy}/decline-invite', [AcademyMemberController::class, 'declineInvitation'])->name('api.academy.decline-invite');
    Route::get('/{academy}/pending-requests', [AcademyMemberController::class, 'getPendingRequests'])->name('api.academy.pending-requests');
    
    // ============================================
    // Enhanced Member Management Routes
    // ============================================
    Route::get('/{academy}/members/search', [AcademyMemberController::class, 'searchMembers'])->name('api.academy.members.search');
    Route::get('/{academy}/members/stats', [AcademyMemberController::class, 'getMemberStats'])->name('api.academy.members.stats');
    Route::get('/{academy}/members/filter-options', [AcademyMemberController::class, 'getFilterOptions'])->name('api.academy.members.filter-options');
    
    // Member management (accept/reject/suspend/remove)
    Route::post('/{academy}/members/{member}/accept', [AcademyMemberController::class, 'acceptmember'])->name('api.academy.members.accept');
    Route::post('/{academy}/members/{member}/reject', [AcademyMemberController::class, 'rejectmember'])->name('api.academy.members.reject');
    Route::delete('/{academy}/members/{member}', [AcademyMemberController::class, 'removeMember'])->name('api.academy.members.remove');
    Route::post('/{academy}/members/{member}/suspend', [AcademyMemberController::class, 'suspendMember'])->name('api.academy.members.suspend');
    Route::post('/{academy}/members/{member}/unsuspend', [AcademyMemberController::class, 'unsuspendMember'])->name('api.academy.members.unsuspend');
    Route::patch('/{academy}/members/{member}', [AcademyMemberController::class, 'updateMember'])->name('api.academy.members.update');
    Route::patch('/{academy}/members/{member}/identity', [AcademyMemberController::class, 'updateIdentity'])->name('api.academy.members.updateIdentity');
    
    // Member profile routes
    Route::get('/{academy}/members/{member}/profile', [AcademyMemberController::class, 'getMemberProfile'])->name('api.academy.members.profile');
    Route::get('/{academy}/members/{member}/courses', [AcademyMemberController::class, 'getMemberCourses'])->name('api.academy.members.courses');
    Route::get('/{academy}/members/{member}/activity', [AcademyMemberController::class, 'getMemberActivity'])->name('api.academy.members.activity');
    
    // Bulk invite members
    Route::post('/{academy}/members/invite', [AcademyMemberController::class, 'bulkInviteMembers'])->name('api.academy.members.bulkInvite');
    
    // Bulk actions for members
    Route::post('/{academy}/members/bulk-action', [AcademyMemberController::class, 'bulkAction'])->name('api.academy.members.bulkAction');
    Route::post('/{academy}/members/export-selected', [AcademyMemberController::class, 'exportSelectedMembers'])->name('api.academy.members.exportSelected');
    
    // Bulk import/export members
    Route::post('/{academy}/members/import', [AcademyMemberController::class, 'importMembersFromCsv'])->name('api.academy.members.import');
    Route::get('/{academy}/members/export', [AcademyMemberController::class, 'exportMembersToCsv'])->name('api.academy.members.export');
    
    // Academy Settings
    Route::post('/{academy}/settings', [AcademyController::class, 'updateSettings'])->name('api.academy.settings.update');
    
    // ============================================
    // Academy Roles & Permissions Routes
    // ============================================
    Route::get('/{academy}/roles', [AcademyRoleController::class, 'index'])->name('api.academy.roles.index');
    Route::get('/{academy}/roles/available', [AcademyRoleController::class, 'available'])->name('api.academy.roles.available');
    Route::get('/{academy}/my-role', [AcademyRoleController::class, 'myRole'])->name('api.academy.roles.my');
    Route::post('/{academy}/roles', [AcademyRoleController::class, 'store'])->name('api.academy.roles.store');
    Route::put('/{academy}/roles/{role}', [AcademyRoleController::class, 'update'])->name('api.academy.roles.update');
    Route::delete('/{academy}/roles/{role}', [AcademyRoleController::class, 'destroy'])->name('api.academy.roles.destroy');
    
    // Assign role to member
    Route::post('/{academy}/members/{member}/role', [AcademyRoleController::class, 'assignRole'])->name('api.academy.members.assignRole');
    Route::post('/{academy}/members/bulk-role', [AcademyRoleController::class, 'bulkAssignRole'])->name('api.academy.members.bulkAssignRole');
    
    // Get all available permissions (for role creation UI)
    Route::get('/permissions/all', [AcademyRoleController::class, 'permissions'])->name('api.academy.permissions.all');

    // ============================================
    // Guardian Management Routes (ผู้ปกครอง)
    // ============================================
    Route::prefix('{academy}/guardians')->group(function () {
        Route::get('/', [GuardianController::class, 'getAllGuardians'])->name('api.academy.guardians.index');
        Route::get('statistics', [GuardianController::class, 'getStatistics'])->name('api.academy.guardians.statistics');
    });
    
    // Student-specific guardian routes
    Route::prefix('{academy}/students/{student}/guardians')->group(function () {
        Route::get('/', [GuardianController::class, 'index'])->name('api.academy.students.guardians.index');
        Route::post('/', [GuardianController::class, 'store'])->name('api.academy.students.guardians.store');
    });
    
    // Guardian detail routes
    Route::prefix('{academy}/guardians/{guardian}')->group(function () {
        Route::patch('/', [GuardianController::class, 'update'])->name('api.academy.guardians.update');
        Route::delete('/', [GuardianController::class, 'destroy'])->name('api.academy.guardians.destroy');
        Route::post('link-user', [GuardianController::class, 'linkUser'])->name('api.academy.guardians.linkUser');
    });

    // ============================================
    // Parent Dashboard Routes (แดชบอร์ดผู้ปกครอง)
    // ============================================
    Route::prefix('{academy}/parent')->group(function () {
        Route::get('children', [ParentDashboardController::class, 'getMyChildren'])->name('api.academy.parent.children');
        Route::get('children/{student}', [ParentDashboardController::class, 'getChildDetail'])->name('api.academy.parent.child.detail');
        Route::get('children/{student}/grades', [ParentDashboardController::class, 'getChildGrades'])->name('api.academy.parent.child.grades');
        Route::get('children/{student}/attendance', [ParentDashboardController::class, 'getChildAttendance'])->name('api.academy.parent.child.attendance');
        Route::get('announcements', [ParentDashboardController::class, 'getAnnouncements'])->name('api.academy.parent.announcements');
        Route::get('events', [ParentDashboardController::class, 'getUpcomingEvents'])->name('api.academy.parent.events');
        Route::get('fees', [ParentDashboardController::class, 'getFeeStatus'])->name('api.academy.parent.fees');
    });

    // ============================================
    // Member Activity Log Routes (ประวัติกิจกรรม)
    // ============================================
    Route::get('activity-log/actions', [MemberActivityLogController::class, 'getAvailableActions'])->name('api.academy.activity-log.actions');
    Route::prefix('{academy}/activity-log')->group(function () {
        Route::get('/', [MemberActivityLogController::class, 'index'])->name('api.academy.activity-log.index');
        Route::get('statistics', [MemberActivityLogController::class, 'getStatistics'])->name('api.academy.activity-log.statistics');
        Route::get('member/{member}', [MemberActivityLogController::class, 'getMemberLogs'])->name('api.academy.activity-log.member');
    });

    // ============================================
    // Invite Link Routes (ลิงก์เชิญ)
    // ============================================
    Route::prefix('{academy}/invite-links')->group(function () {
        Route::get('/', [InviteLinkController::class, 'index'])->name('api.academy.invite-links.index');
        Route::post('/', [InviteLinkController::class, 'store'])->name('api.academy.invite-links.store');
        Route::patch('/{link}', [InviteLinkController::class, 'update'])->name('api.academy.invite-links.update');
        Route::delete('/{link}', [InviteLinkController::class, 'destroy'])->name('api.academy.invite-links.destroy');
        Route::post('/{link}/toggle-active', [InviteLinkController::class, 'toggleActive'])->name('api.academy.invite-links.toggleActive');
        Route::post('/{link}/reset-count', [InviteLinkController::class, 'resetUseCount'])->name('api.academy.invite-links.resetCount');
    });

    // ============================================
    // Member Tags Routes (แท็กสมาชิก)
    // ============================================
    Route::get('member-tags/colors', [MemberTagController::class, 'getAvailableColors'])->name('api.academy.member-tags.colors');
    Route::prefix('{academy}/member-tags')->group(function () {
        Route::get('/', [MemberTagController::class, 'index'])->name('api.academy.member-tags.index');
        Route::post('/', [MemberTagController::class, 'store'])->name('api.academy.member-tags.store');
        Route::patch('/{tag}', [MemberTagController::class, 'update'])->name('api.academy.member-tags.update');
        Route::delete('/{tag}', [MemberTagController::class, 'destroy'])->name('api.academy.member-tags.destroy');
        Route::get('/{tag}/members', [MemberTagController::class, 'getTagMembers'])->name('api.academy.member-tags.members');
        Route::post('/bulk-add', [MemberTagController::class, 'bulkAddTag'])->name('api.academy.member-tags.bulkAdd');
    });
    
    // Member tag management (per member)
    Route::prefix('{academy}/members/{member}/tags')->group(function () {
        Route::get('/', [MemberTagController::class, 'getMemberTags'])->name('api.academy.members.tags.index');
        Route::post('/', [MemberTagController::class, 'addTagsToMember'])->name('api.academy.members.tags.add');
        Route::delete('/', [MemberTagController::class, 'removeTagsFromMember'])->name('api.academy.members.tags.remove');
    });

    // ============================================
    // Department Management Routes (ฝ่ายงาน)
    // ============================================
    Route::prefix('{academy}/departments')->group(function () {
        Route::get('/', [DepartmentController::class, 'index'])->name('api.academy.departments.index');
        Route::post('/', [DepartmentController::class, 'store'])->name('api.academy.departments.store');
        Route::get('statistics', [DepartmentController::class, 'getStatistics'])->name('api.academy.departments.statistics');
    });

    Route::prefix('departments/{department}')->group(function () {
        Route::get('/', [DepartmentController::class, 'show'])->name('api.academy.departments.show');
        Route::patch('/', [DepartmentController::class, 'update'])->name('api.academy.departments.update');
        Route::delete('/', [DepartmentController::class, 'destroy'])->name('api.academy.departments.destroy');
        Route::get('members', [DepartmentController::class, 'getMembers'])->name('api.academy.departments.members');
        Route::post('members', [DepartmentController::class, 'addMember'])->name('api.academy.departments.members.add');
        Route::post('members/bulk', [DepartmentController::class, 'bulkAddMembers'])->name('api.academy.departments.members.bulk');
        Route::delete('members', [DepartmentController::class, 'removeMember'])->name('api.academy.departments.members.remove');
        Route::patch('members/role', [DepartmentController::class, 'updateMemberRole'])->name('api.academy.departments.members.updateRole');
        
        // Permission management
        Route::get('permissions', [AcademyGroupPermissionController::class, 'index'])->name('api.academy.departments.permissions.index');
        Route::put('permissions', [AcademyGroupPermissionController::class, 'update'])->name('api.academy.departments.permissions.update');
    });

    // ============================================
    // Classroom Management Routes (ห้องเรียน)
    // ============================================
    Route::prefix('{academy}/classrooms')->group(function () {
        Route::get('/', [ClassroomController::class, 'index'])->name('api.academy.classrooms.index');
        Route::post('/', [ClassroomController::class, 'store'])->name('api.academy.classrooms.store');
        Route::get('grade-levels', [ClassroomController::class, 'getGradeLevels'])->name('api.academy.classrooms.gradeLevels');
        Route::get('statistics', [ClassroomController::class, 'getStatistics'])->name('api.academy.classrooms.statistics');
        Route::get('students', [ClassroomController::class, 'getAllStudents'])->name('api.academy.classrooms.allStudents');
    });

    Route::prefix('{academy}/classrooms/{classroom}')->group(function () {
        Route::get('/', [ClassroomController::class, 'show'])->name('api.academy.classrooms.show');
        Route::patch('/', [ClassroomController::class, 'update'])->name('api.academy.classrooms.update');
        Route::delete('/', [ClassroomController::class, 'destroy'])->name('api.academy.classrooms.destroy');
        Route::post('archive', [ClassroomController::class, 'archive'])->name('api.academy.classrooms.archive');

        // Attendance routes removed — replaced by school-attendances (session-based)

        // Legacy student management
        Route::post('students', [ClassroomController::class, 'addStudents'])->name('api.academy.classrooms.students.add');
        Route::delete('students/{student}', [ClassroomController::class, 'removeStudent'])->name('api.academy.classrooms.students.remove');
        Route::patch('students/{student}/number', [ClassroomController::class, 'updateStudentNumber'])->name('api.academy.classrooms.students.updateNumber');

        // New member management
        Route::get('members', [ClassroomController::class, 'listMembers'])->name('api.academy.classrooms.members.index');
        Route::post('members', [ClassroomController::class, 'addMember'])->name('api.academy.classrooms.members.add');
        Route::post('members/bulk', [ClassroomController::class, 'bulkAddMembers'])->name('api.academy.classrooms.members.bulk');
        Route::patch('members/{member}', [ClassroomController::class, 'updateMember'])->name('api.academy.classrooms.members.update');
        Route::delete('members/{member}', [ClassroomController::class, 'removeMember'])->name('api.academy.classrooms.members.remove');

        // Classroom Groups
        Route::get('groups', [ClassroomGroupController::class, 'index'])->name('api.academy.classrooms.groups.index');
        Route::post('groups', [ClassroomGroupController::class, 'store'])->name('api.academy.classrooms.groups.store');
        Route::get('groups/{group}', [ClassroomGroupController::class, 'show'])->name('api.academy.classrooms.groups.show');
        Route::patch('groups/{group}', [ClassroomGroupController::class, 'update'])->name('api.academy.classrooms.groups.update');
        Route::delete('groups/{group}', [ClassroomGroupController::class, 'destroy'])->name('api.academy.classrooms.groups.destroy');
        Route::post('groups/{group}/members', [ClassroomGroupController::class, 'addMember'])->name('api.academy.classrooms.groups.members.add');
        Route::post('groups/{group}/members/bulk', [ClassroomGroupController::class, 'bulkAddMembers'])->name('api.academy.classrooms.groups.members.bulk');
        Route::delete('groups/{group}/members/{member}', [ClassroomGroupController::class, 'removeMember'])->name('api.academy.classrooms.groups.members.remove');

        // Classroom Invitations
        Route::get('invitations', [ClassroomInvitationController::class, 'index'])->name('api.academy.classrooms.invitations.index');
        Route::post('invitations', [ClassroomInvitationController::class, 'store'])->name('api.academy.classrooms.invitations.store');
        Route::delete('invitations/{invitation}', [ClassroomInvitationController::class, 'cancel'])->name('api.academy.classrooms.invitations.cancel');
    });

    // Member transfer between classrooms
    Route::post('{academy}/classrooms/transfer-member', [ClassroomController::class, 'transferMember'])->name('api.academy.classrooms.transferMember');

    // Student enrollment management (new system)
    Route::post('{academy}/classrooms/transfer-student', [ClassroomController::class, 'transferStudent'])->name('api.academy.classrooms.transferStudent');
    Route::post('{academy}/classrooms/promote', [ClassroomController::class, 'promoteClassroom'])->name('api.academy.classrooms.promote');
    Route::get('{academy}/students/{student}/enrollment-history', [ClassroomController::class, 'getStudentEnrollmentHistory'])->name('api.academy.students.enrollmentHistory');

    // Invitation accept/decline (token-based, not classroom-scoped)
    Route::post('classrooms/invitations/{token}/accept', [ClassroomInvitationController::class, 'accept'])->name('api.academy.classrooms.invitations.accept');
    Route::post('classrooms/invitations/{token}/decline', [ClassroomInvitationController::class, 'decline'])->name('api.academy.classrooms.invitations.decline');

    // Join via classroom code
    Route::post('classrooms/join', [ClassroomInvitationController::class, 'joinViaCode'])->name('api.academy.classrooms.join');

    // =====================================================
    // Curriculum Routes - ระบบหลักสูตร
    // =====================================================
    
    // Curriculum CRUD
    Route::prefix('{academy}/curriculums')->group(function () {
        Route::get('/', [CurriculumController::class, 'index'])->name('api.academy.curriculums.index');
        Route::post('/', [CurriculumController::class, 'store'])->name('api.academy.curriculums.store');
        Route::get('statistics', [CurriculumController::class, 'getStatistics'])->name('api.academy.curriculums.statistics');
    });

    // Curriculum detail routes
    Route::prefix('curriculums/{curriculum}')->group(function () {
        Route::get('/', [CurriculumController::class, 'show'])->name('api.academy.curriculums.show');
        Route::patch('/', [CurriculumController::class, 'update'])->name('api.academy.curriculums.update');
        Route::delete('/', [CurriculumController::class, 'destroy'])->name('api.academy.curriculums.destroy');
        
        // Curriculum Courses
        Route::get('courses', [CurriculumController::class, 'getCourses'])->name('api.academy.curriculums.courses');
        Route::post('courses', [CurriculumController::class, 'addCourse'])->name('api.academy.curriculums.courses.add');
        Route::post('courses/bulk', [CurriculumController::class, 'bulkAddCourses'])->name('api.academy.curriculums.courses.bulkAdd');
        Route::patch('courses/{curriculumCourse}', [CurriculumController::class, 'updateCourse'])->name('api.academy.curriculums.courses.update');
        Route::delete('courses/{curriculumCourse}', [CurriculumController::class, 'removeCourse'])->name('api.academy.curriculums.courses.remove');
        
        // Curriculum Students
        Route::get('students', [CurriculumController::class, 'getStudents'])->name('api.academy.curriculums.students');
        Route::post('students', [CurriculumController::class, 'enrollStudent'])->name('api.academy.curriculums.students.enroll');
        Route::post('students/bulk', [CurriculumController::class, 'bulkEnrollStudents'])->name('api.academy.curriculums.students.bulkEnroll');
        Route::patch('students/{student}', [CurriculumController::class, 'updateStudent'])->name('api.academy.curriculums.students.update');
        Route::delete('students/{student}', [CurriculumController::class, 'removeStudent'])->name('api.academy.curriculums.students.remove');
    });

    // Available courses for curriculum
    Route::get('{academy}/curriculums/{curriculum}/available-courses', [CurriculumController::class, 'getAvailableCourses'])->name('api.academy.curriculums.availableCourses');

    // =====================================================
    // Class Schedule Routes - ระบบตารางเรียน
    // =====================================================
    
    Route::prefix('{academy}/schedules')->group(function () {
        Route::get('/', [ClassScheduleController::class, 'index'])->name('api.academy.schedules.index');
        Route::get('/timetable', [ClassScheduleController::class, 'timetable'])->name('api.academy.schedules.timetable');
        Route::get('/today', [ClassScheduleController::class, 'today'])->name('api.academy.schedules.today');
        Route::get('/check-availability', [ClassScheduleController::class, 'checkAvailability'])->name('api.academy.schedules.checkAvailability');
        Route::post('/', [ClassScheduleController::class, 'store'])->name('api.academy.schedules.store');
        Route::post('/bulk', [ClassScheduleController::class, 'bulkStore'])->name('api.academy.schedules.bulkStore');
        Route::patch('/{id}', [ClassScheduleController::class, 'update'])->name('api.academy.schedules.update');
        Route::delete('/{id}', [ClassScheduleController::class, 'destroy'])->name('api.academy.schedules.destroy');
    });

    // =====================================================
    // Finance Routes - ระบบการเงิน
    // =====================================================
    
    // Fee Structure - โครงสร้างค่าธรรมเนียม
    Route::prefix('{academy}/fee-structures')->group(function () {
        Route::get('/', [FeeStructureController::class, 'index'])->name('api.academy.feeStructures.index');
        Route::post('/', [FeeStructureController::class, 'store'])->name('api.academy.feeStructures.store');
        Route::get('/fee-types', [FeeStructureController::class, 'feeTypes'])->name('api.academy.feeStructures.feeTypes');
        Route::get('/{feeStructure}', [FeeStructureController::class, 'show'])->name('api.academy.feeStructures.show');
        Route::patch('/{feeStructure}', [FeeStructureController::class, 'update'])->name('api.academy.feeStructures.update');
        Route::delete('/{feeStructure}', [FeeStructureController::class, 'destroy'])->name('api.academy.feeStructures.destroy');
        Route::post('/{feeStructure}/duplicate', [FeeStructureController::class, 'duplicate'])->name('api.academy.feeStructures.duplicate');
        Route::post('/{feeStructure}/items', [FeeStructureController::class, 'addItem'])->name('api.academy.feeStructures.items.add');
        Route::patch('/{feeStructure}/items/{feeItem}', [FeeStructureController::class, 'updateItem'])->name('api.academy.feeStructures.items.update');
        Route::delete('/{feeStructure}/items/{feeItem}', [FeeStructureController::class, 'removeItem'])->name('api.academy.feeStructures.items.remove');
    });

    // Tuition Fee - ใบแจ้งหนี้ค่าเล่าเรียน
    Route::prefix('{academy}/tuition-fees')->group(function () {
        Route::get('/', [TuitionFeeController::class, 'index'])->name('api.academy.tuitionFees.index');
        Route::post('/', [TuitionFeeController::class, 'store'])->name('api.academy.tuitionFees.store');
        Route::post('/bulk-generate', [TuitionFeeController::class, 'bulkGenerate'])->name('api.academy.tuitionFees.bulkGenerate');
        Route::get('/summary', [TuitionFeeController::class, 'summary'])->name('api.academy.tuitionFees.summary');
        Route::get('/student/{userId}', [TuitionFeeController::class, 'studentInvoices'])->name('api.academy.tuitionFees.student');
        Route::get('/{tuitionFee}', [TuitionFeeController::class, 'show'])->name('api.academy.tuitionFees.show');
        Route::patch('/{tuitionFee}', [TuitionFeeController::class, 'update'])->name('api.academy.tuitionFees.update');
        Route::post('/{tuitionFee}/cancel', [TuitionFeeController::class, 'cancel'])->name('api.academy.tuitionFees.cancel');
        Route::post('/{tuitionFee}/discount', [TuitionFeeController::class, 'addDiscount'])->name('api.academy.tuitionFees.addDiscount');
    });

    // Payments - การชำระเงิน
    Route::prefix('{academy}/payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('api.academy.payments.index');
        Route::post('/', [PaymentController::class, 'store'])->name('api.academy.payments.store');
        Route::get('/summary', [PaymentController::class, 'summary'])->name('api.academy.payments.summary');
        Route::get('/methods', [PaymentController::class, 'paymentMethods'])->name('api.academy.payments.methods');
        Route::get('/daily-report', [PaymentController::class, 'dailyReport'])->name('api.academy.payments.dailyReport');
        Route::get('/{payment}', [PaymentController::class, 'show'])->name('api.academy.payments.show');
        Route::post('/{payment}/confirm', [PaymentController::class, 'confirm'])->name('api.academy.payments.confirm');
        Route::post('/{payment}/reject', [PaymentController::class, 'reject'])->name('api.academy.payments.reject');
    });

    // Expenses - รายจ่าย
    Route::prefix('{academy}/expenses')->group(function () {
        Route::get('/', [ExpenseController::class, 'index'])->name('api.academy.expenses.index');
        Route::post('/', [ExpenseController::class, 'store'])->name('api.academy.expenses.store');
        Route::get('/summary', [ExpenseController::class, 'summary'])->name('api.academy.expenses.summary');
        Route::get('/categories', [ExpenseController::class, 'categories'])->name('api.academy.expenses.categories');
        Route::post('/categories', [ExpenseController::class, 'storeCategory'])->name('api.academy.expenses.categories.store');
        Route::patch('/categories/{category}', [ExpenseController::class, 'updateCategory'])->name('api.academy.expenses.categories.update');
        Route::delete('/categories/{category}', [ExpenseController::class, 'destroyCategory'])->name('api.academy.expenses.categories.destroy');
        Route::get('/{expense}', [ExpenseController::class, 'show'])->name('api.academy.expenses.show');
        Route::patch('/{expense}', [ExpenseController::class, 'update'])->name('api.academy.expenses.update');
        Route::delete('/{expense}', [ExpenseController::class, 'destroy'])->name('api.academy.expenses.destroy');
        Route::post('/{expense}/approve', [ExpenseController::class, 'approve'])->name('api.academy.expenses.approve');
        Route::post('/{expense}/reject', [ExpenseController::class, 'reject'])->name('api.academy.expenses.reject');
        Route::post('/{expense}/mark-paid', [ExpenseController::class, 'markAsPaid'])->name('api.academy.expenses.markPaid');
    });

    // Budgets - งบประมาณ
    Route::prefix('{academy}/budgets')->group(function () {
        Route::get('/', [BudgetController::class, 'index'])->name('api.academy.budgets.index');
        Route::post('/', [BudgetController::class, 'store'])->name('api.academy.budgets.store');
        Route::get('/summary', [BudgetController::class, 'summary'])->name('api.academy.budgets.summary');
        Route::get('/{budget}', [BudgetController::class, 'show'])->name('api.academy.budgets.show');
        Route::patch('/{budget}', [BudgetController::class, 'update'])->name('api.academy.budgets.update');
        Route::delete('/{budget}', [BudgetController::class, 'destroy'])->name('api.academy.budgets.destroy');
        Route::post('/{budget}/activate', [BudgetController::class, 'activate'])->name('api.academy.budgets.activate');
        Route::post('/{budget}/close', [BudgetController::class, 'close'])->name('api.academy.budgets.close');
        Route::post('/{budget}/add-funds', [BudgetController::class, 'addFunds'])->name('api.academy.budgets.addFunds');
        Route::post('/{budget}/transfer', [BudgetController::class, 'transfer'])->name('api.academy.budgets.transfer');
        Route::get('/{budget}/expenses', [BudgetController::class, 'expenses'])->name('api.academy.budgets.expenses');
    });

    // =====================================================
    // Staff System Routes - ระบบบุคลากร
    // =====================================================
    
    // Staff - บุคลากร
    Route::prefix('{academy}/staff')->group(function () {
        Route::get('/', [StaffController::class, 'index'])->name('api.academy.staff.index');
        Route::post('/', [StaffController::class, 'store'])->name('api.academy.staff.store');
        Route::get('/directory', [StaffController::class, 'directory'])->name('api.academy.staff.directory');
        Route::get('/summary', [StaffController::class, 'summary'])->name('api.academy.staff.summary');
        Route::get('/positions', [StaffController::class, 'positions'])->name('api.academy.staff.positions');
        Route::post('/positions', [StaffController::class, 'storePosition'])->name('api.academy.staff.positions.store');
        Route::patch('/positions/{position}', [StaffController::class, 'updatePosition'])->name('api.academy.staff.positions.update');
        Route::delete('/positions/{position}', [StaffController::class, 'destroyPosition'])->name('api.academy.staff.positions.destroy');
        Route::get('/{staff}', [StaffController::class, 'show'])->name('api.academy.staff.show');
        Route::patch('/{staff}', [StaffController::class, 'update'])->name('api.academy.staff.update');
        Route::patch('/{staff}/status', [StaffController::class, 'updateStatus'])->name('api.academy.staff.updateStatus');
        Route::delete('/{staff}', [StaffController::class, 'destroy'])->name('api.academy.staff.destroy');
    });

    // Staff Attendance - การลงเวลาทำงาน
    Route::prefix('{academy}/staff-attendance')->group(function () {
        Route::get('/', [StaffAttendanceController::class, 'index'])->name('api.academy.staffAttendance.index');
        Route::post('/', [StaffAttendanceController::class, 'store'])->name('api.academy.staffAttendance.store');
        Route::post('/check-in', [StaffAttendanceController::class, 'checkIn'])->name('api.academy.staffAttendance.checkIn');
        Route::post('/check-out', [StaffAttendanceController::class, 'checkOut'])->name('api.academy.staffAttendance.checkOut');
        Route::get('/daily-report', [StaffAttendanceController::class, 'dailyReport'])->name('api.academy.staffAttendance.dailyReport');
        Route::get('/staff/{staff}', [StaffAttendanceController::class, 'staffReport'])->name('api.academy.staffAttendance.staffReport');
        Route::get('/{attendance}', [StaffAttendanceController::class, 'show'])->name('api.academy.staffAttendance.show');
        Route::patch('/{attendance}', [StaffAttendanceController::class, 'update'])->name('api.academy.staffAttendance.update');
        Route::delete('/{attendance}', [StaffAttendanceController::class, 'destroy'])->name('api.academy.staffAttendance.destroy');
    });

    // Leave Requests - การลา
    Route::prefix('{academy}/leave-requests')->group(function () {
        Route::get('/', [LeaveRequestController::class, 'index'])->name('api.academy.leaveRequests.index');
        Route::post('/', [LeaveRequestController::class, 'store'])->name('api.academy.leaveRequests.store');
        Route::get('/summary', [LeaveRequestController::class, 'summary'])->name('api.academy.leaveRequests.summary');
        Route::get('/leave-types', [LeaveRequestController::class, 'leaveTypes'])->name('api.academy.leaveRequests.leaveTypes');
        Route::post('/leave-types', [LeaveRequestController::class, 'storeLeaveType'])->name('api.academy.leaveRequests.leaveTypes.store');
        Route::patch('/leave-types/{leaveType}', [LeaveRequestController::class, 'updateLeaveType'])->name('api.academy.leaveRequests.leaveTypes.update');
        Route::get('/staff/{staff}/balance', [LeaveRequestController::class, 'staffLeaveBalance'])->name('api.academy.leaveRequests.staffBalance');
        Route::get('/{leave}', [LeaveRequestController::class, 'show'])->name('api.academy.leaveRequests.show');
        Route::post('/{leave}/approve', [LeaveRequestController::class, 'approve'])->name('api.academy.leaveRequests.approve');
        Route::post('/{leave}/reject', [LeaveRequestController::class, 'reject'])->name('api.academy.leaveRequests.reject');
        Route::post('/{leave}/cancel', [LeaveRequestController::class, 'cancel'])->name('api.academy.leaveRequests.cancel');
    });

    // Payroll - เงินเดือน
    Route::prefix('{academy}/payroll')->group(function () {
        Route::get('/', [PayrollController::class, 'index'])->name('api.academy.payroll.index');
        Route::post('/', [PayrollController::class, 'store'])->name('api.academy.payroll.store');
        Route::post('/bulk-generate', [PayrollController::class, 'bulkGenerate'])->name('api.academy.payroll.bulkGenerate');
        Route::get('/summary', [PayrollController::class, 'summary'])->name('api.academy.payroll.summary');
        Route::get('/salary-structures', [PayrollController::class, 'salaryStructures'])->name('api.academy.payroll.salaryStructures');
        Route::post('/salary-structures', [PayrollController::class, 'storeSalaryStructure'])->name('api.academy.payroll.salaryStructures.store');
        Route::get('/{payroll}', [PayrollController::class, 'show'])->name('api.academy.payroll.show');
        Route::patch('/{payroll}', [PayrollController::class, 'update'])->name('api.academy.payroll.update');
        Route::post('/{payroll}/items', [PayrollController::class, 'addItem'])->name('api.academy.payroll.items.add');
        Route::delete('/{payroll}/items/{item}', [PayrollController::class, 'removeItem'])->name('api.academy.payroll.items.remove');
        Route::post('/{payroll}/approve', [PayrollController::class, 'approve'])->name('api.academy.payroll.approve');
        Route::post('/{payroll}/mark-paid', [PayrollController::class, 'markAsPaid'])->name('api.academy.payroll.markPaid');
        Route::get('/{payroll}/payslip', [PayrollController::class, 'payslip'])->name('api.academy.payroll.payslip');
    });

    // =====================================================
    // Communication System Routes - ระบบการสื่อสาร
    // =====================================================

    // Announcements - ประกาศ
    Route::prefix('{academy}/announcements')->group(function () {
        Route::get('/', [AnnouncementController::class, 'index'])->name('api.academy.announcements.index');
        Route::post('/', [AnnouncementController::class, 'store'])->name('api.academy.announcements.store');
        Route::get('/stats', [AnnouncementController::class, 'stats'])->name('api.academy.announcements.stats');
        Route::get('/{announcement}', [AnnouncementController::class, 'show'])->name('api.academy.announcements.show');
        Route::patch('/{announcement}', [AnnouncementController::class, 'update'])->name('api.academy.announcements.update');
        Route::post('/{announcement}/publish', [AnnouncementController::class, 'publish'])->name('api.academy.announcements.publish');
        Route::post('/{announcement}/unpublish', [AnnouncementController::class, 'unpublish'])->name('api.academy.announcements.unpublish');
        Route::delete('/{announcement}', [AnnouncementController::class, 'destroy'])->name('api.academy.announcements.destroy');
    });

    // School Events - กิจกรรมโรงเรียน
    Route::prefix('{academy}/events')->group(function () {
        Route::get('/', [SchoolEventController::class, 'index'])->name('api.academy.events.index');
        Route::post('/', [SchoolEventController::class, 'store'])->name('api.academy.events.store');
        Route::get('/upcoming', [SchoolEventController::class, 'upcoming'])->name('api.academy.events.upcoming');
        Route::get('/my-schedule', [SchoolEventController::class, 'mySchedule'])->name('api.academy.events.mySchedule');
        Route::post('/{event}/enroll', [SchoolEventController::class, 'enroll'])->name('api.academy.events.enroll');
        Route::get('/{event}', [SchoolEventController::class, 'show'])->name('api.academy.events.show');
        Route::patch('/{event}', [SchoolEventController::class, 'update'])->name('api.academy.events.update');
        Route::post('/{event}/publish', [SchoolEventController::class, 'publish'])->name('api.academy.events.publish');
        Route::post('/{event}/cancel', [SchoolEventController::class, 'cancel'])->name('api.academy.events.cancel');
        Route::delete('/{event}', [SchoolEventController::class, 'destroy'])->name('api.academy.events.destroy');
        // Event Registration
        Route::post('/{event}/register', [SchoolEventController::class, 'register'])->name('api.academy.events.register');
        Route::post('/{event}/cancel-registration', [SchoolEventController::class, 'cancelRegistration'])->name('api.academy.events.cancelRegistration');
        Route::get('/{event}/registrations', [SchoolEventController::class, 'registrations'])->name('api.academy.events.registrations');
        Route::post('/{event}/registrations/{registration}/confirm', [SchoolEventController::class, 'confirmRegistration'])->name('api.academy.events.confirmRegistration');
        Route::post('/{event}/registrations/{registration}/attendance', [SchoolEventController::class, 'markAttendance'])->name('api.academy.events.markAttendance');
    });

    // Emergency Alerts - การแจ้งเตือนฉุกเฉิน
    Route::prefix('{academy}/emergency-alerts')->group(function () {
        Route::get('/', [EmergencyAlertController::class, 'index'])->name('api.academy.emergencyAlerts.index');
        Route::post('/', [EmergencyAlertController::class, 'store'])->name('api.academy.emergencyAlerts.store');
        Route::get('/active', [EmergencyAlertController::class, 'active'])->name('api.academy.emergencyAlerts.active');
        Route::get('/{alert}', [EmergencyAlertController::class, 'show'])->name('api.academy.emergencyAlerts.show');
        Route::patch('/{alert}', [EmergencyAlertController::class, 'update'])->name('api.academy.emergencyAlerts.update');
        Route::post('/{alert}/deactivate', [EmergencyAlertController::class, 'deactivate'])->name('api.academy.emergencyAlerts.deactivate');
        Route::post('/{alert}/acknowledge', [EmergencyAlertController::class, 'acknowledge'])->name('api.academy.emergencyAlerts.acknowledge');
        Route::get('/{alert}/acknowledgements', [EmergencyAlertController::class, 'acknowledgements'])->name('api.academy.emergencyAlerts.acknowledgements');
        Route::get('/{alert}/need-help', [EmergencyAlertController::class, 'needHelp'])->name('api.academy.emergencyAlerts.needHelp');
        Route::delete('/{alert}', [EmergencyAlertController::class, 'destroy'])->name('api.academy.emergencyAlerts.destroy');
    });

    // Message Threads - ระบบข้อความ/สนทนา
    Route::prefix('{academy}/messages')->group(function () {
        Route::get('/', [MessageThreadController::class, 'index'])->name('api.academy.messages.index');
        Route::post('/', [MessageThreadController::class, 'store'])->name('api.academy.messages.store');
        Route::get('/unread-count', [MessageThreadController::class, 'unreadCount'])->name('api.academy.messages.unreadCount');
        Route::get('/{thread}', [MessageThreadController::class, 'show'])->name('api.academy.messages.show');
        Route::get('/{thread}/messages', [MessageThreadController::class, 'messages'])->name('api.academy.messages.messages');
        Route::post('/{thread}/messages', [MessageThreadController::class, 'sendMessage'])->name('api.academy.messages.sendMessage');
        Route::patch('/{thread}/messages/{message}', [MessageThreadController::class, 'editMessage'])->name('api.academy.messages.editMessage');
        Route::delete('/{thread}/messages/{message}', [MessageThreadController::class, 'deleteMessage'])->name('api.academy.messages.deleteMessage');
        Route::post('/{thread}/participants', [MessageThreadController::class, 'addParticipant'])->name('api.academy.messages.addParticipant');
        Route::delete('/{thread}/participants/{participantUserId}', [MessageThreadController::class, 'removeParticipant'])->name('api.academy.messages.removeParticipant');
        Route::post('/{thread}/archive', [MessageThreadController::class, 'archive'])->name('api.academy.messages.archive');
        Route::post('/{thread}/unarchive', [MessageThreadController::class, 'unarchive'])->name('api.academy.messages.unarchive');
        Route::post('/{thread}/mute', [MessageThreadController::class, 'mute'])->name('api.academy.messages.mute');
        Route::post('/{thread}/unmute', [MessageThreadController::class, 'unmute'])->name('api.academy.messages.unmute');
    });

    // Parent-Teacher Meetings - การนัดพบผู้ปกครอง
    Route::prefix('{academy}/meetings')->group(function () {
        Route::get('/slots', [MeetingController::class, 'slots'])->name('api.academy.meetings.slots');
        Route::get('/slots/available', [MeetingController::class, 'available'])->name('api.academy.meetings.available');
        Route::post('/slots', [MeetingController::class, 'createSlots'])->name('api.academy.meetings.createSlots');
        Route::post('/slots/bulk', [MeetingController::class, 'createBulkSlots'])->name('api.academy.meetings.createBulkSlots');
        Route::patch('/slots/{slot}', [MeetingController::class, 'updateSlot'])->name('api.academy.meetings.updateSlot');
        Route::delete('/slots/{slot}', [MeetingController::class, 'deleteSlot'])->name('api.academy.meetings.deleteSlot');
        Route::get('/slots/{slot}/bookings', [MeetingController::class, 'bookings'])->name('api.academy.meetings.slotBookings');
        Route::post('/slots/{slot}/book', [MeetingController::class, 'book'])->name('api.academy.meetings.book');
        Route::get('/teacher-bookings', [MeetingController::class, 'teacherBookings'])->name('api.academy.meetings.teacherBookings');
        Route::get('/my-bookings', [MeetingController::class, 'myBookings'])->name('api.academy.meetings.myBookings');
        Route::post('/bookings/{booking}/confirm', [MeetingController::class, 'confirmBooking'])->name('api.academy.meetings.confirmBooking');
        Route::post('/bookings/{booking}/cancel', [MeetingController::class, 'cancelBooking'])->name('api.academy.meetings.cancelBooking');
        Route::post('/bookings/{booking}/complete', [MeetingController::class, 'completeBooking'])->name('api.academy.meetings.completeBooking');
    });

    // ==================== PHASE 5: REPORTS & ANALYTICS ====================

    // Report Definitions
    Route::prefix('/{academy}/reports')->group(function () {
        // Report Definitions
        Route::get('/definitions', [ReportController::class, 'listDefinitions'])->name('api.academy.reports.definitions');
        Route::post('/definitions', [ReportController::class, 'createDefinition'])->name('api.academy.reports.definitions.create');
        Route::get('/definitions/{definition}', [ReportController::class, 'showDefinition'])->name('api.academy.reports.definitions.show');
        Route::patch('/definitions/{definition}', [ReportController::class, 'updateDefinition'])->name('api.academy.reports.definitions.update');
        Route::delete('/definitions/{definition}', [ReportController::class, 'deleteDefinition'])->name('api.academy.reports.definitions.delete');
        Route::post('/definitions/{definition}/toggle-status', [ReportController::class, 'toggleDefinitionStatus'])->name('api.academy.reports.definitions.toggleStatus');
        Route::post('/definitions/{definition}/duplicate', [ReportController::class, 'duplicateDefinition'])->name('api.academy.reports.definitions.duplicate');

        // Saved Reports
        Route::get('/saved', [ReportController::class, 'listSavedReports'])->name('api.academy.reports.saved');
        Route::post('/generate', [ReportController::class, 'generateReport'])->name('api.academy.reports.generate');
        Route::get('/saved/{report}', [ReportController::class, 'showSavedReport'])->name('api.academy.reports.saved.show');
        Route::delete('/saved/{report}', [ReportController::class, 'deleteSavedReport'])->name('api.academy.reports.saved.delete');
        Route::post('/saved/{report}/favorite', [ReportController::class, 'toggleFavorite'])->name('api.academy.reports.saved.favorite');
        Route::post('/saved/{report}/refresh', [ReportController::class, 'refreshReport'])->name('api.academy.reports.saved.refresh');

        // Report Schedules
        Route::get('/schedules', [ReportController::class, 'listSchedules'])->name('api.academy.reports.schedules');
        Route::post('/schedules', [ReportController::class, 'createSchedule'])->name('api.academy.reports.schedules.create');
        Route::patch('/schedules/{schedule}', [ReportController::class, 'updateSchedule'])->name('api.academy.reports.schedules.update');
        Route::delete('/schedules/{schedule}', [ReportController::class, 'deleteSchedule'])->name('api.academy.reports.schedules.delete');
        Route::post('/schedules/{schedule}/toggle-status', [ReportController::class, 'toggleScheduleStatus'])->name('api.academy.reports.schedules.toggleStatus');

        // Report Exports
        Route::get('/exports', [ReportController::class, 'listExports'])->name('api.academy.reports.exports');
        Route::post('/saved/{report}/export', [ReportController::class, 'exportReport'])->name('api.academy.reports.export');
        Route::get('/exports/{export}', [ReportController::class, 'showExport'])->name('api.academy.reports.exports.show');
    });

    // Dashboard Widgets
    Route::prefix('/{academy}/dashboard')->group(function () {
        // Widgets Management
        Route::get('/widgets', [DashboardWidgetController::class, 'listWidgets'])->name('api.academy.dashboard.widgets');
        Route::post('/widgets', [DashboardWidgetController::class, 'createWidget'])->name('api.academy.dashboard.widgets.create');
        Route::get('/widgets/{widget}', [DashboardWidgetController::class, 'showWidget'])->name('api.academy.dashboard.widgets.show');
        Route::patch('/widgets/{widget}', [DashboardWidgetController::class, 'updateWidget'])->name('api.academy.dashboard.widgets.update');
        Route::delete('/widgets/{widget}', [DashboardWidgetController::class, 'deleteWidget'])->name('api.academy.dashboard.widgets.delete');
        Route::post('/widgets/{widget}/toggle-status', [DashboardWidgetController::class, 'toggleWidgetStatus'])->name('api.academy.dashboard.widgets.toggleStatus');

        // User Dashboard Layouts
        Route::get('/layout', [DashboardWidgetController::class, 'getLayout'])->name('api.academy.dashboard.layout');
        Route::post('/layout', [DashboardWidgetController::class, 'saveLayout'])->name('api.academy.dashboard.layout.save');
        Route::post('/layout/add-widget', [DashboardWidgetController::class, 'addWidgetToLayout'])->name('api.academy.dashboard.layout.addWidget');
        Route::post('/layout/remove-widget', [DashboardWidgetController::class, 'removeWidgetFromLayout'])->name('api.academy.dashboard.layout.removeWidget');
        Route::post('/layout/update-position', [DashboardWidgetController::class, 'updateWidgetPosition'])->name('api.academy.dashboard.layout.updatePosition');
        Route::post('/layout/reset', [DashboardWidgetController::class, 'resetLayout'])->name('api.academy.dashboard.layout.reset');
    });

    // Extended Modules (Phase 6)
    Route::prefix('{academy}')->group(function () {
        // Library
        Route::prefix('library')->group(function () {
            Route::get('/books', [\App\Http\Controllers\Api\Learn\Academy\LibraryController::class, 'index'])->name('api.academy.library.books.index');
            Route::post('/books', [\App\Http\Controllers\Api\Learn\Academy\LibraryController::class, 'store'])->name('api.academy.library.books.store');
            Route::post('/borrow', [\App\Http\Controllers\Api\Learn\Academy\LibraryController::class, 'borrow'])->name('api.academy.library.borrow');
            Route::post('/borrowings/{borrowing}/return', [\App\Http\Controllers\Api\Learn\Academy\LibraryController::class, 'returnBook'])->name('api.academy.library.return');
        });

        // Assets
        Route::prefix('assets')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Learn\Academy\AssetController::class, 'index'])->name('api.academy.assets.index');
            Route::post('/', [\App\Http\Controllers\Api\Learn\Academy\AssetController::class, 'store'])->name('api.academy.assets.store');
            Route::post('/{asset}/maintenance', [\App\Http\Controllers\Api\Learn\Academy\AssetController::class, 'requestMaintenance'])->name('api.academy.assets.maintenance');
        });
    });

    // Gamification & Points
    Route::prefix('{academy}/gamification')->group(function () {
        Route::get('/points/rules', [\App\Http\Controllers\Api\Learn\Academy\AcademyPointRuleController::class, 'index'])->name('api.academy.gamification.points.rules');
        Route::post('/points/rules', [\App\Http\Controllers\Api\Learn\Academy\AcademyPointRuleController::class, 'store'])->name('api.academy.gamification.points.rules.store');
        Route::patch('/points/rules/{rule}', [\App\Http\Controllers\Api\Learn\Academy\AcademyPointRuleController::class, 'update'])->name('api.academy.gamification.points.rules.update');
        Route::delete('/points/rules/{rule}', [\App\Http\Controllers\Api\Learn\Academy\AcademyPointRuleController::class, 'destroy'])->name('api.academy.gamification.points.rules.delete');
        
        Route::get('/leaderboard/houses', [AnalyticsController::class, 'houseLeaderboard'])->name('api.academy.gamification.leaderboard.houses');
        Route::get('/leaderboard/classrooms', [AnalyticsController::class, 'classroomLeaderboard'])->name('api.academy.gamification.leaderboard.classrooms');
    });

    // Analytics
    Route::prefix('/{academy}/analytics')->group(function () {
        // Overview & Snapshots
        Route::get('/dashboard-stats', [AnalyticsController::class, 'dashboardStats'])->name('api.academy.analytics.dashboardStats');
        Route::get('/at-risk', [AnalyticsController::class, 'getAtRiskStudents'])->name('api.academy.analytics.atRisk');
        Route::get('/overview', [AnalyticsController::class, 'overview'])->name('api.academy.analytics.overview');
        Route::get('/snapshots', [AnalyticsController::class, 'getSnapshots'])->name('api.academy.analytics.snapshots');

        // KPI Management
        Route::get('/kpis', [AnalyticsController::class, 'listKpis'])->name('api.academy.analytics.kpis');
        Route::post('/kpis', [AnalyticsController::class, 'createKpi'])->name('api.academy.analytics.kpis.create');
        Route::get('/kpis/{kpi}', [AnalyticsController::class, 'showKpi'])->name('api.academy.analytics.kpis.show');
        Route::patch('/kpis/{kpi}', [AnalyticsController::class, 'updateKpi'])->name('api.academy.analytics.kpis.update');
        Route::delete('/kpis/{kpi}', [AnalyticsController::class, 'deleteKpi'])->name('api.academy.analytics.kpis.delete');
        Route::get('/kpis/{kpi}/values', [AnalyticsController::class, 'getKpiValues'])->name('api.academy.analytics.kpis.values');
        Route::post('/kpis/{kpi}/values', [AnalyticsController::class, 'recordKpiValue'])->name('api.academy.analytics.kpis.recordValue');

        // Comparative Analytics
        Route::get('/comparisons', [AnalyticsController::class, 'getComparisons'])->name('api.academy.analytics.comparisons');
        Route::post('/comparisons', [AnalyticsController::class, 'generateComparison'])->name('api.academy.analytics.comparisons.generate');

        // Trend Analysis
        Route::get('/trends', [AnalyticsController::class, 'getTrends'])->name('api.academy.analytics.trends');
        Route::post('/trends', [AnalyticsController::class, 'generateTrend'])->name('api.academy.analytics.trends.generate');

        // Alert Rules
        Route::get('/alert-rules', [AnalyticsController::class, 'listAlertRules'])->name('api.academy.analytics.alertRules');
        Route::post('/alert-rules', [AnalyticsController::class, 'createAlertRule'])->name('api.academy.analytics.alertRules.create');
        Route::patch('/alert-rules/{rule}', [AnalyticsController::class, 'updateAlertRule'])->name('api.academy.analytics.alertRules.update');
        Route::delete('/alert-rules/{rule}', [AnalyticsController::class, 'deleteAlertRule'])->name('api.academy.analytics.alertRules.delete');
        Route::post('/alert-rules/{rule}/toggle-status', [AnalyticsController::class, 'toggleAlertRuleStatus'])->name('api.academy.analytics.alertRules.toggleStatus');

        // Alert History
        Route::get('/alerts/history', [AnalyticsController::class, 'getAlertHistory'])->name('api.academy.analytics.alerts.history');
        Route::get('/alerts/unacknowledged-count', [AnalyticsController::class, 'getUnacknowledgedCount'])->name('api.academy.analytics.alerts.unacknowledgedCount');
        Route::post('/alerts/{alert}/acknowledge', [AnalyticsController::class, 'acknowledgeAlert'])->name('api.academy.analytics.alerts.acknowledge');
        Route::post('/alerts/bulk-acknowledge', [AnalyticsController::class, 'bulkAcknowledge'])->name('api.academy.analytics.alerts.bulkAcknowledge');
    });

    // =====================================================
    // School Attendance Routes (session-based, QR check-in)
    // =====================================================
    Route::prefix('{academy}/school-attendances')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Learn\Academy\SchoolAttendanceController::class, 'index'])->name('api.academy.schoolAttendance.index');
        Route::post('/', [\App\Http\Controllers\Api\Learn\Academy\SchoolAttendanceController::class, 'store'])->name('api.academy.schoolAttendance.store');
        Route::get('/student/{student}', [\App\Http\Controllers\Api\Learn\Academy\SchoolAttendanceController::class, 'studentHistory'])->name('api.academy.schoolAttendance.studentHistory');
        Route::get('/{attendance}', [\App\Http\Controllers\Api\Learn\Academy\SchoolAttendanceController::class, 'show'])->name('api.academy.schoolAttendance.show');
        Route::post('/{attendance}/check-in', [\App\Http\Controllers\Api\Learn\Academy\SchoolAttendanceController::class, 'checkIn'])->name('api.academy.schoolAttendance.checkIn');
        Route::post('/{attendance}/scan-student', [\App\Http\Controllers\Api\Learn\Academy\SchoolAttendanceController::class, 'scanStudent'])->name('api.academy.schoolAttendance.scanStudent');
        Route::post('/{attendance}/records', [\App\Http\Controllers\Api\Learn\Academy\SchoolAttendanceController::class, 'storeRecords'])->name('api.academy.schoolAttendance.storeRecords');
        Route::post('/{attendance}/close', [\App\Http\Controllers\Api\Learn\Academy\SchoolAttendanceController::class, 'close'])->name('api.academy.schoolAttendance.close');
    });
});

