<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseDonationPilotSeeder extends Seeder
{
    public function run(): void
    {
        $courseId = $this->command?->option('course-id') ?: env('PILOT_COURSE_ID');

        if (! $courseId) {
            $this->command?->info('Usage: php artisan db:seed --class=CourseDonationPilotSeeder --course-id=COURSE_ID');

            return;
        }

        $course = Course::find($courseId);
        if (! $course) {
            $this->command?->error("Course {$courseId} not found.");

            return;
        }

        $course->update(['donation_enabled' => true]);
        $this->command?->info("Course {$courseId} donation pilot enabled.");
    }
}
