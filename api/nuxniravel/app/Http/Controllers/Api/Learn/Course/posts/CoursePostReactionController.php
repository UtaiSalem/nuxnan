<?php

namespace App\Http\Controllers\Api\Learn\Course\posts;

use App\Http\Controllers\Controller;

use App\Models\User;
use App\Models\Course;
use App\Models\CoursePost;

class CoursePostReactionController extends Controller
{
    public $plearndAdmin = null;

    public function __construct()
    {
        $this->plearndAdmin = User::find(1);
    }

    public function toggleLike(Course $course, CoursePost $course_post)
    {
        $userId = auth()->id();
        $hasLiked = $course_post->likedPost()->where('user_id', $userId)->exists();
        $hasDisliked = $course_post->dislikedPost()->where('user_id', $userId)->exists();
        
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
            $course_post->dislikedPost()->detach($userId);
            $course_post->decrement('dislikes');
            // Undislike ไม่คืนแต้มให้ทั้งสองฝ่าย
        }
        
        // Toggle like
        $course_post->likedPost()->toggle($userId);
        
        if($course_post->likedPost()->where('user_id', $userId)->exists())
        {
            // Like: ผู้กดเสีย 24 แต้ม (12 ให้เจ้าของ, 12 เข้าระบบ)
            $course_post->increment('likes');
            auth()->user()->decrement('pp', 24);
            $course_post->user->increment('pp', 12);
            $this->plearndAdmin->increment('pp', 12);
        }
        else
        {
            // Unlike: ผู้ยกเลิกเสีย 12 แต้ม (เข้าระบบ), เจ้าของไม่ลดแต้ม
            $course_post->decrement('likes');
            auth()->user()->decrement('pp', 12);
            // เจ้าของไม่ลดแต้ม
            $this->plearndAdmin->increment('pp', 12);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function toggleDislike(Course $course, CoursePost $course_post)
    {
        $userId = auth()->id();
        $hasLiked = $course_post->likedPost()->where('user_id', $userId)->exists();
        $hasDisliked = $course_post->dislikedPost()->where('user_id', $userId)->exists();
        
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
            $course_post->likedPost()->detach($userId);
            $course_post->decrement('likes');
            // Unlike ไม่คืนแต้มให้ทั้งสองฝ่าย
        }
        
        // Toggle dislike
        $course_post->dislikedPost()->toggle($userId);
        
        if($course_post->dislikedPost()->where('user_id', $userId)->exists()){
            // Dislike: ผู้กดเสีย 12 แต้ม, เจ้าของเสีย 12 แต้ม, ระบบได้ 24 แต้ม
            $course_post->increment('dislikes');
            auth()->user()->decrement('pp', 12);
            $course_post->user->decrement('pp', 12);
            $this->plearndAdmin->increment('pp', 24);
        }
        else{
            // Undislike: ผู้ยกเลิกเสีย 12 แต้ม (เข้าระบบ), เจ้าของไม่คืนแต้ม
            $course_post->decrement('dislikes');
            auth()->user()->decrement('pp', 12);
            $this->plearndAdmin->increment('pp', 12);
            // ไม่คืนแต้มให้เจ้าของโพสต์
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
