<?php

namespace App\Http\Controllers\Api\Learn\Course\posts;

use App\Http\Controllers\Controller;

use App\Models\User;
use App\Models\Course;
use App\Models\CoursePost;
use Illuminate\Http\Request;
use App\Models\CoursePostImage;
use App\Models\CoursePostImageComment;
use App\Http\Resources\Learn\Course\posts\CoursePostImageCommentResource;

class CoursePostImageCommentController extends Controller
{
    public $plearndAdmin = null;

    public function __construct()
    {
        $this->plearndAdmin = User::find(1);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Course $course, CoursePost $course_post, CoursePostImage $image)
    {
        $comments = $image->image_comments()->with('user')->latest()->paginate();

        return response()->json([
            'success' => true,
            'comments' => CoursePostImageCommentResource::collection($comments),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Course $course, CoursePost $course_post, CoursePostImage $image, Request $request)
    {
        try {
            $validatedData = $request->validate([
                'content' => 'nullable|string',
            ]);

            $newComment = $image->image_comments()->create([
                'user_id' => auth()->id(),
                'content' => $validatedData['content']
            ]);

            $image->increment('comments', 1);

            return response()->json([
                'success' => true,
                'comment' => new CoursePostImageCommentResource($newComment->fresh()->load('user')),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified comment in storage.
     */
    public function update(Request $request, Course $course, CoursePost $course_post, CoursePostImage $image, CoursePostImageComment $comment)
    {
        try {
            // Check authorization - only comment owner can update
            if ($comment->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์แก้ไขความคิดเห็นนี้'
                ], 403);
            }

            $validatedData = $request->validate([
                'content' => 'required|string',
            ]);

            $comment->update([
                'content' => $validatedData['content']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'แก้ไขความคิดเห็นสำเร็จ',
                'comment' => new CoursePostImageCommentResource($comment->fresh()->load('user'))
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified comment from storage.
     */
    public function destroy(Course $course, CoursePost $course_post, CoursePostImage $image, CoursePostImageComment $comment)
    {
        try {
            // Check authorization - comment owner or post owner can delete
            if ($comment->user_id !== auth()->id() && $course_post->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์ลบความคิดเห็นนี้'
                ], 403);
            }

            // Detach likes and dislikes
            $comment->liked()->detach();
            $comment->disliked()->detach();

            $comment->delete();

            // Decrement comments count
            $image->decrement('comments', 1);

            return response()->json([
                'success' => true,
                'message' => 'ลบความคิดเห็นสำเร็จ',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
