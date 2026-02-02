<?php

namespace App\Http\Controllers\Api\Play;

use App\Models\Post;
use App\Models\PostImage;
use Illuminate\Http\Request;
use App\Models\PostImageComment;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Play\PostImageCommentResource;

class PostImageController extends \App\Http\Controllers\Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post, PostImage $image)
    {
        Storage::disk('public')->delete('images/posts/'. $image->filename);
        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Image deleted'
        ], 200);
    }

    // store comment
    public function storeComment(Request $request, PostImage $post_image)
    {
        $request->validate([
            'content' => 'required|string'
        ]);

        $image_comment = $post_image->image_comments()->create([
            'content' => $request->content,
            'user_id' => auth()->id()
        ]);

        if ($image_comment) {
            // $post_image->post->activity()->create([
            //     'user_id' => auth()->id(),
            //     'content' => 'commented on your post image',
            // ]);
            $post_image->increment('comments');
        }

        return response()->json([
            'success' => true,
            'comment' => new PostImageCommentResource($image_comment),
        ], 200);
    }

    //get comments
    public function getComments(PostImage $post_image)
    {
        return response()->json([
            'success' => true,
            'comments' => PostImageCommentResource::collection($post_image->image_comments()->latest()->get()),
        ], 200);
    }

    /**
     * Update a comment on a post image
     */
    public function updateComment(Request $request, PostImageComment $post_image_comment)
    {
        // Check authorization - only comment owner can update
        if ($post_image_comment->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์แก้ไขความคิดเห็นนี้'
            ], 403);
        }

        $request->validate([
            'content' => 'required|string'
        ]);

        $post_image_comment->update([
            'content' => $request->content
        ]);

        return response()->json([
            'success' => true,
            'message' => 'แก้ไขความคิดเห็นสำเร็จ',
            'comment' => new PostImageCommentResource($post_image_comment->fresh()),
        ], 200);
    }

    /**
     * Delete a comment on a post image
     */
    public function destroyComment(PostImageComment $post_image_comment)
    {
        // Check authorization - comment owner or post image owner can delete
        $postImage = $post_image_comment->postImage;
        $postOwnerId = $postImage?->post?->user_id ?? null;
        
        if ($post_image_comment->user_id !== auth()->id() && $postOwnerId !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์ลบความคิดเห็นนี้'
            ], 403);
        }

        // Detach likes and dislikes
        $post_image_comment->liked()->detach();
        $post_image_comment->disliked()->detach();
        
        // Store reference to post image before delete
        $postImageRef = $post_image_comment->postImage;
        
        $post_image_comment->delete();
        
        // Decrement comments count
        if ($postImageRef) {
            $postImageRef->decrement('comments');
        }

        return response()->json([
            'success' => true,
            'message' => 'ลบความคิดเห็นสำเร็จ'
        ], 200);
    }
}
