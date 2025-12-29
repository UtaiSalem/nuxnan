<?php

namespace App\Http\Controllers\Api\Play;

use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Http\Request;
use App\Models\PostCommentImage;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Play\PostCommentResource;

class PostCommentController extends \App\Http\Controllers\Controller
{
    public function index(Post $post, Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            
            // Get top-level comments only (exclude replies)
            // Replies have parent_post_comment_id set
            $postComments = PostComment::where('post_id', $post->id)
                ->whereNull('parent_post_comment_id')
                ->with(['user', 'postCommentImages'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
            
            $postCommentResource = PostCommentResource::collection($postComments);

            return response()->json([
                'success' => true,
                'comments' => $postCommentResource,
                'pagination' => [
                    'current_page' => $postComments->currentPage(),
                    'last_page' => $postComments->lastPage(),
                    'per_page' => $postComments->perPage(),
                    'total' => $postComments->total(),
                    'has_more' => $postComments->hasMorePages(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading comments',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        }
    }

    /**
     * Store a newly created comment in storage.
     *
     * @param  \App\Http\Requests\StoreCommentRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Post $post, Request $request)
    {
        $validatedData = $request->validate([
            'content' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $newComment = $post->postComments()->create([
            'user_id' => auth()->id(),
            'content' => $validatedData['content']
        ]);

        if($request->hasFile('images')) {
            $post_comment_images = $request->file('images');
            foreach ($post_comment_images as $image) {
                $fileName = $post->id . uniqid() . '.' . $image->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/posts/comments', $image, $fileName);
                // PostCommentImage::create([
                //     'post_id' => $post->id,
                //     'post_comment_id' => $newComment->id,
                //     'filename' => $fileName,
                // ]);
                $newComment->postCommentImages()->create([
                    'post_id' => $post->id,
                    'filename' => $fileName,
                ]);
            }
        }

        $post->increment('comments', 1);

        return response()->json([
            'success' => true,
            'comment' => new PostCommentResource(PostComment::find($newComment->id)),
        ]);
    }

    /**
     * Update the specified comment in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $comment = Comment::findOrFail($id);
        $comment->content = $request->input('content');
        // Update other attributes for the comment as needed

        $comment->save();

        return response()->json([
            'message' => 'Comment updated successfully',
            'comment' => $comment
        ]);
    }

    /**
     * Remove the specified comment from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Post $post, PostComment $comment)
    {
        // $comment = Comment::findOrFail($id);
        $comment_images = $comment->postCommentImages;
        if ($comment_images->count() > 0) {
            foreach ($comment_images as $image) {
                Storage::disk('public')->delete('images/posts/comments/' . $image->filename);
                $image->delete();
            }
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully'
        ]);
    }

    /**
     * Store a reply to a comment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PostComment  $comment
     * @return \Illuminate\Http\Response
     */
    public function storeReply(PostComment $comment, Request $request)
    {
        $validatedData = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        // Check if user has enough points (12 points required for reply)
        $user = auth()->user();
        if ($user->pp < 12) {
            return response()->json([
                'success' => false,
                'message' => 'คุณมีแต้มไม่เพียงพอในการตอบกลับ (ต้องการ 12 แต้ม)',
            ], 400);
        }

        // Create reply
        $reply = PostComment::create([
            'post_id' => $comment->post_id,
            'user_id' => $user->id,
            'content' => $validatedData['content'],
            'parent_post_comment_id' => $comment->id,
            'likes' => 0,
            'dislikes' => 0,
            'replies' => 0,
        ]);

        // Deduct points from replier (12 points)
        $user->decrement('pp', 12);

        // Give points to parent comment author (6 points)
        if ($comment->user_id !== $user->id) {
            $comment->user->increment('pp', 6);
        }

        // Give points to super admin (6 points)
        $superAdmin = \App\Models\User::find(1);
        if ($superAdmin) {
            $superAdmin->increment('pp', 6);
        }

        // Increment reply count on parent comment
        $comment->increment('replies');

        // Load the reply with user relationship
        $reply->load('user');

        return response()->json([
            'success' => true,
            'reply' => new PostCommentResource($reply),
            'message' => 'ตอบกลับสำเร็จ',
        ]);
    }

    /**
     * Get replies for a comment.
     *
     * @param  \App\Models\PostComment  $comment
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getReplies(PostComment $comment, Request $request)
    {
        try {
            $perPage = $request->input('per_page', 5);
            
            $replies = PostComment::where('parent_post_comment_id', $comment->id)
                ->with(['user', 'postCommentImages'])
                ->orderBy('created_at', 'asc')
                ->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'replies' => PostCommentResource::collection($replies),
                'pagination' => [
                    'current_page' => $replies->currentPage(),
                    'last_page' => $replies->lastPage(),
                    'per_page' => $replies->perPage(),
                    'total' => $replies->total(),
                    'has_more' => $replies->hasMorePages(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading replies',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
