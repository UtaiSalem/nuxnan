<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons;

use App\Http\Controllers\Controller;

use App\Models\User;
use App\Models\Course;
use App\Models\Lesson;

class CourseLessonReactionController extends Controller
{
    public $plearndAdmin = null;

    public function __construct()
    {
        $this->plearndAdmin = User::find(1);
    }

    public function toggleLike(Course $course, Lesson $lesson)
    {
        $userId = auth()->id();
        $hasLiked = $lesson->likeLesson()->where('user_id', $userId)->exists();
        $hasDisliked = $lesson->dislikeLesson()->where('user_id', $userId)->exists();
        
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
            $lesson->dislikeLesson()->detach($userId);
            $lesson->decrement('dislike_count');
            // Undislike ไม่คืนแต้มให้ทั้งสองฝ่าย
        }
        
        // Toggle like
        $lesson->likeLesson()->toggle($userId);
        
        if($lesson->likeLesson()->where('user_id', $userId)->exists())
        {
            // Like: ผู้กดเสีย 24 แต้ม (12 ให้เจ้าของ, 12 เข้าระบบ)
            $lesson->increment('like_count');
            auth()->user()->decrement('pp', 24);
            $lesson->user->increment('pp', 12);
            $this->plearndAdmin->increment('pp', 12);
        }
        else
        {
            // Unlike: ผู้ยกเลิกเสีย 12 แต้ม (เข้าระบบ), เจ้าของไม่ลดแต้ม
            $lesson->decrement('like_count');
            auth()->user()->decrement('pp', 12);
            // เจ้าของไม่ลดแต้ม
            $this->plearndAdmin->increment('pp', 12);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function toggleDislike(Course $course, Lesson $lesson)
    {
        $userId = auth()->id();
        $hasLiked = $lesson->likeLesson()->where('user_id', $userId)->exists();
        $hasDisliked = $lesson->dislikeLesson()->where('user_id', $userId)->exists();
        
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
            $lesson->likeLesson()->detach($userId);
            $lesson->decrement('like_count');
            // Unlike ไม่คืนแต้มให้ทั้งสองฝ่าย
        }
        
        // Toggle dislike
        $lesson->dislikeLesson()->toggle($userId);
        
        if($lesson->dislikeLesson()->where('user_id', $userId)->exists()){
            // Dislike: ผู้กดเสีย 12 แต้ม, เจ้าของเสีย 12 แต้ม, ระบบได้ 24 แต้ม
            $lesson->increment('dislike_count');
            auth()->user()->decrement('pp', 12);
            $lesson->user->decrement('pp', 12);
            $this->plearndAdmin->increment('pp', 24);
        }
        else{
            // Undislike: ผู้ยกเลิกเสีย 12 แต้ม (เข้าระบบ), เจ้าของไม่คืนแต้ม
            $lesson->decrement('dislike_count');
            auth()->user()->decrement('pp', 12);
            $this->plearndAdmin->increment('pp', 12);
            // ไม่คืนแต้มให้เจ้าของบทเรียน
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
