<?php

namespace App\Observers;

use App\Models\AssignmentAnswer;
use App\Services\Gamification\ClassroomPointsService;

class AssignmentAnswerObserver
{
    public function __construct(protected ClassroomPointsService $pointsService) {}

    public function created(AssignmentAnswer $answer): void
    {
        $user = $answer->user;
        $assignment = $answer->assignment;
        if (!$user || !$assignment) {
            return;
        }

        $lesson = $assignment->getLesson();
        if (!$lesson) {
            return;
        }

        $course = $lesson->course;
        if (!$course) {
            return;
        }

        $course->loadMissing('academy');
        $academy = $course->academy;
        if (!$academy) {
            return;
        }

        // Find student's classroom
        $classroom = \App\Models\AcademyGroup::where('academy_id', $academy->id)
            ->where('type', 'classroom')
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        if ($classroom) {
            $isLate = $answer->late_submission ?? false;
            $points = $isLate 
                ? config('xp_rates.classroom.assignment.submitted_late', 2) 
                : config('xp_rates.classroom.assignment.submitted_on_time', 5);

            $source = $isLate ? 'assignment.submitted_late' : 'assignment.submitted_on_time';

            $this->pointsService->award(
                $classroom,
                $source,
                $points,
                $user->id,
                [
                    'assignment_id' => $assignment->id,
                    'answer_id'     => $answer->id,
                ]
            );
        }
    }
}
