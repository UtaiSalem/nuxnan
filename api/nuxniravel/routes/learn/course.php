<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Learn\Course\lessons\topics\TopicController;
use App\Http\Controllers\Api\Learn\Course\info\CourseController;
use App\Http\Controllers\Api\Learn\Course\lessons\LessonController;
use App\Http\Controllers\Api\Learn\Course\questions\QuestionController;
use App\Http\Controllers\Api\Learn\Course\assignments\AssignmentController;
use App\Http\Controllers\Api\Learn\Course\posts\CoursePostController;
use App\Http\Controllers\Api\Learn\Course\quizzes\CourseQuizController;
use App\Http\Controllers\Api\Learn\Course\lessons\topics\TopicImageController;
use App\Http\Controllers\Api\Learn\Course\groups\CourseGroupController;
use App\Http\Controllers\Api\Learn\Course\lessons\images\LessonImageController;
use App\Http\Controllers\Api\Learn\Course\lessons\CourseLessonController;
use App\Http\Controllers\Api\Learn\Course\members\CourseMemberController;
use App\Http\Controllers\Api\Learn\Course\lessons\comments\LessonCommentController;
use App\Http\Controllers\Api\Learn\Course\questions\QuestionImageController;
use App\Http\Controllers\Api\Learn\Course\info\CourseActivityController;
use App\Http\Controllers\Api\Learn\Course\lessons\questions\LessonQuestionController;
use App\Http\Controllers\Api\Learn\Course\questions\QuestionAnswerController;
use App\Http\Controllers\Api\Learn\Course\questions\QuestionOptionController;
use App\Http\Controllers\Api\Learn\Course\assignments\AssignmentImageController;
use App\Http\Controllers\Api\Learn\Course\posts\CoursePostImageController;
use App\Http\Controllers\Api\Learn\Course\assignments\AssignmentAnswerController;
use App\Http\Controllers\Api\Learn\Course\attendances\AttendanceDetailController;
use App\Http\Controllers\Api\Learn\Course\assignments\CourseAssignmentController;
use App\Http\Controllers\Api\Learn\Course\attendances\CourseAttendanceController;
use App\Http\Controllers\Api\Learn\Course\quizzes\CourseQuizResultController;
use App\Http\Controllers\Api\Learn\Course\lessons\assignments\LessonAssignmentController;
use App\Http\Controllers\Api\Learn\Course\groups\CourseGroupMemberController;
use App\Http\Controllers\Api\Learn\Course\posts\CoursePostCommentController;
use App\Http\Controllers\Api\Learn\Course\posts\CoursePostReactionController;
use App\Http\Controllers\Api\Learn\Course\quizzes\CourseQuizQuestionController;
use App\Http\Controllers\Api\Learn\Course\questions\UserAnswerQuestionController;
use App\Http\Controllers\Api\Learn\Course\lessons\CourseLessonReactionController;
use App\Http\Controllers\Api\Learn\Course\lessons\comments\LessonCommentReactionController;
use App\Http\Controllers\Api\Learn\Course\posts\CoursePostImageCommentController;
use App\Http\Controllers\Api\Learn\Course\members\CourseMemberGradeProgressController;
use App\Http\Controllers\Api\Learn\Course\posts\CoursePostCommentReactionController;
use App\Http\Controllers\Api\Learn\Course\posts\CoursePostImageCommentReactionController;
use App\Http\Controllers\CoursePostShareController;


Route::middleware(['auth:api', 'verified'])->group(function () {
    Route::get('/courses/{course}/settings', [CourseController::class, 'settings'])->name('course.settings.page.show');
    Route::get('/courses/{course}/basic-info', [CourseController::class, 'basicInfo'])->name('course.basic-info.page.show');
});

Route::middleware(['auth:api', 'verified'])->prefix('/courses')->group(function () {
    Route::get('/', [CourseController::class, 'index'])->name('courses');
    Route::post('/', [CourseController::class, 'store'])->name('courses.store');
    Route::get('/create', [CourseController::class, 'create'])->name('courses.create');
    Route::get('/{course}', [CourseController::class, 'show'])->name('course.show');
    Route::put('/{course}', [CourseController::class, 'update'])->name('course.update');
    Route::patch('/{course}', [CourseController::class, 'update'])->name('course.part.update');
    Route::delete('/{course}', [CourseController::class, 'destroy'])->name('course.destroy');
    Route::get('/{course}/progress', [CourseController::class, 'progress'])->name('course.progress');
    
    Route::get('/users/{user}', [CourseController::class, 'getUserCourses'])->name('user.courses');
    Route::get('/users/{user}/member', [CourseController::class, 'getAuthMemberCourses'])->name('auth.member.courses');

    Route::post('/{course}/groups/{group}/members', [CourseGroupMemberController::class, 'store']);
    Route::delete('/{course}/members/groups/{group}', [CourseGroupMemberController::class, 'unMemberGroup']);
    Route::delete('/{course}/groups/{group}/members/{member}', [CourseGroupMemberController::class, 'unMemberGroup']);

    Route::resource('/{course}/quizzes/{quiz}/questions', CourseQuizQuestionController::class);
    Route::resource('/{course}/quizzes/{quiz}/results', CourseQuizResultController::class);

    Route::get('/{course}/groups/{group}/attendances', [CourseAttendanceController::class, 'getCourseGroupAttendances'])->name('course.groups.attendances');
    Route::post('/{course}/groups/{group}/attendances', [CourseAttendanceController::class, 'store'])->name('course.groups.attendances.store');

    Route::get('/{course}/feeds', [CourseActivityController::class, 'index'])->name('course.feeds');
    Route::get('/{course}/feeds/get-more-activities', [CourseActivityController::class, 'getActivities'])->name('course.feeds.getMoresActivities');
});



Route::middleware(['auth:api', 'verified'])->prefix('/api/courses')->group(function () {
    Route::get('/', [CourseController::class, 'getMoreCourses'])->name('api.courses.all');
    Route::get('/users/{user}', [CourseController::class, 'getMyCourses'])->name('api.courses.user-courses');
    Route::get('/users/{user}/my-courses', [CourseController::class, 'getMyCourses'])->name('api.courses.my-courses');
    Route::get('/users/{user}/membered', [CourseController::class, 'getAuthMemberedCourses'])->name('api.courses.member');
});

Route::middleware(['auth:api', 'verified'])->prefix('/courses/{course}/groups')->group(function () {
    Route::resource('/', CourseGroupController::class);
    Route::patch('/{group}', [CourseGroupController::class, 'update'])->name('course.groups.update');
    Route::delete('/{group}', [CourseGroupController::class, 'destroy'])->name('course.groups.destroy');
});

Route::middleware(['auth:api', 'verified'])->prefix('/courses/{course}/lessons')->group(function () {
    Route::get('/', [CourseLessonController::class, 'index'])->name('course.lessons.index');
    Route::post('/', [CourseLessonController::class, 'store'])->name('course.lessons.store');
    Route::get('/create', [CourseLessonController::class, 'create'])->name('course.lessons.create');
    Route::get('/{lesson}', [CourseLessonController::class, 'show'])->name('course.lessons.show');
    Route::get('/{lesson}/edit', [CourseLessonController::class, 'edit'])->name('course.lessons.edit');
    Route::put('/{lesson}', [CourseLessonController::class, 'update'])->name('course.lessons.update');
    Route::patch('/{lesson}', [CourseLessonController::class, 'update'])->name('course.lessons.part.update');
    Route::delete('/{lesson}', [CourseLessonController::class, 'destroy'])->name('course.lessons.destroy');


    // Lesson reactions - both singular and plural routes for compatibility
    Route::post('/{lesson}/like', [CourseLessonReactionController::class,'toggleLike'])->name('course.lessons.like');
    Route::post('/{lesson}/dislike', [CourseLessonReactionController::class,'toggleDislike'])->name('course.lessons.dislike');
    Route::post('/{lesson}/likes', [CourseLessonReactionController::class,'toggleLike'])->name('course.lessons.like.toggle');
    Route::post('/{lesson}/dislikes', [CourseLessonReactionController::class,'toggleDislike'])->name('course.lessons.dislike.toggle');
    Route::post('/{lesson}/bookmark', [CourseLessonController::class, 'toggleBookmark'])->name('course.lessons.bookmark.toggle');
    Route::post('/{lesson}/share', [CourseLessonController::class, 'shareLesson'])->name('course.lessons.share');


});


Route::middleware(['auth:api', 'verified'])->prefix('/lessons/{lesson}')->group(function () {
    Route::resource('/comments', LessonCommentController::class);


    Route::post('/comments/{lesson_comment}/like', [LessonCommentReactionController::class, 'toggleLike'])->name('lesson.comments.like.toggle');
    Route::post('/comments/{lesson_comment}/dislike', [LessonCommentReactionController::class, 'toggleDislike'])->name('lesson.comments.dislike.toggle');


});

Route::middleware(['auth:api', 'verified'])->prefix('/lessons')->group(function () {
    Route::resource('/', LessonController::class);
    Route::resource('/{lesson}/images', LessonImageController::class);
    Route::resource('/{lesson}/assignments', LessonAssignmentController::class);
    Route::resource('/{lesson}/questions', LessonQuestionController::class);
    
    Route::resource('/{lesson}/topics', TopicController::class);
    
});
Route::middleware(['auth:api', 'verified'])->prefix('/topics')->group(function () {
    Route::resource('/{topic}/images', TopicImageController::class);
});


Route::middleware(['auth:api', 'verified'])->group(function () {
    Route::resource('/assignments', AssignmentController::class);
    Route::resource('/asmimages', AssignmentImageController::class);
    Route::resource('/assignments/{assignment}/answers', AssignmentAnswerController::class);
    Route::post('/assignments/{assignment}/answers/{answer}/set-points', [AssignmentAnswerController::class, 'setAnswerPoints'])->name('assignments.answers.setAnswerPoints');
});

Route::middleware(['auth:api', 'verified'])->prefix('/courses/{course}')->group(function () {
    Route::resource('/assignments', CourseAssignmentController::class);

    Route::resource('/quizzes', CourseQuizController::class);
    Route::post('/quizzes/{quiz}/efficiency', [CourseQuizController::class, 'calculateSumQuizsEfficiency'])->name('course.member.quiz.efficiency');

    Route::resource('/quizzes/{quiz}/questions', CourseQuizQuestionController::class);
    Route::resource('/quizzes/{quiz}/results', CourseQuizResultController::class);

    // Route::get('/quizzes/get-quizzes', [CourseQuizController::class, 'getQuizzes'])->name('course.quizzes.getQuizzes');
});

Route::middleware(['auth:api', 'verified'])->group(function () {
    Route::get('/quizzes/get-quizzes', [CourseQuizController::class, 'getQuizzes'])->name('course.quizzes.getQuizzes');
    Route::post('/quizzes/{quiz}/duplicate', [CourseQuizController::class, 'duplicateQuiz'])->name('course.quizzes.duplicateQuizzes');
});


Route::middleware(['auth:api', 'verified'])->prefix('/courses/{course}/members')->group(function () {
    Route::get('/member-requesters', [CourseMemberController::class, 'getMembersRequesters'])->name('course.members.requesters');
    Route::get('/', [CourseMemberController::class, 'index'])->name('course.members.index');
    Route::post('/', [CourseMemberController::class, 'storemember'])->name('course.members.storemember');
    Route::get('/{member}/progress', [CourseMemberController::class, 'show'])->name('course.member.show');
    Route::delete('/{member}', [CourseMemberController::class, 'destroy']);
    Route::delete('/{member}/delete', [CourseMemberController::class, 'deleteCourseMember']);
    Route::post('/{member}/set-active-tab', [CourseMemberController::class, 'setActiveTab']);
    Route::post('/{member}/set-active-group-tab', [CourseMemberController::class, 'setActiveGroupTab']);
    Route::patch('/{member}/update', [CourseMemberController::class, 'update']);
    Route::patch('/{member}/bonus-points', [CourseMemberController::class, 'updateBonusPoints']);
    Route::patch('/{member}/grade_progress', [CourseMemberController::class, 'updateGradeProgress']);
    Route::patch('/{member}/notes-comments', [CourseMemberController::class, 'updateNotesComments']);

    Route::get('/{course_member}/member-settings', [CourseMemberController::class, 'memberSettings'])->name('course.member.settings.page.show');
    Route::get('/{member}/admin/progress', [CourseMemberController::class, 'memberProgress'])->name('course.admin.member.progress.show');
    Route::post('/process-scores', [CourseMemberController::class, 'processMemberScores'])->name('course.members.process-scores');

    Route::patch('/{member}/order-number', [CourseMemberController::class, 'updateOrderNumber'])->name('course.member.order-number.update');
    Route::patch('/{member}/member-code', [CourseMemberController::class, 'updateMemberCode'])->name('course.member.member-code.update');

    Route::post('/{member}/process-grades', [CourseMemberGradeProgressController::class, 'processGrades']);
    Route::patch('/{member}/edited-grade', [CourseMemberGradeProgressController::class, 'updateEditedGrade'])->name('course.member.edited-grade.update');
    Route::get('/{member}/member-grade-progress', [CourseMemberGradeProgressController::class, 'memberGradeProgress'])->name('course.member.grade-progress.show');

});

Route::middleware(['auth:api', 'verified'])->group(function () {
    Route::resource('/questions', QuestionController::class);
    Route::post('/questions/{question}/duplicate', [QuestionController::class, 'duplicateQuestion']);
    Route::patch('/questions/{question}/set_correct_option', [QuestionController::class, 'set_correct_option'])->name('questions.set_correct_option');

    Route::get('/user/questions', [QuestionController::class, 'getUserQuestions'])->name('user.get.questions');

    Route::resource('/questions/{question}/images', QuestionImageController::class);
    
    Route::resource('/questions/{question}/options', QuestionOptionController::class);
    Route::resource('/options', QuestionOptionController::class);
    
    Route::resource('/questions/{question}/answers', QuestionAnswerController::class);
    // Route::resource('/users/{user}/answers/{answer}/questions', UserAnswerQuestionController::class);
    // Route::resource('/users/{user}/questions/{question}/answers', UserAnswerQuestionController::class);
    Route::resource('/quizs/{quiz}/questions/{question}/answers', UserAnswerQuestionController::class);
});

Route::middleware(['auth:api', 'verified'])->group(function () {
    Route::get('/courses/{course}/attendances', [CourseAttendanceController::class, 'index'])->name('attendances.index');
    // Route::post('/attendances', [CourseAttendanceController::class, 'store'])->name('attendances.store');
    // Route::get('/attendances/{attendance}', [CourseAttendanceController::class, 'show'])->name('attendances.show');
    Route::patch('/attendances/{attendance}', [CourseAttendanceController::class, 'update'])->name('attendances.update');
    Route::delete('/attendances/{attendance}', [CourseAttendanceController::class, 'destroy'])->name('attendances.destroy');
    
    // Update member attendance status by admin
    Route::post('/attendances/{attendance}/member/{member}/update-status', [CourseAttendanceController::class, 'updateMemberStatus'])->name('attendances.member.update-status');
    
    // Student self check-in
    Route::post('/attendances/{attendance}/check-in', [CourseAttendanceController::class, 'studentCheckIn'])->name('attendances.student.check-in');
    
    // Update last access group tab for course member
    Route::patch('/courses/{course}/members/update-last-access-group', [CourseAttendanceController::class, 'updateLastAccessGroupTab'])->name('courses.members.update-last-access-group');
});

Route::middleware(['auth:api', 'verified'])->group(function () {
    Route::get('/attendances/{attendance}/details', [AttendanceDetailController::class, 'index'])->name('attendances.details.index');
    Route::post('/attendances/{attendance}/details', [AttendanceDetailController::class, 'store'])->name('attendances.details.store');
    Route::patch('/attendances/details/{detail}', [AttendanceDetailController::class, 'update'])->name('attendance.details.update');
    
    
    // Get all members attendance details for a group attendance
    Route::get('/attendances/{attendance}/group-members-details', [AttendanceDetailController::class, 'getGroupMembersAttendanceDetails'])->name('attendances.group.members.details');
    
    // Basic attendance info route
    Route::get('/attendances/{attendance}/basic-info', [AttendanceDetailController::class, 'index'])->name('attendances.basic.info');

    Route::get('/attendances/{attendance}/member/{member}/join-status', [AttendanceDetailController::class, 'getMemberJoinStatus'])->name('attendances.member.join.status');
    
});

Route::middleware(['auth:api', 'verified'])->group(function () {
    Route::post('/courses/posts/comments/{comment}/like', [CoursePostCommentReactionController::class, 'toggleLikeComment'])->name('toggle.like.course_post_comment');
    Route::post('/courses/posts/comments/{comment}/dislike', [CoursePostCommentReactionController::class, 'toggleDislikeComment'])->name('toggle.dislike.course_post_comment');
    
    Route::post('/courses/posts/images/comments/{comment}/like', [CoursePostImageCommentReactionController::class, 'toggleLikeComment'])->name('toggle.like.course_post_image_comment');
    Route::post('/courses/posts/images/comments/{comment}/dislike', [CoursePostImageCommentReactionController::class, 'toggleDislikeComment'])->name('toggle.dislike.course_post_image_comment');
});

Route::middleware(['auth:api', 'verified'])->prefix('/courses/{course}/posts')->group(function () {

    Route::get('/', [CoursePostController::class, 'index'])->name('course.posts.index');
    Route::post('/', [CoursePostController::class, 'store'])->name('course.posts.store');
    Route::get('/{course_post}', [CoursePostController::class, 'show'])->name('course.posts.show');
    Route::get('/{course_post}/edit', [CoursePostController::class, 'edit'])->name('course.posts.edit');
    Route::patch('/{course_post}', [CoursePostController::class, 'update'])->name('course.posts.update');
    Route::delete('/{course_post}', [CoursePostController::class, 'destroy'])->name('course.posts.destroy');

    Route::post('/{course_post}/like', [CoursePostReactionController::class, 'toggleLike'])->name('toggle.like.course_post');
    Route::post('/{course_post}/dislike', [CoursePostReactionController::class, 'toggleDislike'])->name('toggle.dislike.course_post');
    
    // Share routes
    Route::post('/{course_post}/share', [CoursePostShareController::class, 'share'])->name('course.post.share');
    Route::delete('/{course_post}/unshare', [CoursePostShareController::class, 'unshare'])->name('course.post.unshare');
    Route::get('/{course_post}/shares', [CoursePostShareController::class, 'shares'])->name('course.post.shares.list');
    
    Route::get('/{course_post}/comments', [CoursePostCommentController::class, 'index'])->name('course.post.comments.index');
    Route::post('/{course_post}/comments', [CoursePostCommentController::class, 'store'])->name('course.post.comments.store');
    Route::delete('/{course_post}/comments/{comment}', [CoursePostCommentController::class, 'destroy'])->name('course.post.comments.destroy');

    Route::post('/{course_post}/images/{image}/like', [CoursePostImageController::class, 'toggleLike'])->name('course.post.images.toggle-like');
    Route::post('/{course_post}/images/{image}/dislike', [CoursePostImageController::class, 'toggleDislike'])->name('course.post.images.toggle-dislike');
    
    Route::get('/{course_post}/images/{image}/comments', [CoursePostImageCommentController::class, 'index'])->name('course.post.images.comment.index');
    Route::post('/{course_post}/images/{image}/comments', [CoursePostImageCommentController::class, 'store'])->name('course.post.images.comment.store');
    Route::delete('/{course_post}/images/{image}/comments/{comment}', [CoursePostImageCommentController::class, 'destroy'])->name('course.post.images.comment.destroy');
    Route::put('/{course_post}/images/{image}/comments/{comment}', [CoursePostImageCommentController::class, 'update'])->name('course.post.images.comment.update');
    
    Route::post('/{course_post}/images/{image}/comments/{comment}/like', [CoursePostImageCommentReactionController::class, 'toggleLike'])->name('course.post.images.comment.toggle-like');
    Route::post('/{course_post}/images/{image}/comments/{comment}/dislike', [CoursePostImageCommentReactionController::class, 'toggleDislike'])->name('course.post.images.comment.toggle-dislike');

    // Route::get('/{course_post}/images', [PostImageController::class, 'index'])->name('course.post.images.index');
    // Route::post('/{course_post}/images', [PostImageController::class, 'store'])->name('course.post.images.store');
    // Route::delete('/{course_post}/images/{image}', [PostImageController::class, 'destroy'])->name('course.post.images.destroy');
    // Route::post('/{course_post}/images/{image}/set-as-cover', [PostImageController::class, 'setAsCover'])->name('course.post.images.setAsCover');
    
    // Route::post('/postimage/{post_image}/comments', [PostImageController::class, 'storeComment'])->name('course.post.image.comments.store');

});

// Course cover and logo update routes
Route::middleware(['auth:api', 'verified'])->group(function () {
    Route::post('/courses/{course}/cover', [CourseController::class, 'updateCover'])->name('course.cover.update');
    Route::post('/courses/{course}/logo', [CourseController::class, 'updateLogo'])->name('course.logo.update');
    Route::patch('/courses/{course}/header', [CourseController::class, 'updateHeader'])->name('course.header.update');
    Route::patch('/courses/{course}/subheader', [CourseController::class, 'updateSubheader'])->name('course.subheader.update');
    Route::get('/courses/{course}/profile', [CourseController::class, 'profile'])->name('course.profile');
});

