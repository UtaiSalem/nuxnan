<?php

namespace App\Http\Controllers\Api\Learn\Course\posts;

use App\Http\Controllers\Controller;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\CoursePostComment;

class CoursePostCommentReactionController extends Controller
{
    public $plearndAdmin = null;

    public function __construct()
    {
        $this->plearndAdmin = User::find(1);
    }

    public function toggleLikeComment(CoursePostComment $comment)
    {
        $userId = auth()->id();
        $hasLiked = $comment->comment_likes()->where('user_id', $userId)->exists();
        $hasDisliked = $comment->comment_dislikes()->where('user_id', $userId)->exists();
        
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
            $comment->comment_dislikes()->detach($userId);
            $comment->decrement('dislikes');
        }
        
        $comment->comment_likes()->toggle($userId);
        if($comment->comment_likes()->where('user_id', $userId)->exists())
        {
            // Like
            $comment->increment('likes');
            auth()->user()->decrement('pp', 24);
            $comment->user->increment('pp', 12);
            $this->plearndAdmin->increment('pp', 12);
        }
        else
        {
            // Unlike
            $comment->decrement('likes');
            auth()->user()->decrement('pp', 12);
            // เจ้าของไม่ลดแต้ม
            $this->plearndAdmin->increment('pp', 12);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function toggleDislikeComment(CoursePostComment $comment)
    {
        $userId = auth()->id();
        $hasLiked = $comment->comment_likes()->where('user_id', $userId)->exists();
        $hasDisliked = $comment->comment_dislikes()->where('user_id', $userId)->exists();
        
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
            $comment->comment_likes()->detach($userId);
            $comment->decrement('likes');
        }
        
        $comment->comment_dislikes()->toggle($userId);
        if($comment->comment_dislikes()->where('user_id', $userId)->exists()){
            // Dislike
            $comment->increment('dislikes');
            auth()->user()->decrement('pp', 12);
            $comment->user->decrement('pp', 12);
            $this->plearndAdmin->increment('pp', 24);
        }
        else{
            // Undislike
            $comment->decrement('dislikes');
            auth()->user()->decrement('pp', 12);
            $this->plearndAdmin->increment('pp', 12);
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
