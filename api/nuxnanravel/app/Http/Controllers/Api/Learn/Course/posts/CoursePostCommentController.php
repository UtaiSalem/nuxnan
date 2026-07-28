<?php

namespace App\Http\Controllers\Api\Learn\Course\posts;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\Learn\Course\posts\CoursePostCommentResource;
use App\Models\Course;
use App\Models\CoursePost;
use App\Models\CoursePostComment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class CoursePostCommentController extends Controller
{
    public function index(Course $course, CoursePost $course_post, Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);

            $postComments = CoursePostComment::where('course_post_id', $course_post->id)
                ->with(['user', 'postCommentImages'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'comments' => CoursePostCommentResource::collection($postComments),
                'pagination' => [
                    'current_page' => $postComments->currentPage(),
                    'last_page' => $postComments->lastPage(),
                    'per_page' => $postComments->perPage(),
                    'total' => $postComments->total(),
                    'has_more' => $postComments->hasMorePages(),
                ],
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading comments',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created comment in storage.
     *
     * @param  StoreCommentRequest  $request
     * @return Response
     */
    public function store(Course $course, CoursePost $course_post, Request $request)
    {
        try {

            $validatedData = $request->validate([
                'content' => 'nullable|string',
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $newComment = $course_post->post_comments()->create([
                'user_id' => auth()->id(),
                'content' => $validatedData['content'],
            ]);

            if ($request->hasFile('images')) {
                $post_comment_images = $request->file('images');
                foreach ($post_comment_images as $image) {
                    $fileName = $course_post->id.uniqid().'.'.$image->getClientOriginalExtension();
                    Storage::disk('public')->putFileAs('images/courses/posts/comments', $image, $fileName);

                    $newComment->postCommentImages()->create([
                        'course_post_id' => $course_post->id,
                        'filename' => $fileName,
                    ]);
                }
            }

            $course_post->increment('comments', 1);

            return response()->json([
                'success' => true,
                'comment' => new CoursePostCommentResource($newComment),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update the specified comment in storage.
     *
     * @return Response
     */
    public function update(Request $request, Course $course, CoursePost $course_post, CoursePostComment $comment)
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
                'comment' => new CoursePostCommentResource($comment->fresh()),
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
     *
     * @return Response
     */
    public function destroy(Course $course, CoursePost $course_post, CoursePostComment $comment)
    {
        try {
            // Check authorization - comment owner or post owner can delete
            if ($comment->user_id !== auth()->id() && $course_post->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์ลบความคิดเห็นนี้',
                ], 403);
            }

            // Detach likes and dislikes
            $comment->comment_likes()->detach();
            $comment->comment_dislikes()->detach();

            // Delete images
            $post_comment_images = $comment->postCommentImages;
            foreach ($post_comment_images as $image) {
                Storage::disk('public')->delete('images/courses/posts/comments/'.$image->filename);
                $image->delete();
            }

            // Delete replies if any
            $comment->replies()->each(function ($reply) {
                $reply->comment_likes()->detach();
                $reply->comment_dislikes()->detach();
                $reply->postCommentImages()->each(function ($image) {
                    Storage::disk('public')->delete('images/courses/posts/comments/'.$image->filename);
                    $image->delete();
                });
                $reply->delete();
            });

            $comment->delete();

            $course_post->decrement('comments', 1);

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
