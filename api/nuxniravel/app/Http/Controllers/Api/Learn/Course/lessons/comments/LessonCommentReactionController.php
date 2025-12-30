<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons\comments;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\LessonComment;

class LessonCommentReactionController extends Controller
{
    public $plearndAdmin = null;

    public function __construct()
    {
        $this->plearndAdmin = User::find(1);
    }

    public function toggleLike(LessonComment $lesson_comment)
    {
        $userId = auth()->id();
        $hasLiked = $lesson_comment->likeComment()->where('user_id', $userId)->exists();
        $hasDisliked = $lesson_comment->dislikeComment()->where('user_id', $userId)->exists();
        
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
            $lesson_comment->dislikeComment()->detach($userId);
            $lesson_comment->decrement('dislikes');
        }
        
        $lesson_comment->likeComment()->toggle($userId);
        if($lesson_comment->likeComment()->where('user_id', $userId)->exists())
        {
            // Like
            $lesson_comment->increment('likes');
            auth()->user()->decrement('pp', 24);
            $lesson_comment->user->increment('pp', 12);
            $this->plearndAdmin->increment('pp', 12);
        }
        else
        {
            // Unlike
            $lesson_comment->decrement('likes');
            auth()->user()->decrement('pp', 12);
            // เจ้าของไม่ลดแต้ม
            $this->plearndAdmin->increment('pp', 12);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function toggleDislike(LessonComment $lesson_comment)
    {
        $userId = auth()->id();
        $hasLiked = $lesson_comment->likeComment()->where('user_id', $userId)->exists();
        $hasDisliked = $lesson_comment->dislikeComment()->where('user_id', $userId)->exists();
        
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
            $lesson_comment->likeComment()->detach($userId);
            $lesson_comment->decrement('likes');
        }
        
        $lesson_comment->dislikeComment()->toggle($userId);
        if($lesson_comment->dislikeComment()->where('user_id', $userId)->exists()){
            // Dislike
            $lesson_comment->increment('dislikes');
            auth()->user()->decrement('pp', 12);
            $lesson_comment->user->decrement('pp', 12);
            $this->plearndAdmin->increment('pp', 24);
        }
        else{
            // Undislike
            $lesson_comment->decrement('dislikes');
            auth()->user()->decrement('pp', 12);
            $this->plearndAdmin->increment('pp', 12);
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
