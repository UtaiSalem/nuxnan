<?php

namespace Database\Seeders;

use App\Models\Academy;
use App\Models\AcademyPost;
use App\Models\AcademyPostLike;
use App\Models\AcademyPostComment;
use App\Models\CourseMember;
use App\Models\SchoolAttendanceRecord;
use App\Models\EventRegistration;
use App\Models\AssignmentAnswer;
use App\Services\Gamification\XpService;
use App\Services\Gamification\ClassroomPointsService;
use Illuminate\Database\Seeder;

class BackfillGamificationSeeder extends Seeder
{
    public function run(XpService $xpService, ClassroomPointsService $classroomPointsService): void
    {
        $this->command->info('Backfilling gamification data...');

        // 1. Pre-create cycles for all academies and classrooms
        Academy::cursor()->each(function (Academy $academy) use ($xpService, $classroomPointsService) {
            $xpService->ensureCurrentCycles($academy);
            $classrooms = \App\Models\AcademyGroup::where('academy_id', $academy->id)
                ->where('type', 'classroom')
                ->get();
            foreach ($classrooms as $cl) {
                $classroomPointsService->ensureCurrentCycles($cl);
            }
        });

        // 2. Posts
        AcademyPost::cursor()->each(function (AcademyPost $post) use ($xpService) {
            if ($post->academy_id && $post->user_id) {
                $xpService->award(
                    $post->academy,
                    'post.created',
                    config('xp_rates.school.post.created', 10),
                    $post->user_id,
                    $post->posted_as_group_id,
                    ['backfill' => true, 'post_id' => $post->id]
                );
            }
        });

        // 3. Likes
        AcademyPostLike::cursor()->each(function (AcademyPostLike $like) use ($xpService) {
            $post = $like->post;
            if ($post && $post->academy_id && $post->user_id) {
                $xpService->award(
                    $post->academy,
                    'post.like_received',
                    config('xp_rates.school.post.like_received', 1),
                    $post->user_id,
                    $post->posted_as_group_id,
                    ['backfill' => true, 'like_id' => $like->id]
                );
            }
        });

        // 4. Comments
        AcademyPostComment::cursor()->each(function (AcademyPostComment $comment) use ($xpService) {
            $post = $comment->post;
            if ($post && $post->academy_id && $post->user_id) {
                $xpService->award(
                    $post->academy,
                    'post.comment_received',
                    config('xp_rates.school.post.comment_received', 2),
                    $post->user_id,
                    $post->posted_as_group_id,
                    ['backfill' => true, 'comment_id' => $comment->id]
                );
            }
        });

        // 5. Course Completion (CourseMember completed)
        CourseMember::where('completion_status', 'completed')->cursor()->each(function (CourseMember $member) use ($xpService) {
            $course = $member->course;
            if ($course && $course->academy_id && $member->user_id) {
                $xpService->award(
                    $course->academy,
                    'course.completed',
                    config('xp_rates.school.course.completed', 50),
                    $member->user_id,
                    null,
                    ['backfill' => true, 'course_id' => $course->id]
                );
            }
        });

        // 6. Event registrations (attended)
        EventRegistration::where('status', 'attended')->cursor()->each(function (EventRegistration $reg) use ($xpService) {
            $event = $reg->event;
            if ($event && $event->academy_id && $reg->user_id) {
                $xpService->award(
                    $event->academy,
                    'event.attended',
                    config('xp_rates.school.event.attended', 20),
                    $reg->user_id,
                    null,
                    ['backfill' => true, 'event_id' => $event->id]
                );
            }
        });

        // 7. School Attendance & Classroom check-in
        SchoolAttendanceRecord::where('status', '!=', 'absent')->cursor()->each(function (SchoolAttendanceRecord $rec) use ($xpService, $classroomPointsService) {
            $academy = $rec->academy;
            if ($academy && $rec->student_id) {
                $xpService->award(
                    $academy,
                    'attendance.recorded',
                    config('xp_rates.school.attendance.recorded', 5),
                    $rec->student_id,
                    null,
                    ['backfill' => true, 'attendance_record_id' => $rec->id]
                );

                $classroom = \App\Models\AcademyGroup::where('academy_id', $academy->id)
                    ->where('type', 'classroom')
                    ->whereHas('members', function ($query) use ($rec) {
                        $query->where('user_id', $rec->student_id);
                    })
                    ->first();

                if ($classroom) {
                    $classroomPointsService->award(
                        $classroom,
                        'attendance.checkin',
                        config('xp_rates.classroom.attendance.checkin', 1),
                        $rec->student_id,
                        ['backfill' => true, 'attendance_record_id' => $rec->id]
                    );
                }
            }
        });

        // 8. Assignment Submissions (Classroom Points)
        AssignmentAnswer::cursor()->each(function (AssignmentAnswer $answer) use ($classroomPointsService) {
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

                $classroomPointsService->award(
                    $classroom,
                    $source,
                    $points,
                    $user->id,
                    ['backfill' => true, 'assignment_id' => $assignment->id]
                );
            }
        });

        $this->command->info('Backfill gamification data completed.');
    }
}
