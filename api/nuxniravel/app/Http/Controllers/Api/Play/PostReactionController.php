<?php

namespace App\Http\Controllers\Api\Play;

use App\Models\Post;
use App\Models\User;

class PostReactionController extends \App\Http\Controllers\Controller
{
    public function toggleLikePost(Post $post)
    {
        $userId = auth()->id();
        $hasLiked = $post->likedPost()->where('user_id', $userId)->exists();
        $hasDisliked = $post->dislikedPost()->where('user_id', $userId)->exists();
        
        // ระบบใหม่: Like ใช้ 24 แต้ม, Unlike ใช้ 12 แต้ม
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
            $post->dislikedPost()->detach($userId);
            $post->decrement('dislikes');
            // Undislike ไม่คืนแต้มให้ทั้งสองฝ่าย (ตัดไปแล้ว 12 แต้ม)
        }
        
        // Toggle like
        $post->likedPost()->toggle($userId);
        
        // Get super admin safely
        $superAdmin = User::find(1);
        
        if($post->likedPost()->where('user_id', $userId)->exists()){
            // Like: ผู้กดเสีย 24 แต้ม (12 ให้เจ้าของ, 12 เข้าระบบ)
            $post->increment('likes');
            auth()->user()->decrement('pp', 24);
            if ($post->author) {
                $post->author->increment('pp', 12);
            }
            if ($superAdmin) {
                $superAdmin->increment('pp', 12); // Super Admin ได้ 12 แต้ม
            }
        } else {
            // Unlike: ผู้ยกเลิกเสีย 12 แต้ม (เข้าระบบ), เจ้าของไม่ลดแต้ม
            $post->decrement('likes');
            auth()->user()->decrement('pp', 12);
            // เจ้าของไม่ลดแต้ม
            if ($superAdmin) {
                $superAdmin->increment('pp', 12); // Super Admin ได้ 12 แต้ม
            }
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function toggleDislikePost(Post $post)
    {
        $userId = auth()->id();
        $hasLiked = $post->likedPost()->where('user_id', $userId)->exists();
        $hasDisliked = $post->dislikedPost()->where('user_id', $userId)->exists();
        
        // ระบบใหม่: ทั้ง Dislike และ Undislike ใช้ 12 แต้ม
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
            $post->likedPost()->detach($userId);
            $post->decrement('likes');
            // Unlike ไม่คืนแต้มให้ทั้งสองฝ่าย (ตัดไปแล้ว 12 แต้ม)
        }
        
        // Toggle dislike
        $post->dislikedPost()->toggle($userId);
        
        // Get super admin safely
        $superAdmin = User::find(1);
        
        if($post->dislikedPost()->where('user_id', $userId)->exists()){
            // Dislike: ผู้กดเสีย 12 แต้ม, เจ้าของเสีย 12 แต้ม, ระบบได้ 24 แต้ม
            $post->increment('dislikes');
            auth()->user()->decrement('pp', 12);
            if ($post->author) {
                $post->author->decrement('pp', 12);
            }
            if ($superAdmin) {
                $superAdmin->increment('pp', 24); // Super Admin ได้ 24 แต้ม
            }
        } else {
            // Undislike: ผู้ยกเลิกเสีย 12 แต้ม (เข้าระบบ), เจ้าของไม่คืนแต้ม
            $post->decrement('dislikes');
            auth()->user()->decrement('pp', 12);
            if ($superAdmin) {
                $superAdmin->increment('pp', 12); // Super Admin ได้ 12 แต้ม
            }
            // ไม่คืนแต้มให้เจ้าของโพสต์
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
