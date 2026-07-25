<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Academy\AcademyPostCommentResource;
use App\Models\Academy;
use App\Models\AcademyPost;
use App\Models\AcademyPostComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AcademyPostCommentController extends Controller
{
    /**
     * Display a listing of the comments for an academy post.
     */
    public function index(Academy $academy, AcademyPost $academy_post, Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);

            $comments = AcademyPostComment::where('academy_post_id', $academy_post->id)
                ->whereNull('parent_comment_id')
                ->with(['user', 'images', 'replies.user'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'comments' => AcademyPostCommentResource::collection($comments),
                'pagination' => [
                    'current_page' => $comments->currentPage(),
                    'last_page' => $comments->lastPage(),
                    'per_page' => $comments->perPage(),
                    'total' => $comments->total(),
                    'has_more' => $comments->hasMorePages(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading comments: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created comment in storage.
     */
    public function store(Request $request, Academy $academy, AcademyPost $academy_post)
    {
        try {
            $validatedData = $request->validate([
                'content' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'parent_comment_id' => 'nullable|exists:academy_post_comments,id',
            ]);

            $newComment = $academy_post->comments()->create([
                'user_id' => auth()->id(),
                'content' => $validatedData['content'],
                'parent_comment_id' => $validatedData['parent_comment_id'] ?? null,
            ]);

            if ($request->hasFile('images')) {
                $commentImages = $request->file('images');
                foreach ($commentImages as $image) {
                    $fileName = $academy_post->id.uniqid().'.'.$image->getClientOriginalExtension();
                    Storage::disk('public')->putFileAs('images/academies/posts/comments', $image, $fileName);
                    $newComment->images()->create([
                        'filename' => $fileName,
                    ]);
                }
            }

            // Increment comments count on the post
            $academy_post->increment('comments');

            return response()->json([
                'success' => true,
                'comment' => new AcademyPostCommentResource($newComment->fresh()->load(['user', 'images'])),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified comment in storage.
     */
    public function update(Request $request, Academy $academy, AcademyPost $academy_post, AcademyPostComment $comment)
    {
        try {
            // Check authorization - only comment owner can update
            if ($comment->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์แก้ไขความคิดเห็นนี้',
                ], 403);
            }

            $validatedData = $request->validate([
                'content' => 'required|string',
            ]);

            $comment->update([
                'content' => $validatedData['content'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'แก้ไขความคิดเห็นสำเร็จ',
                'comment' => new AcademyPostCommentResource($comment->fresh()->load(['user', 'images'])),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified comment from storage.
     */
    public function destroy(Academy $academy, AcademyPost $academy_post, AcademyPostComment $comment)
    {
        try {
            // Check authorization - comment owner or post owner can delete
            if ($comment->user_id !== auth()->id() && $academy_post->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์ลบความคิดเห็นนี้',
                ], 403);
            }

            // Detach likes and dislikes
            $comment->likes()->detach();
            $comment->dislikes()->detach();

            // Delete images
            $images = $comment->images;
            if ($images && $images->count() > 0) {
                foreach ($images as $image) {
                    Storage::disk('public')->delete('images/academies/posts/comments/'.$image->filename);
                    $image->delete();
                }
            }

            // Delete replies recursively
            $comment->replies()->each(function ($reply) {
                $reply->likes()->detach();
                $reply->dislikes()->detach();
                $reply->images()->each(function ($image) {
                    Storage::disk('public')->delete('images/academies/posts/comments/'.$image->filename);
                    $image->delete();
                });
                $reply->delete();
            });

            $comment->delete();

            // Decrement comments count
            $academy_post->decrement('comments');

            return response()->json([
                'success' => true,
                'message' => 'ลบความคิดเห็นสำเร็จ',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }
}
