<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Course;
use App\Services\CourseScoreService;

class CourseScoreSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'course:sync-scores {course_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recompute total_score for courses and external_score_points/grade_progress for members';

    /**
     * Execute the console command.
     */
    public function handle(CourseScoreService $scoreService)
    {
        $courseId = $this->argument('course_id');

        if ($courseId) {
            $course = Course::find($courseId);
            if (!$course) {
                $this->error("Course with ID {$courseId} not found.");
                return 1;
            }
            $this->syncCourse($course, $scoreService);
        } else {
            $courses = Course::all();
            $bar = $this->output->createProgressBar($courses->count());
            foreach ($courses as $course) {
                $this->syncCourse($course, $scoreService);
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
        }

        $this->info('Successfully synced scores and grades.');
        return 0;
    }

    protected function syncCourse(Course $course, CourseScoreService $scoreService)
    {
        // 1. Recompute course total_score
        $scoreService->syncCourseTotalScore($course);

        // 2. Recompute each member's external_score_points and grade_progress
        $scoreService->syncAllCourseMembers($course);
    }
}
