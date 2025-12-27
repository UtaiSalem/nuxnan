<?php

namespace App\Http\Controllers\Api\Play;

use Illuminate\Http\Request;
use App\Models\PostImageComment;
use App\Models\User;

class PostImageCommentReactionController extends \App\Http\Controllers\Controller
{
    public function toggleLikePostImageComment(PostImageComment $post_image_comment)
    {
        try {
            $userId = auth()->id();
            $hasLiked = $post_image_comment->liked()->where('user_id', $userId)->exists();
            $hasDisliked = $post_image_comment->disliked()->where('user_id', $userId)->exists();
            
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
                $post_image_comment->disliked()->detach($userId);
                $post_image_comment->decrement('dislikes');
            }
            
            $toggled = $post_image_comment->liked()->toggle($userId);

            if(empty($toggled['detached'])){
                // Like
                $post_image_comment->increment('likes');
                auth()->user()->decrement('pp', 24);
                $post_image_comment->user->increment('pp', 12);
                User::find(1)->increment('pp', 12);
            }else{
                // Unlike
                $post_image_comment->decrement('likes');
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

    public function toggleDislikePostImageComment(PostImageComment $post_image_comment)
    {
        try {
            $userId = auth()->id();
            $hasLiked = $post_image_comment->liked()->where('user_id', $userId)->exists();
            $hasDisliked = $post_image_comment->disliked()->where('user_id', $userId)->exists();
            
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
                $post_image_comment->liked()->detach($userId);
                $post_image_comment->decrement('likes');
            }
            
            $toggled = $post_image_comment->disliked()->toggle($userId);

            if(empty($toggled['detached'])){
                // Dislike
                $post_image_comment->increment('dislikes');
                auth()->user()->decrement('pp', 12);
                $post_image_comment->user->decrement('pp', 12);
                User::find(1)->increment('pp', 24);
            }else{
                // Undislike
                $post_image_comment->decrement('dislikes');
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
