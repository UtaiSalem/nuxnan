<?php

namespace App\Observers;

use App\Models\CourseMember;
use App\Services\Gamification\XpService;

class CourseMemberObserver
{
    public function __construct(protected XpService $xpService) {}

    public function updated(CourseMember $member): void
    {
        if ($member->isDirty('completion_status') && $member->completion_status === 'completed') {
            $course = $member->course;
            if (!$course) {
                return;
            }

            $course->loadMissing('academy');
            if ($course->academy) {
                $this->xpService->award(
                    $course->academy,
                    'course.completed',
                    config('xp_rates.school.course.completed', 50),
                    $member->user_id,
                    null,
                    [
                        'course_id'   => $course->id,
                        'course_code' => $course->code,
                        'grade'       => $member->final_grade,
                    ]
                );
            }
        }
    }
}
