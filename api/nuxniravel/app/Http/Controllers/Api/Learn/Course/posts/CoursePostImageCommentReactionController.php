<?php

namespace App\Http\Controllers\Api\Learn\Course\posts;

use App\Http\Controllers\Controller;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\CoursePostImageComment;

class CoursePostImageCommentReactionController extends Controller
{
    public $plearndAdmin = null;

    public function __construct()
    {
        $this->plearndAdmin = User::find(1);
    }

    public function toggleLikeComment(CoursePostImageComment $comment)
    {
        try {
            if(auth()->user()->pp > 24){
                $userId = auth()->id();
                $hasDisliked = $comment->disliked()->where('user_id', $userId)->exists();
                
                // ถ้ากด dislike อยู่แล้ว ต้องยกเลิก dislike ก่อน
                if ($hasDisliked) {
                    $comment->disliked()->detach($userId);
                    $comment->decrement('dislikes');
                    auth()->user()->decrement('pp', 12);
                    $this->plearndAdmin->increment('pp', 12);
                }
                
                $comment->liked()->toggle($userId);
                if($comment->liked()->where('user_id', $userId)->exists())
                {
                    $comment->increment('likes');
                    auth()->user()->decrement('pp', 24);
                    $comment->user()->increment('pp',12);
                    $this->plearndAdmin->increment('pp', 12);
                }
                else
                {
                    $comment->decrement('likes');
                    auth()->user()->decrement('pp', 12);
                    $this->plearndAdmin->increment('pp', 12);
                };
    
                return response()->json([
                    'success' => true,
                ]);


            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have enough points to like this post. / คุณไม่มีพ้อยท์เพียงพอในการกดถูกใจโพสต์นี้ กรุณาสะสมแต้มเพิ่มเติม',
                ]);
            }

        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $th->getMessage(),
            ]);
        }
    }

    public function toggleDislikeComment(CoursePostImageComment $comment)
    {
        try {
            if(auth()->user()->pp > 24){
                $userId = auth()->id();
                $hasLiked = $comment->liked()->where('user_id', $userId)->exists();
                
                // ถ้ากด like อยู่แล้ว ต้องยกเลิก like ก่อน
                if ($hasLiked) {
                    $comment->liked()->detach($userId);
                    $comment->decrement('likes');
                    auth()->user()->decrement('pp', 12);
                    $this->plearndAdmin->increment('pp', 12);
                }
                
                $comment->disliked()->toggle($userId);
                if($comment->disliked()->where('user_id', $userId)->exists()){
                    $comment->increment('dislikes');
                    auth()->user()->decrement('pp', 12);
                    $comment->user()->decrement('pp', 12);
                    $this->plearndAdmin->increment('pp', 24);
                }
                else{
                    $comment->decrement('dislikes');
                    auth()->user()->decrement('pp', 12);
                    $this->plearndAdmin->increment('pp', 12);
                }
    
                return response()->json([
                    'success' => true,
                ]);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have enough points to dislike this post. / คุณไม่มีพ้อยท์เพียงพอในการกดไม่ถูกใจโพสต์นี้ กรุณาสะสมแต้มเพิ่มเติม',
                ]);
            }
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $th->getMessage(),
            ]);
        }
    }

}
