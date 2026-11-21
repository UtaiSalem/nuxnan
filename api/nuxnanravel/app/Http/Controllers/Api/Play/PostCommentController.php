<?php

namespace App\Http\Controllers\Api\Play;

use App\Enums\UsageEventType;
use App\Exceptions\AccountEconomyRestrictedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\Play\PostCommentResource;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostCommentImage;
use App\Models\User;
use App\Services\FeedPointsService;
use App\Services\UsageEventService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class PostCommentController extends Controller
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
                ],
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
     * @param  StoreCommentRequest  $request
     * @return Response
     */
    public function store(Post $post, Request $request)
    {
        $validatedData = $request->validate([
            'content' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = auth()->user();
        $requiredPoints = (int) config('economy.feed_costs.comment');
        $authorShare = (int) config('economy.feed_costs.comment_author_share');
        $superAdmin = User::find(1);

        try {
            $charge = app(FeedPointsService::class)->charge(
                $user, $requiredPoints, 'feed_comment', $post->id, 'แสดงความคิดเห็นในโพสต์',
                [
                    ['user' => $post->user_id === $user->id ? $superAdmin : $post->user, 'amount' => $authorShare, 'sourceType' => $post->user_id === $user->id ? 'feed_platform_fee' : 'feed_comment_reward'],
                    ['user' => $superAdmin, 'amount' => $requiredPoints - $authorShare, 'sourceType' => 'feed_platform_fee'],
                ],
                function () use ($post, $request, $validatedData) {
                    $newComment = $post->postComments()->create([
                        'user_id' => auth()->id(),
                        'content' => $validatedData['content'],
                    ]);

                    if ($request->hasFile('images')) {
                        $post_comment_images = $request->file('images');
                        foreach ($post_comment_images as $image) {
                            $fileName = $post->id.uniqid().'.'.$image->getClientOriginalExtension();
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

                    return ['comment' => $newComment];
                }
            );
        } catch (AccountEconomyRestrictedException $e) {
            return response()->json(['success' => false, 'code' => 'ACCOUNT_RESTRICTED', 'message' => $e->getMessage()], 403);
        }

        if (! $charge['ok']) {
            return response()->json([
                'success' => false,
                'code' => 'INSUFFICIENT_PP',
                'message' => "คุณมีแต้มไม่เพียงพอในการแสดงความคิดเห็น (ต้องการ {$requiredPoints} แต้ม)",
                'required_pp' => $requiredPoints,
                'current_pp' => $charge['current_pp'],
            ], 422);
        }
        $newComment = $charge['comment'];

        // Fire gamification event
        UsageEventService::fire(auth()->user(), UsageEventType::COMMENT_CREATE->value, 'post', $post->id);

        return response()->json([
            'success' => true,
            'comment' => new PostCommentResource(PostComment::find($newComment->id)),
            'user_pp' => $charge['user_pp'],
        ]);
    }

    /**
     * Update the specified comment in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, Post $post, PostComment $comment)
    {
        if ($comment->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validatedData = $request->validate([
            'content' => 'required|string',
        ]);

        $comment->update([
            'content' => $validatedData['content'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comment updated successfully',
            'comment' => new PostCommentResource($comment),
        ]);
    }

    /**
     * Remove the specified comment from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy(Post $post, PostComment $comment)
    {
        if ($comment->user_id !== auth()->id() && $post->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Clean up likes and dislikes
        $comment->likedPostComment()->detach();
        $comment->dislikedPostComment()->detach();

        // Delete images
        $comment_images = $comment->postCommentImages;
        if ($comment_images->count() > 0) {
            foreach ($comment_images as $image) {
                Storage::disk('public')->delete('images/posts/comments/'.$image->filename);
                $image->delete();
            }
        }

        // Delete replies
        $comment->replies()->each(function ($reply) {
            $reply->likedPostComment()->detach();
            $reply->dislikedPostComment()->detach();
            $reply->postCommentImages()->each(function ($image) {
                Storage::disk('public')->delete('images/posts/comments/'.$image->filename);
                $image->delete();
            });
            $reply->delete();
        });

        $comment->delete();

        // Decrement comments count
        $post->decrement('comments');

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully',
        ]);
    }

    /**
     * Store a reply to a comment.
     *
     * @return Response
     */
    public function storeReply(PostComment $comment, Request $request)
    {
        $validatedData = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $user = auth()->user();
        $requiredPoints = (int) config('economy.feed_costs.reply');
        $authorShare = (int) config('economy.feed_costs.reply_author_share');
        $superAdmin = User::find(1);

        try {
            $charge = app(FeedPointsService::class)->charge(
                $user, $requiredPoints, 'feed_reply', $comment->id, 'ตอบกลับความคิดเห็น',
                [
                    ['user' => $comment->user_id === $user->id ? $superAdmin : $comment->user, 'amount' => $authorShare, 'sourceType' => $comment->user_id === $user->id ? 'feed_platform_fee' : 'feed_reply_reward'],
                    ['user' => $superAdmin, 'amount' => $requiredPoints - $authorShare, 'sourceType' => 'feed_platform_fee'],
                ],
                function () use ($comment, $user, $validatedData) {
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

                    // Increment reply count on parent comment
                    $comment->increment('replies');

                    return ['reply' => $reply];
                }
            );
        } catch (AccountEconomyRestrictedException $e) {
            return response()->json(['success' => false, 'code' => 'ACCOUNT_RESTRICTED', 'message' => $e->getMessage()], 403);
        }

        if (! $charge['ok']) {
            return response()->json([
                'success' => false,
                'code' => 'INSUFFICIENT_PP',
                'message' => "คุณมีแต้มไม่เพียงพอในการตอบกลับ (ต้องการ {$requiredPoints} แต้ม)",
                'required_pp' => $requiredPoints,
                'current_pp' => $charge['current_pp'],
            ], 422);
        }
        $reply = $charge['reply'];

        // Fire gamification event
        UsageEventService::fire($user, UsageEventType::COMMENT_CREATE->value, 'post', $comment->post_id);

        // Load the reply with user relationship
        $reply->load('user');

        return response()->json([
            'success' => true,
            'reply' => new PostCommentResource($reply),
            'user_pp' => $charge['user_pp'],
            'message' => 'ตอบกลับสำเร็จ',
        ]);
    }

    /**
     * Get replies for a comment.
     *
     * @return Response
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
                ],
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
