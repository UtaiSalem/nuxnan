<?php

namespace App\Http\Controllers;

use App\Models\Share;
use App\Models\User;
use Illuminate\Http\Request;

class ShareReactionController extends Controller
{
    /**
     * Toggle like on a share
     */
    public function toggleLike(Request $request, $id)
    {
        try {
            $user = $request->user();
            $share = Share::find($id);
            
            if (!$share) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบการแชร์'
                ], 404);
            }
            
            // Prevent liking own share
            if ($share->user_id === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่สามารถกดถูกใจการแชร์ของตัวเองได้'
                ], 403);
            }
            
            $hasLiked = $share->likedShare()->where('user_id', $user->id)->exists();
            $hasDisliked = $share->dislikedShare()->where('user_id', $user->id)->exists();
            
            // Calculate points
            $pointsRequired = $hasLiked ? 12 : 24;
            
            if ($user->pp < $pointsRequired) {
                return response()->json([
                    'success' => false,
                    'message' => $hasLiked 
                        ? 'แต้มของคุณไม่เพียงพอในการยกเลิกไลค์ (ต้องการ 12 แต้ม)'
                        : 'แต้มของคุณไม่เพียงพอในการกดถูกใจ (ต้องการ 24 แต้ม)'
                ], 400);
            }
            
            // Remove dislike if exists
            if ($hasDisliked) {
                $share->dislikedShare()->detach($user->id);
                $share->decrementDislikes();
            }
            
            // Toggle like
            $share->likedShare()->toggle($user->id);
            
            if ($share->likedShare()->where('user_id', $user->id)->exists()) {
                // Liked
                $share->incrementLikes();
                $user->decrement('pp', 24);
                $share->user->increment('pp', 12);
                User::find(1)->increment('pp', 12); // Super Admin
            } else {
                // Unliked
                $share->decrementLikes();
                $user->decrement('pp', 12);
                User::find(1)->increment('pp', 12); // Super Admin
            }
            
            return response()->json([
                'success' => true,
                'liked' => $share->likedShare()->where('user_id', $user->id)->exists(),
                'likes' => $share->likes,
                'dislikes' => $share->dislikes
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ], 500);
        }
    }

    /**
     * Toggle dislike on a share
     */
    public function toggleDislike(Request $request, $id)
    {
        try {
            $user = $request->user();
            $share = Share::find($id);
            
            if (!$share) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบการแชร์'
                ], 404);
            }
            
            // Prevent disliking own share
            if ($share->user_id === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่สามารถกดไม่ถูกใจการแชร์ของตัวเองได้'
                ], 403);
            }
            
            $hasLiked = $share->likedShare()->where('user_id', $user->id)->exists();
            $hasDisliked = $share->dislikedShare()->where('user_id', $user->id)->exists();
            
            if ($user->pp < 12) {
                return response()->json([
                    'success' => false,
                    'message' => 'แต้มของคุณไม่เพียงพอ (ต้องการ 12 แต้ม)'
                ], 400);
            }
            
            // Remove like if exists
            if ($hasLiked) {
                $share->likedShare()->detach($user->id);
                $share->decrementLikes();
            }
            
            // Toggle dislike
            $share->dislikedShare()->toggle($user->id);
            
            if ($share->dislikedShare()->where('user_id', $user->id)->exists()) {
                // Disliked
                $share->incrementDislikes();
                $user->decrement('pp', 12);
                $share->user->decrement('pp', 12);
                User::find(1)->increment('pp', 12); // Super Admin
            } else {
                // Undisliked
                $share->decrementDislikes();
                $user->decrement('pp', 12);
                User::find(1)->increment('pp', 12); // Super Admin
            }
            
            return response()->json([
                'success' => true,
                'disliked' => $share->dislikedShare()->where('user_id', $user->id)->exists(),
                'likes' => $share->likes,
                'dislikes' => $share->dislikes
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ], 500);
        }
    }
}
