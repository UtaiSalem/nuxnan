<?php

namespace App\Http\Controllers;

use App\Models\Share;
use App\Models\ShareComment;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShareCommentController extends Controller
{
    /**
     * Add a comment to a share
     */
    public function store(Request $request, $shareId)
    {
        $request->validate([
            'content' => 'required|string|max:1000'
        ]);

        $share = Share::findOrFail($shareId);
        $user = Auth::user();

        DB::beginTransaction();
        try {
            // Create comment (no points required)
            $comment = ShareComment::create([
                'share_id' => $share->id,
                'user_id' => $user->id,
                'content' => $request->content
            ]);

            // Increment share comment count
            $share->increment('comments');
            
            DB::commit();
            
            // Refresh comment with user relationship
            $comment = ShareComment::with('user')->find($comment->id);
            
            return response()->json([
                'success' => true,
                'message' => 'แสดงความคิดเห็นสำเร็จ',
                'comment' => [
                    'id' => $comment->id,
                    'share_id' => $comment->share_id,
                    'user' => new UserResource($comment->user),
                    'content' => $comment->content,
                    'likes' => $comment->likes ?? 0,
                    'dislikes' => $comment->dislikes ?? 0,
                    'is_liked_by_auth' => $comment->is_liked_by_auth ?? false,
                    'is_disliked_by_auth' => $comment->is_disliked_by_auth ?? false,
                    'diff_humans_created_at' => $comment->diff_humans_created_at,
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->updated_at,
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการแสดงความคิดเห็น'
            ], 500);
        }
    }

    /**
     * Get comments for a share (paginated)
     */
    public function index(Request $request, $shareId)
    {
        try {
            $share = Share::findOrFail($shareId);
            
            $perPage = $request->input('per_page', 10);
            
            $comments = ShareComment::where('share_id', $shareId)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            // Format comments เหมือน ShareResource getShareComments()
            $formattedComments = $comments->getCollection()->map(function($comment) {
                return [
                    'id' => $comment->id,
                    'user' => new UserResource($comment->user),
                    'content' => $comment->content,
                    'likes' => $comment->likes,
                    'dislikes' => $comment->dislikes,
                    'is_liked_by_auth' => $comment->is_liked_by_auth,
                    'is_disliked_by_auth' => $comment->is_disliked_by_auth,
                    'diff_humans_created_at' => $comment->diff_humans_created_at,
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'comments' => $formattedComments,
                'pagination' => [
                    'current_page' => $comments->currentPage(),
                    'last_page' => $comments->lastPage(),
                    'per_page' => $comments->perPage(),
                    'total' => $comments->total(),
                    'has_more' => $comments->hasMorePages()
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('ShareCommentController@index error:', [
                'share_id' => $shareId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load comments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a comment
     */
    public function destroy($commentId)
    {
        $comment = ShareComment::findOrFail($commentId);
        
        // Check if user owns the comment
        if ($comment->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์ลบความคิดเห็นนี้'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $share = $comment->share;
            
            // Delete comment
            $comment->delete();
            
            // Decrement share comment count
            $share->decrement('comments');
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'ลบความคิดเห็นสำเร็จ'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการลบความคิดเห็น'
            ], 500);
        }
    }

    /**
     * Like a share comment
     */
    public function like($commentId)
    {
        $comment = ShareComment::findOrFail($commentId);
        $user = Auth::user();
        $user->refresh(); // Get fresh points from database

        // Can't like own comment
        if ($comment->user_id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่สามารถกดถูกใจความคิดเห็นของตัวเองได้'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Check if already liked
            $existingLike = DB::table('share_comment_likes')
                ->where('share_comment_id', $comment->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existingLike) {
                // Unlike
                DB::table('share_comment_likes')
                    ->where('share_comment_id', $comment->id)
                    ->where('user_id', $user->id)
                    ->delete();
                
                $comment->decrement('likes');
                
                // Return points
                $user->increment('pp', 24);
                
                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'message' => 'ยกเลิกการกดถูกใจแล้ว',
                    'action' => 'unliked',
                    'likes' => $comment->likes,
                    'pp' => $user->pp
                ]);
            }

            // Remove dislike if exists
            $existingDislike = DB::table('share_comment_dislikes')
                ->where('share_comment_id', $comment->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existingDislike) {
                DB::table('share_comment_dislikes')
                    ->where('share_comment_id', $comment->id)
                    ->where('user_id', $user->id)
                    ->delete();
                
                $comment->decrement('dislikes');
                
                // Return points from dislike
                $user->increment('pp', 12);
            }

            // Refresh user to get latest points
            $user->refresh();

            // Check if user has enough points
            if ($user->pp < 24) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'คุณมีแต้มไม่เพียงพอ (ต้องการ 24 แต้ม)'
                ], 400);
            }

            // Add like
            DB::table('share_comment_likes')->insert([
                'share_comment_id' => $comment->id,
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $comment->increment('likes');
            
            // Deduct points
            $user->decrement('pp', 24);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'กดถูกใจสำเร็จ',
                'action' => 'liked',
                'likes' => $comment->likes,
                'pp' => $user->pp
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dislike a share comment
     */
    public function dislike($commentId)
    {
        $comment = ShareComment::findOrFail($commentId);
        $user = Auth::user();
        $user->refresh(); // Get fresh points from database

        // Can't dislike own comment
        if ($comment->user_id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่สามารถกดไม่ถูกใจความคิดเห็นของตัวเองได้'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Check if already disliked
            $existingDislike = DB::table('share_comment_dislikes')
                ->where('share_comment_id', $comment->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existingDislike) {
                // Remove dislike
                DB::table('share_comment_dislikes')
                    ->where('share_comment_id', $comment->id)
                    ->where('user_id', $user->id)
                    ->delete();
                
                $comment->decrement('dislikes');
                
                // Return points
                $user->increment('pp', 12);
                
                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'message' => 'ยกเลิกการกดไม่ถูกใจแล้ว',
                    'action' => 'undisliked',
                    'dislikes' => $comment->dislikes,
                    'pp' => $user->pp
                ]);
            }

            // Remove like if exists
            $existingLike = DB::table('share_comment_likes')
                ->where('share_comment_id', $comment->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existingLike) {
                DB::table('share_comment_likes')
                    ->where('share_comment_id', $comment->id)
                    ->where('user_id', $user->id)
                    ->delete();
                
                $comment->decrement('likes');
                
                // Return points from like
                $user->increment('pp', 24);
            }

            // Refresh user to get latest points
            $user->refresh();

            // Check if user has enough points
            if ($user->pp < 12) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'คุณมีแต้มไม่เพียงพอ (ต้องการ 12 แต้ม)'
                ], 400);
            }

            // Add dislike
            DB::table('share_comment_dislikes')->insert([
                'share_comment_id' => $comment->id,
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $comment->increment('dislikes');
            
            // Deduct points
            $user->decrement('pp', 12);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'กดไม่ถูกใจสำเร็จ',
                'action' => 'disliked',
                'dislikes' => $comment->dislikes,
                'pp' => $user->pp
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ], 500);
        }
    }
}
