<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\AcademyPost;
use Illuminate\Support\Facades\Auth;

class AcademyPostReactionController extends Controller
{
    /**
     * Toggle like on an academy post
     */
    public function toggleLike(Academy $academy, AcademyPost $post)
    {
        $user = Auth::user();

        // Check if user is trying to like their own post
        if ($post->user_id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่สามารถกดถูกใจโพสต์ของตัวเองได้',
            ], 400);
        }

        // Check if already liked
        $isLiked = $post->likedPost()->where('user_id', $user->id)->exists();

        if ($isLiked) {
            // Unlike - remove the like
            $post->likedPost()->detach($user->id);
            $post->decrement('likes');

            // Deduct points for unlike (12 points to system)
            $user->decrement('pp', 12);

            return response()->json([
                'success' => true,
                'message' => 'ยกเลิกถูกใจแล้ว',
                'isLiked' => false,
                'likes' => $post->likes,
                'points_used' => 12,
            ]);
        } else {
            // Like - add the like
            // First remove dislike if exists
            if ($post->dislikedPost()->where('user_id', $user->id)->exists()) {
                $post->dislikedPost()->detach($user->id);
                $post->decrement('dislikes');
            }

            $post->likedPost()->attach($user->id);
            $post->increment('likes');

            // Deduct points (24 total: 12 to post owner, 12 to system)
            $user->decrement('pp', 24);

            // Give points to post owner
            $postOwner = $post->user;
            if ($postOwner) {
                $postOwner->increment('pp', 12);
            }

            return response()->json([
                'success' => true,
                'message' => 'ถูกใจแล้ว',
                'isLiked' => true,
                'likes' => $post->likes,
                'points_used' => 24,
                'points_to_author' => 12,
            ]);
        }
    }

    /**
     * Toggle dislike on an academy post
     */
    public function toggleDislike(Academy $academy, AcademyPost $post)
    {
        $user = Auth::user();

        // Check if user is trying to dislike their own post
        if ($post->user_id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่สามารถกดไม่ถูกใจโพสต์ของตัวเองได้',
            ], 400);
        }

        // Check if already disliked
        $isDisliked = $post->dislikedPost()->where('user_id', $user->id)->exists();

        if ($isDisliked) {
            // Un-dislike - remove the dislike
            $post->dislikedPost()->detach($user->id);
            $post->decrement('dislikes');

            // Deduct points for un-dislike (12 points to system)
            $user->decrement('pp', 12);

            return response()->json([
                'success' => true,
                'message' => 'ยกเลิกไม่ถูกใจแล้ว',
                'isDisliked' => false,
                'dislikes' => $post->dislikes,
                'points_used' => 12,
            ]);
        } else {
            // Dislike - add the dislike
            // First remove like if exists
            if ($post->likedPost()->where('user_id', $user->id)->exists()) {
                $post->likedPost()->detach($user->id);
                $post->decrement('likes');
            }

            $post->dislikedPost()->attach($user->id);
            $post->increment('dislikes');

            // Deduct points (12 to system)
            $user->decrement('pp', 12);

            // Deduct points from post owner
            $postOwner = $post->user;
            if ($postOwner) {
                $postOwner->decrement('pp', 12);
            }

            return response()->json([
                'success' => true,
                'message' => 'ไม่ถูกใจแล้ว',
                'isDisliked' => true,
                'dislikes' => $post->dislikes,
                'points_used' => 12,
            ]);
        }
    }
}
