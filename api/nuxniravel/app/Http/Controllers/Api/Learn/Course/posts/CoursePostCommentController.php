<?php

namespace App\Http\Controllers\Api\Learn\Course\posts;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePost;
use Illuminate\Http\Request;
use App\Models\CoursePostComment;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Learn\Course\posts\CoursePostCommentResource;

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
                ]
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
     * @param  \App\Http\Requests\StoreCommentRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Course $course, CoursePost $course_post, Request $request)
    {
        try {

            $validatedData = $request->validate([
                'content' => 'nullable|string',
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            $newComment = $course_post->post_comments()->create([
                'user_id' => auth()->id(),
                'content' => $validatedData['content']
            ]);

            if($request->hasFile('images')) {
                $post_comment_images = $request->file('images');
                foreach ($post_comment_images as $image) {
                    $fileName = $course_post->id . uniqid() . '.' . $image->getClientOriginalExtension();
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
                'message' => 'Error: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Remove the specified comment from storage.
     *
     * @param  \App\Models\CoursePostComment  $comment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Course $course, CoursePost $course_post, CoursePostComment $comment)
    {
        try {
            
            $post_comment_images = $comment->postCommentImages;
            foreach ($post_comment_images as $image) {
                Storage::disk('public')->delete('images/courses/posts/comments/' . $image->filename);
                $image->delete();
            }

            $comment->delete();

            $course_post->decrement('comments', 1);

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 404);
        }
    }
}
