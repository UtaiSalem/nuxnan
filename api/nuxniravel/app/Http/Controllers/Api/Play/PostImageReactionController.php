<?php

namespace App\Http\Controllers\Api\Play;

use App\Models\User;
use App\Models\PostImage;
use Illuminate\Http\Request;

class PostImageReactionController extends \App\Http\Controllers\Controller
{
    public function toggleLikePostImage(PostImage $post_image)
    {
        try {
            $userId = auth()->id();
            $hasLiked = $post_image->likedPostImage()->where('user_id', $userId)->exists();
            $hasDisliked = $post_image->dislikedPostImage()->where('user_id', $userId)->exists();
            
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
                $post_image->dislikedPostImage()->detach($userId);
                $post_image->decrement('dislikes');
            }
            
            $result = $post_image->likedPostImage()->toggle($userId);

            if(empty($result['detached'])){
                // Like
                $post_image->increment('likes');
                auth()->user()->decrement('pp', 24);
                $post = $post_image->post;
                $post->user->increment('pp', 12);
                User::find(1)->increment('pp', 12);
            }else{
                // Unlike
                $post_image->decrement('likes');
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

    public function toggleDislikePostImage(PostImage $post_image)
    {
        try {
            if(auth()->user()->pp > 24){
                $userId = auth()->id();
                $hasLiked = $post_image->likedPostImage()->where('user_id', $userId)->exists();
                
                // ถ้ากด like อยู่แล้ว ต้องยกเลิก like ก่อน
                if ($hasLiked) {
                    $post_image->likedPostImage()->detach($userId);
                    $post_image->decrement('likes');
                    $post = $post_image->post;
                    $post->user()->decrement('pp', 12);
                }
                
                $dislike = $post_image->dislikedPostImage()->toggle($userId);

                if(empty($dislike['detached'])){
                    $post_image->increment('dislikes');
                    auth()->user()->decrement('pp', 12);

                    $post = $post_image->post;
                    
                    $post->user()->decrement('pp', 12);
                    User::find(1)->increment('pp', 24);
                }else{
                    $post_image->decrement('dislikes');
                    auth()->user()->decrement('pp', 12);
                };

                return response()->json([
                    'success' => true,
                ]);

            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have enough points to dislike this image. / คุณไม่มีพ้อยท์เพียงพอในการกดไม่ถูกใจรูปนี้ กรุณาสะสมแต้มเพิ่มเติม',
                ]);
            }

        } catch (\Throwable $th) {
            throw $th;
        }

    }
}
