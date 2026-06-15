<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\Lesson;
use App\Models\LessonScoreReset;
use App\Models\AssignmentAnswer;
use App\Models\LessonAnswerQuestion;
use App\Events\LessonScoreResetEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LessonScoreResetService
{
    protected CourseScoreService $scoreService;

    public function __construct(CourseScoreService $scoreService)
    {
        $this->scoreService = $scoreService;
    }

    /**
     * Reset quiz scores for a member in a specific lesson.
     */
    public function resetQuiz(CourseMember $member, Lesson $lesson, $adminId, $reason = null)
    {
        return DB::transaction(function () use ($member, $lesson, $adminId, $reason) {
            $userId = $member->user_id;
            
            // Snapshot old scores
            $oldScores = LessonAnswerQuestion::where('user_id', $userId)
                ->whereHas('question', function($q) use ($lesson) {
                    $q->where('questionable_id', $lesson->id)
                      ->where('questionable_type', Lesson::class);
                })
                ->get()
                ->toArray();

            // Delete answers
            LessonAnswerQuestion::where('user_id', $userId)
                ->whereHas('question', function($q) use ($lesson) {
                    $q->where('questionable_id', $lesson->id)
                      ->where('questionable_type', Lesson::class);
                })
                ->delete();

            // Audit log
            LessonScoreReset::create([
                'course_id' => $member->course_id,
                'course_member_id' => $member->id,
                'lesson_id' => $lesson->id,
                'admin_id' => $adminId,
                'reset_type' => 'quiz',
                'old_score_snapshot' => $oldScores,
                'reason' => $reason,
            ]);

            // Broadcast
            event(new LessonScoreResetEvent($member->course_id, $userId, $lesson->id, 'quiz'));

            // Recompute
            return $this->scoreService->recompute($member);
        });
    }

    /**
     * Reset assignment status/points (Graded -> Submitted).
     */
    public function resetAssignmentGrade(CourseMember $member, Lesson $lesson, $adminId, $reason = null)
    {
        return DB::transaction(function () use ($member, $lesson, $adminId, $reason) {
            $userId = $member->user_id;
            
            $answers = AssignmentAnswer::where('user_id', $userId)
                ->whereHas('assignment', function($q) use ($lesson) {
                    $q->where('assignmentable_id', $lesson->id)
                      ->where('assignmentable_type', Lesson::class);
                })
                ->get();

            $oldScores = $answers->toArray();

            foreach ($answers as $answer) {
                $answer->update([
                    'points' => null,
                    'status' => 'submitted',
                    'graded_at' => null,
                    'grader_id' => null,
                ]);
            }

            LessonScoreReset::create([
                'course_id' => $member->course_id,
                'course_member_id' => $member->id,
                'lesson_id' => $lesson->id,
                'admin_id' => $adminId,
                'reset_type' => 'assignment_grade',
                'old_score_snapshot' => $oldScores,
                'reason' => $reason,
            ]);

            // Broadcast
            event(new LessonScoreResetEvent($member->course_id, $userId, $lesson->id, 'assignment_grade'));

            return $this->scoreService->recompute($member);
        });
    }

    /**
     * Fully remove assignment submission and files.
     */
    public function resetAssignmentFull(CourseMember $member, Lesson $lesson, $adminId, $reason = null)
    {
        return DB::transaction(function () use ($member, $lesson, $adminId, $reason) {
            $userId = $member->user_id;
            
            $answers = AssignmentAnswer::with('images')
                ->where('user_id', $userId)
                ->whereHas('assignment', function($q) use ($lesson) {
                    $q->where('assignmentable_id', $lesson->id)
                      ->where('assignmentable_type', Lesson::class);
                })
                ->get();

            $oldScores = $answers->toArray();

            foreach ($answers as $answer) {
                // 1. Delete single attachment file (attachment_path is relative to public disk)
                if (!empty($answer->attachment_path)) {
                    Storage::disk('public')->delete($answer->attachment_path);
                }

                // 2. Delete image files + rows (filename stored on public disk under images/courses/assignments/answers/)
                foreach ($answer->images as $image) {
                    if (!empty($image->filename)) {
                        Storage::disk('public')->delete('images/courses/assignments/answers/' . $image->filename);
                    }
                    $image->delete();
                }

                // 3. Delete the answer row itself
                $answer->delete();
            }

            LessonScoreReset::create([
                'course_id' => $member->course_id,
                'course_member_id' => $member->id,
                'lesson_id' => $lesson->id,
                'admin_id' => $adminId,
                'reset_type' => 'assignment_full',
                'old_score_snapshot' => $oldScores,
                'reason' => $reason,
            ]);

            // Broadcast
            event(new LessonScoreResetEvent($member->course_id, $userId, $lesson->id, 'assignment_full'));

            return $this->scoreService->recompute($member);
        });
    }
}
