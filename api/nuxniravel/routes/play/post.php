<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Play\PostController;
use App\Http\Controllers\Api\Play\PostReactionController;
use App\Http\Controllers\Api\Play\PostCommentController;
use App\Http\Controllers\Api\Play\PostImageController;
use App\Http\Controllers\Api\Play\PostImageReactionController;
use App\Http\Controllers\Api\Play\PostImageCommentReactionController;
use App\Http\Controllers\Api\Play\PostCommentReactionController;
use App\Http\Controllers\PostShareController;


Route::middleware(['auth:api'])->group(function () {
    // Core Post CRUD
    Route::resource('/posts', PostController::class);
    Route::get('/posts/{post}/getPostActivity', [PostController::class, 'getPostActivity'])->name('post.activity');
    
    // Post Creation Options (feelings, backgrounds, activity types)
    Route::get('/posts-options', [PostController::class, 'create'])->name('post.options');
    Route::get('/posts-feelings', [PostController::class, 'getFeelings'])->name('post.feelings');
    Route::get('/posts-activity-types', [PostController::class, 'getActivityTypes'])->name('post.activity-types');
    Route::get('/posts-backgrounds', [PostController::class, 'getBackgrounds'])->name('post.backgrounds');
    
    // Post Pin & Comments Toggle
    Route::post('/posts/{post}/toggle-pin', [PostController::class, 'togglePin'])->name('post.toggle-pin');
    Route::post('/posts/{post}/toggle-comments', [PostController::class, 'toggleComments'])->name('post.toggle-comments');
    
    // Post Scheduling
    Route::post('/posts/{post}/schedule', [PostController::class, 'schedule'])->name('post.schedule');
    Route::get('/posts-scheduled', [PostController::class, 'scheduledPosts'])->name('post.scheduled');
    Route::get('/posts-pinned', [PostController::class, 'pinnedPosts'])->name('post.pinned');
    
    // Location-based Post Search
    Route::get('/posts-near-location', [PostController::class, 'searchByLocation'])->name('post.near-location');
    
    // Tagged/Mentioned Posts
    Route::get('/posts-tagged', [PostController::class, 'getTaggedPosts'])->name('post.tagged');
    Route::post('/posts/{post}/approve-tag', [PostController::class, 'approveTag'])->name('post.approve-tag');
    Route::delete('/posts/{post}/remove-tag', [PostController::class, 'removeTag'])->name('post.remove-tag');

    // Reactions
    Route::post('/posts/{post}/like', [PostReactionController::class, 'toggleLikePost'])->name('toggle.like.post');
    Route::post('/posts/{post}/dislike', [PostReactionController::class, 'toggleDislikePost'])->name('toggle.dislike.post');
    
    // Share routes
    Route::post('/posts/{post}/share', [PostShareController::class, 'share'])->name('post.share');
    Route::delete('/posts/{post}/unshare', [PostShareController::class, 'unshare'])->name('post.unshare');
    Route::get('/posts/{post}/shares', [PostShareController::class, 'shares'])->name('post.shares.list');
    
    // Comments
    Route::get('/posts/{post}/comments', [PostCommentController::class, 'index'])->name('post.comments.index');
    Route::post('/posts/{post}/comments', [PostCommentController::class, 'store'])->name('post.comments.store');
    Route::delete('/posts/{post}/comments/{comment}', [PostCommentController::class, 'destroy'])->name('post.comments.destroy');

    Route::post('/post_comments/{post_comment}/like', [PostCommentReactionController::class, 'toggleLikePostComment'])->name('toggle.like.post.comment');
    Route::post('/post_comments/{post_comment}/dislike', [PostCommentReactionController::class, 'toggleDislikePostComment'])->name('toggle.dislike.post.comment');

    // Comment Replies
    Route::post('/post_comments/{comment}/replies', [PostCommentController::class, 'storeReply'])->name('post.comments.reply.store');
    Route::get('/post_comments/{comment}/replies', [PostCommentController::class, 'getReplies'])->name('post.comments.replies.index');

    // Images
    Route::get('/posts/{post}/images', [PostImageController::class, 'index'])->name('post.images.index');
    Route::post('/posts/{post}/images', [PostImageController::class, 'store'])->name('post.images.store');
    Route::delete('/posts/{post}/images/{image}', [PostImageController::class, 'destroy'])->name('post.images.destroy');
    
    Route::post('/postimage/{post_image}/comments', [PostImageController::class, 'storeComment'])->name('post.image.comments.store');
    Route::get('/postimage/{post_image}/comments', [PostImageController::class, 'getComments'])->name('post.image.comments.getComments');

    Route::post('/post_images/{post_image}/like', [PostImageReactionController::class, 'toggleLikePostImage'])->name('toggle.like.post.image');
    Route::post('/post_images/{post_image}/dislike', [PostImageReactionController::class, 'toggleDislikePostImage'])->name('toggle.dislike.post.image');

    Route::delete('/post_image_comments/{post_image_comment}', [PostImageController::class, 'destroyComment'])->name('post.image.comments.destroy');

    Route::post('/post_image_comments/{post_image_comment}/like', [PostImageCommentReactionController::class, 'toggleLikePostImageComment'])->name('toggle.like.post.image.comment');
    Route::post('/post_image_comments/{post_image_comment}/dislike', [PostImageCommentReactionController::class, 'toggleDislikePostImageComment'])->name('toggle.dislike.post.image.comment');

});
