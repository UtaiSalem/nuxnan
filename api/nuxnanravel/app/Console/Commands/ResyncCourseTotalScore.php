<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\CourseExternalScore;
use App\Services\CourseScoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ResyncCourseTotalScore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'courses:resync-total-score {id?} {--all} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resync total_score for courses from actual quizzes and assignments scores.';

    /**
     * Execute the console command.
     */
    public function handle(CourseScoreService $service)
    {
        $id = $this->argument('id');
        $all = $this->option('all');
        $dryRun = $this->option('dry-run');

        if ($id) {
            $course = Course::find($id);
            if (! $course) {
                $this->error("Course $id not found.");

                return 1;
            }
            $this->resync($course, $service, $dryRun);
        } elseif ($all) {
            $total = Course::count();
            $bar = $this->output->createProgressBar($total);

            Course::chunk(100, function ($courses) use ($service, $dryRun, $bar) {
                foreach ($courses as $course) {
                    $this->resync($course, $service, $dryRun);
                    $bar->advance();
                }
            });

            $bar->finish();
            $this->newLine();
        } else {
            $this->error('Please specify a course ID or use --all.');

            return 1;
        }

        $this->info('Resync completed.');

        return 0;
    }

    protected function resync(Course $course, CourseScoreService $service, bool $dryRun)
    {
        $oldTotal = $course->total_score;

        // We use the service's calculation logic but we don't call syncCourseTotalScore
        // directly if we want to handle dry-run here, or we can improve the service.
        $internalTotal = $service->calculateInternalTotalScore($course);
        $externalTotal = CourseExternalScore::where('course_id', $course->id)->sum('max_score');

        $newTotal = max(0, $internalTotal + $externalTotal);

        if ($oldTotal != $newTotal) {
            $this->line("Course ID {$course->id} ({$course->name}): $oldTotal -> $newTotal".($dryRun ? ' (Dry Run)' : ''));

            // Log to storage/logs/course-resync.log
            Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/course-resync.log'),
            ])->info("Course resync: ID {$course->id}, Name: {$course->name}, Old: $oldTotal, New: $newTotal".($dryRun ? ' [DRY RUN]' : ''));

            if (! $dryRun) {
                $course->update(['total_score' => $newTotal]);
            }
        }
    }
}
