<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons;

use App\Http\Controllers\Controller;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Support\Facades\DB;

class CourseLessonReactionController extends Controller
{
    private const REACTION_COST = 24;
    private const REACTION_REWARD = 12;

    public function toggleReaction(Course $course, Lesson $lesson, string $reactionType)
    {
        if (auth()->user()->pp < self::REACTION_COST) {
            return $this->insufficientPointsResponse($reactionType);
        }

        return DB::transaction(function () use ($course, $lesson, $reactionType) {
            $userId = auth()->id();
            $relationMethod = $reactionType . 'Lesson';
            $countColumn = $reactionType . '_count';
            
            // กำหนดอีกประเภทหนึ่ง
            $oppositeType = $reactionType === 'like' ? 'dislike' : 'like';
            $oppositeRelationMethod = $oppositeType . 'Lesson';
            $oppositeCountColumn = $oppositeType . '_count';
            
            // ตรวจสอบว่ามีการกดอีกอย่างหนึ่งอยู่หรือไม่
            $hasOppositeReaction = $lesson->$oppositeRelationMethod()->where('user_id', $userId)->exists();
            
            // ถ้ามีการกดอีกอย่างหนึ่งอยู่ ให้ยกเลิกก่อน
            if ($hasOppositeReaction) {
                $lesson->$oppositeRelationMethod()->detach($userId);
                $lesson->decrement($oppositeCountColumn);
                auth()->user()->decrement('pp', self::REACTION_REWARD);
                $course->increment('points', self::REACTION_REWARD);
            }

            $isReacted = $lesson->$relationMethod()->toggle($userId);
            $wasToggled = !empty($isReacted['attached']);

            if ($wasToggled) {
                $lesson->increment($countColumn);
                auth()->user()->decrement('pp', self::REACTION_COST);
                $lesson->user()->increment('pp', self::REACTION_REWARD);
                $course->increment('points', self::REACTION_REWARD);
            } else {
                $lesson->decrement($countColumn);
                auth()->user()->decrement('pp', self::REACTION_REWARD);
                $course->increment('points', self::REACTION_REWARD);
            }

            return response()->json(['success' => true]);
        });
    }

    public function toggleLike(Course $course, Lesson $lesson)
    {
        return $this->toggleReaction($course, $lesson, 'like');
    }

    public function toggleDislike(Course $course, Lesson $lesson)
    {
        return $this->toggleReaction($course, $lesson, 'dislike');
    }

    private function insufficientPointsResponse(string $reactionType)
    {
        $action = $reactionType === 'liked' ? 'ถูกใจ' : 'ไม่ถูกใจ';
        return response()->json([
            'success' => false,
            'message' => "คุณมีพอยต์ไม่เพียงพอในการกด{$action}บทเรียนนี้ กรุณาสะสมแต้มเพิ่มเติม",
        ], 403);
    }
}
