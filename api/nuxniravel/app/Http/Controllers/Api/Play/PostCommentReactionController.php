<?php

namespace App\Http\Controllers\Api\Play;

use App\Models\User;
use App\Models\PostComment;
// use Illuminate\Http\Request;

class PostCommentReactionController extends \App\Http\Controllers\Controller
{
    public function toggleLikePostComment(PostComment $post_comment)
    {
        try {
            $userId = auth()->id();
            $hasLiked = $post_comment->likedPostComment()->where('user_id', $userId)->exists();
            $hasDisliked = $post_comment->dislikedPostComment()->where('user_id', $userId)->exists();
            
            $requiredPoints = $hasLiked ? 12 : 24;
            
            if(auth()->user()->pp < $requiredPoints){
                return response()->json([
                    'success' => false,
                    'message' => $hasLiked 
                        ? 'You do not have enough points to unlike (12 points required). / คุณไม่มีพ้อยท์เพียงพอในการยกเลิกไลค์ (ต้องการ 12 แต้ม)'
                        : 'You do not have enough points to like (24 points required). / คุณไม่มีพ้อยท์เพียงพอในการกดถูกใจ (ต้องการ 24 แต้ม)',
                ]);
            }
            
            // ถ้ากด dislike อยู่แล้ว ต้องยกเลิก dislike ก่อน
            if ($hasDisliked) {
                $post_comment->dislikedPostComment()->detach($userId);
                $post_comment->decrement('dislikes');
            }
            
            $post_comment->likedPostComment()->toggle($userId);

            if($post_comment->likedPostComment()->where('user_id', $userId)->exists()){
                // Like
                $post_comment->increment('likes');
                auth()->user()->decrement('pp', 24);
                $post_comment->user->increment('pp', 12);
                User::find(1)->increment('pp', 12);
            }else{
                // Unlike
                $post_comment->decrement('likes');
                auth()->user()->decrement('pp', 12);
                // เจ้าของไม่ลดแต้ม
                User::find(1)->increment('pp', 12);
            }
            return response()->json([
                'success' => true,
            ]);

        } catch (\Throwable $th) {
            throw $th;
        }

    }

    public function toggleDislikePostComment(PostComment $post_comment)
    {
        try {
            $userId = auth()->id();
            $hasLiked = $post_comment->likedPostComment()->where('user_id', $userId)->exists();
            $hasDisliked = $post_comment->dislikedPostComment()->where('user_id', $userId)->exists();
            
            if(auth()->user()->pp < 12){
                return response()->json([
                    'success' => false,
                    'message' => $hasDisliked
                        ? 'You do not have enough points to undislike (12 points required). / คุณไม่มีพ้อยท์เพียงพอในการยกเลิกดิสไลค์ (ต้องการ 12 แต้ม)'
                        : 'You do not have enough points to dislike (12 points required). / คุณไม่มีพ้อยท์เพียงพอในการกดไม่ถูกใจ (ต้องการ 12 แต้ม)',
                ]);
            }
            
            // ถ้ากด like อยู่แล้ว ต้องยกเลิก like ก่อน
            if ($hasLiked) {
                $post_comment->likedPostComment()->detach($userId);
                $post_comment->decrement('likes');
            }
            
            $post_comment->dislikedPostComment()->toggle($userId);

            if($post_comment->dislikedPostComment()->where('user_id', $userId)->exists()){
                // Dislike
                $post_comment->increment('dislikes');
                auth()->user()->decrement('pp', 12);
                $post_comment->user->decrement('pp', 12);
                User::find(1)->increment('pp', 24);
            }else{
                // Undislike
                $post_comment->decrement('dislikes');
                auth()->user()->decrement('pp', 12);
                User::find(1)->increment('pp', 12);
            }

            return response()->json([
                'success' => true,
            ]);

        } catch (\Throwable $th) {
            throw $th;
        }

    }
}
